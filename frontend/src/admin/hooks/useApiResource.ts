import { useCallback, useEffect, useRef, useState } from "react";
import { ApiError, type ApiItemResponse } from "../services/apiClient";

interface UseApiResourceOptions {
  enabled?: boolean;
}

interface UseApiResourceResult<T> {
  data: T | null;
  loading: boolean;
  error: string | null;
  refetch: () => void;
}

/** Same contract as useApiList but for a single-resource `{data: {...}}` endpoint. */
export function useApiResource<T>(
  fetcher: (signal: AbortSignal) => Promise<ApiItemResponse<T>>,
  deps: unknown[],
  options: UseApiResourceOptions = {},
): UseApiResourceResult<T> {
  const { enabled = true } = options;
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(enabled);
  const [error, setError] = useState<string | null>(null);
  const [reloadToken, setReloadToken] = useState(0);

  const fetcherRef = useRef(fetcher);
  fetcherRef.current = fetcher;

  useEffect(() => {
    if (!enabled) {
      setLoading(false);
      return;
    }

    const controller = new AbortController();
    setLoading(true);
    setError(null);

    fetcherRef
      .current(controller.signal)
      .then((response) => setData(response.data))
      .catch((err) => {
        if (controller.signal.aborted) return;
        setError(err instanceof ApiError ? err.message : "Something went wrong. Please try again.");
      })
      .finally(() => {
        if (!controller.signal.aborted) setLoading(false);
      });

    return () => controller.abort();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [enabled, reloadToken, ...deps]);

  const refetch = useCallback(() => setReloadToken((t) => t + 1), []);

  return { data, loading, error, refetch };
}
