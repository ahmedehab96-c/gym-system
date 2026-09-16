import { motion } from "framer-motion";
import { cn } from "../../utils/cn";

export function BillingToggle({ yearly, onChange }: { yearly: boolean; onChange: (v: boolean) => void }) {
  return (
    <div className="glass flex items-center gap-1 rounded-full p-1.5">
      <button
        onClick={() => onChange(false)}
        className={cn(
          "relative z-10 rounded-full px-5 py-2 text-xs font-semibold uppercase tracking-wider transition-colors",
          !yearly ? "text-ink" : "text-white/60",
        )}
      >
        {!yearly && (
          <motion.span layoutId="billing-pill" className="absolute inset-0 -z-10 rounded-full bg-gold-400" transition={{ type: "spring", duration: 0.5 }} />
        )}
        Monthly
      </button>
      <button
        onClick={() => onChange(true)}
        className={cn(
          "relative z-10 flex items-center gap-2 rounded-full px-5 py-2 text-xs font-semibold uppercase tracking-wider transition-colors",
          yearly ? "text-ink" : "text-white/60",
        )}
      >
        {yearly && (
          <motion.span layoutId="billing-pill" className="absolute inset-0 -z-10 rounded-full bg-gold-400" transition={{ type: "spring", duration: 0.5 }} />
        )}
        Yearly
        <span className={cn("rounded-full px-1.5 py-0.5 text-[9px]", yearly ? "bg-ink/15 text-ink" : "bg-gold-400/15 text-gold-300")}>
          -17%
        </span>
      </button>
    </div>
  );
}
