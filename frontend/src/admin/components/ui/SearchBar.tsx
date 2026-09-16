import { Search } from "lucide-react";

interface SearchBarProps {
  value: string;
  onChange: (v: string) => void;
  placeholder?: string;
  className?: string;
}

export function SearchBar({ value, onChange, placeholder = "Search...", className }: SearchBarProps) {
  return (
    <div className={`relative flex items-center ${className ?? ""}`}>
      <Search size={16} className="pointer-events-none absolute left-3 text-a-muted dark:text-a-dark-muted" />
      <input
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full rounded-xl border border-a-border bg-a-surface py-2.5 pl-9 pr-3 text-sm text-a-text outline-none transition-colors placeholder:text-a-muted focus:border-a-accent dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text dark:placeholder:text-a-dark-muted"
      />
    </div>
  );
}
