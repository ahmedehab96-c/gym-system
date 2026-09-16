import type { ReactNode } from "react";
import { motion } from "framer-motion";
import { ArrowDownRight, ArrowUpRight } from "lucide-react";
import { cn } from "../../../utils/cn";

interface StatCardProps {
  label: string;
  value: string;
  change?: number;
  icon: ReactNode;
  accent?: string;
}

export function StatCard({ label, value, change, icon, accent = "from-a-accent/20 to-a-accent-2/10" }: StatCardProps) {
  const isPositive = (change ?? 0) >= 0;
  return (
    <motion.div
      whileHover={{ y: -3 }}
      className="admin-card relative overflow-hidden rounded-2xl p-5 shadow-sm transition-shadow hover:shadow-md"
    >
      <div className={cn("absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br opacity-70 blur-2xl", accent)} />
      <div className="relative flex items-start justify-between">
        <div>
          <p className="text-xs font-medium text-a-muted dark:text-a-dark-muted">{label}</p>
          <p className="mt-2 text-2xl font-bold tracking-tight text-a-text dark:text-a-dark-text">{value}</p>
        </div>
        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-a-surface-2 text-a-accent-2 dark:bg-a-dark-surface-2 dark:text-a-accent">
          {icon}
        </div>
      </div>
      {change !== undefined && (
        <div className="relative mt-3 flex items-center gap-1 text-xs font-medium">
          <span className={cn("flex items-center gap-0.5 rounded-md px-1.5 py-0.5", isPositive ? "bg-emerald-500/10 text-emerald-600 dark:text-emerald-400" : "bg-rose-500/10 text-rose-600 dark:text-rose-400")}>
            {isPositive ? <ArrowUpRight size={12} /> : <ArrowDownRight size={12} />}
            {Math.abs(change)}%
          </span>
          <span className="text-a-muted dark:text-a-dark-muted">vs last period</span>
        </div>
      )}
    </motion.div>
  );
}
