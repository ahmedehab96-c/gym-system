import { useRef, useState } from "react";
import { ImagePlus, X, Loader2 } from "lucide-react";
import { cn } from "../../../utils/cn";

interface ImageUploaderProps {
  value?: string;
  onChange: (url: string | undefined) => void;
  className?: string;
  /**
   * Uploads the file to Laravel Storage and resolves to its public URL.
   * Omit this when the owning record doesn't exist yet (e.g. a new
   * announcement) — the component then just previews the file locally via
   * `onChange` and reports the raw File through `onFileSelected` so the
   * caller can upload it once the record has been created.
   */
  uploadFn?: (file: File) => Promise<string>;
  onFileSelected?: (file: File | undefined) => void;
}

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB, matches backend's max:5120 KB limit

export function ImageUploader({ value, onChange, className, uploadFn, onFileSelected }: ImageUploaderProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [dragging, setDragging] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | undefined>(undefined);

  async function handleFile(file?: File) {
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      setError("Please select an image file.");
      return;
    }
    if (file.size > MAX_FILE_SIZE) {
      setError("Image must be smaller than 5MB.");
      return;
    }
    setError(undefined);

    onFileSelected?.(file);

    if (!uploadFn) {
      onChange(URL.createObjectURL(file));
      return;
    }

    setUploading(true);
    try {
      const url = await uploadFn(file);
      onChange(url);
    } finally {
      setUploading(false);
    }
  }

  return (
    <div className="flex flex-col gap-1.5">
      <div
        onDragOver={(e) => {
          e.preventDefault();
          if (!uploading) setDragging(true);
        }}
        onDragLeave={() => setDragging(false)}
        onDrop={(e) => {
          e.preventDefault();
          setDragging(false);
          if (!uploading) handleFile(e.dataTransfer.files?.[0]);
        }}
        onClick={() => !uploading && inputRef.current?.click()}
        className={cn(
          "relative flex h-40 cursor-pointer flex-col items-center justify-center gap-2 overflow-hidden rounded-2xl border-2 border-dashed transition-colors",
          dragging ? "border-a-accent bg-a-accent/5" : "border-a-border hover:border-a-accent/50 dark:border-a-dark-border",
          uploading && "cursor-wait",
          className,
        )}
      >
        <input
          ref={inputRef}
          type="file"
          accept="image/*"
          className="hidden"
          onChange={(e) => handleFile(e.target.files?.[0])}
          disabled={uploading}
        />
        {uploading ? (
          <>
            <Loader2 size={22} className="animate-spin text-a-accent" />
            <p className="text-xs text-a-muted dark:text-a-dark-muted">Uploading...</p>
          </>
        ) : value ? (
          <>
            <img src={value} alt="Uploaded" className="absolute inset-0 h-full w-full object-cover" />
            <button
              type="button"
              onClick={(e) => {
                e.stopPropagation();
                onChange(undefined);
                onFileSelected?.(undefined);
              }}
              className="absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white"
            >
              <X size={14} />
            </button>
          </>
        ) : (
          <>
            <ImagePlus size={22} className="text-a-muted dark:text-a-dark-muted" />
            <p className="text-xs text-a-muted dark:text-a-dark-muted">Click or drag an image to upload</p>
          </>
        )}
      </div>
      {error && <span className="text-xs text-rose-500">{error}</span>}
    </div>
  );
}
