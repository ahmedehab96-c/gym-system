export function PageLoader() {
  return (
    <div className="flex min-h-[70vh] items-center justify-center">
      <div className="flex flex-col items-center gap-4">
        <span className="h-10 w-10 animate-spin rounded-full border-2 border-white/15 border-t-gold-400" />
        <span className="text-xs font-semibold uppercase tracking-[0.3em] text-white/55">Loading</span>
      </div>
    </div>
  );
}
