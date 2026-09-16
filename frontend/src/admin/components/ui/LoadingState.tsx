export function LoadingState({ rows = 5 }: { rows?: number }) {
  return (
    <div className="space-y-3">
      {Array.from({ length: rows }, (_, i) => (
        <div key={i} className="h-12 animate-pulse rounded-xl bg-a-surface-2 dark:bg-a-dark-surface-2" />
      ))}
    </div>
  );
}

export function Spinner({ size = 20 }: { size?: number }) {
  return (
    <div
      className="animate-spin rounded-full border-2 border-a-border border-t-a-accent dark:border-a-dark-border"
      style={{ width: size, height: size }}
    />
  );
}
