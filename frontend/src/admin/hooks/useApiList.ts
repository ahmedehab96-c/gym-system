import { useCallback, useEffect, useRef, useState } from "react";
import { ApiError, type ApiListResponse, type ApiMeta } from "../services/apiClient";

interface UseApiListOptions {
  /** Skip fetching (e.g. while a required param isn't ready yet). */
  enabled?: boolean;
}

interface UseApiListResult<T> {
  data: T[];
  meta: ApiMeta | null;
  loading: boolean;
  error: string | null;
  refetch: () => void;
}

/**
 * Fetches one page of a Laravel paginated list endpoint and re-fetches
 * whenever `deps` changes (search/filter/sort/page). The in-flight request
 * is aborted when deps change again or the component unmounts, so a slow
 * stale response can never overwrite a newer one.
 */
export function useApiList<T>(
  fetcher: (signal: AbortSignal) => Promise<ApiListResponse<T>>,
  deps: unknown[],
  options: UseApiListOptions = {},
): UseApiListResult<T> {
  const { enabled = true } = options;
  const [data, setData] = useState<T[]>([]);
  const [meta, setMeta] = useState<ApiMeta | null>(null);
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
      .then((response) => {
        setData(response.data);
        setMeta(response.meta);
      })
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

  return { data, meta, loading, error, refetch };
}
