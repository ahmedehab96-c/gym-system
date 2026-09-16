import { AlertCircle, RotateCcw } from "lucide-react";
import { Button } from "./Button";

interface ErrorStateProps {
  message?: string;
  onRetry?: () => void;
}

export function ErrorState({ message, onRetry }: ErrorStateProps) {
  return (
    <div className="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-rose-500/30 bg-rose-500/5 px-6 py-14 text-center">
      <div className="flex h-12 w-12 items-center justify-center rounded-full bg-rose-500/10 text-rose-500">
        <AlertCircle size={20} />
      </div>
      <div>
        <p className="text-sm font-medium text-a-text dark:text-a-dark-text">Something went wrong</p>
        <p className="mt-1 text-xs text-a-muted dark:text-a-dark-muted">{message ?? "Please try again."}</p>
      </div>
      {onRetry && (
        <Button variant="secondary" size="sm" icon={<RotateCcw size={14} />} onClick={onRetry}>
          Retry
        </Button>
      )}
    </div>
  );
}
