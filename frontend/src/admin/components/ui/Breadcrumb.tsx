import { Link } from "react-router-dom";
import { ChevronRight } from "lucide-react";

interface Crumb {
  label: string;
  to?: string;
}

export function Breadcrumb({ items }: { items: Crumb[] }) {
  return (
    <nav className="flex items-center gap-1.5 text-sm">
      {items.map((item, i) => (
        <span key={i} className="flex items-center gap-1.5">
          {i > 0 && <ChevronRight size={13} className="text-a-muted dark:text-a-dark-muted" />}
          {item.to ? (
            <Link to={item.to} className="text-a-muted transition-colors hover:text-a-accent-2 dark:text-a-dark-muted dark:hover:text-a-accent">
              {item.label}
            </Link>
          ) : (
            <span className="font-medium text-a-text dark:text-a-dark-text">{item.label}</span>
          )}
        </span>
      ))}
    </nav>
  );
}
