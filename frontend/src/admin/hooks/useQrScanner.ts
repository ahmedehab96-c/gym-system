import { useEffect, useRef, useState } from "react";
import jsQR from "jsqr";

/**
 * Drives a `<video>` element from the device camera and decodes QR codes
 * out of its frames via jsQR — the only camera/QR logic in the app
 * (Phase 28 §2 "camera-based QR scanning"). Pause scanning (set `active`
 * false) after a decode so the same physical code sitting in frame isn't
 * re-submitted on every animation frame; the caller re-activates it once
 * ready for the next scan.
 */
export function useQrScanner(active: boolean, onDecode: (text: string) => void) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const onDecodeRef = useRef(onDecode);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    onDecodeRef.current = onDecode;
  }, [onDecode]);

  useEffect(() => {
    if (!active) return;

    let cancelled = false;
    let frameId = 0;
    let stream: MediaStream | null = null;
    const video = videoRef.current;
    const canvas = document.createElement("canvas");
    const ctx = canvas.getContext("2d", { willReadFrequently: true });

    function tick() {
      const video = videoRef.current;
      if (video && ctx && video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const frame = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(frame.data, frame.width, frame.height, { inversionAttempts: "dontInvert" });
        if (code?.data) {
          onDecodeRef.current(code.data);
          return;
        }
      }
      frameId = requestAnimationFrame(tick);
    }

    (async () => {
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } });
        if (cancelled) {
          stream.getTracks().forEach((t) => t.stop());
          return;
        }
        setError(null);
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          await videoRef.current.play();
        }
        frameId = requestAnimationFrame(tick);
      } catch {
        if (!cancelled) setError("Camera access was denied or unavailable. Use manual search instead.");
      }
    })();

    return () => {
      cancelled = true;
      cancelAnimationFrame(frameId);
      stream?.getTracks().forEach((t) => t.stop());
      if (video) video.srcObject = null;
    };
  }, [active]);

  return { videoRef, error };
}
