import { cn } from "../../../utils/cn";

interface TabsProps {
  tabs: string[];
  active: string;
  onChange: (tab: string) => void;
  className?: string;
}

export function Tabs({ tabs, active, onChange, className }: TabsProps) {
  return (
    <div className={cn("flex items-center gap-1 overflow-x-auto rounded-xl bg-a-surface-2 p-1 dark:bg-a-dark-surface-2", className)}>
      {tabs.map((tab) => (
        <button
          key={tab}
          onClick={() => onChange(tab)}
          className={cn(
            "relative whitespace-nowrap rounded-lg px-4 py-2 text-sm font-medium transition-colors",
            active === tab
              ? "bg-a-surface text-a-text shadow-sm dark:bg-a-dark-surface dark:text-a-dark-text"
              : "text-a-muted hover:text-a-text dark:text-a-dark-muted dark:hover:text-a-dark-text",
          )}
        >
          {tab}
        </button>
      ))}
    </div>
  );
}
