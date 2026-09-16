import { motion, AnimatePresence } from "framer-motion";
import { Check } from "lucide-react";
import type { MembershipPlan } from "../../types";
import { Button } from "../ui/Button";
import { cn } from "../../utils/cn";

export function PricingCard({
  plan,
  yearly,
  index = 0,
}: {
  plan: MembershipPlan;
  yearly: boolean;
  index?: number;
}) {
  const price = yearly ? plan.yearlyPrice : plan.monthlyPrice;

  return (
    <motion.article
      initial={{ opacity: 0, y: 40 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-60px" }}
      transition={{ duration: 0.6, delay: index * 0.1, ease: [0.22, 1, 0.36, 1] }}
      whileHover={{ y: -8 }}
      className={cn(
        "relative flex flex-col rounded-3xl border p-8",
        plan.popular
          ? "border-gold-400/50 bg-gradient-to-b from-gold-400/10 to-surface shadow-[0_20px_60px_-20px_rgba(212,167,47,0.5)]"
          : "border-white/10 bg-surface",
      )}
    >
      {plan.popular && (
        <span className="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-gold-300 to-gold-500 px-4 py-1.5 text-[10px] font-bold uppercase tracking-widest text-ink">
          Most Popular
        </span>
      )}
      <h3 className="font-display text-2xl tracking-wide text-white">{plan.name}</h3>
      <p className="mb-6 text-sm text-white/50">{plan.tagline}</p>

      <div className="mb-6 flex items-end gap-1">
        <span className="text-sm text-white/50 pb-1.5">$</span>
        <AnimatePresence mode="wait">
          <motion.span
            key={yearly ? "y" : "m"}
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -8 }}
            transition={{ duration: 0.25 }}
            className="font-display text-5xl text-white"
          >
            {price}
          </motion.span>
        </AnimatePresence>
        <span className="pb-1.5 text-sm text-white/50">/ {yearly ? "yr" : "mo"}</span>
      </div>

      <ul className="mb-8 flex flex-1 flex-col gap-3.5">
        {plan.features.map((feature) => (
          <li key={feature} className="flex items-start gap-3 text-sm text-white/70">
            <Check size={16} className="mt-0.5 shrink-0 text-gold-400" />
            {feature}
          </li>
        ))}
      </ul>

      <Button to="/membership" variant={plan.popular ? "primary" : "outline"} className="w-full">
        Choose {plan.name}
      </Button>
    </motion.article>
  );
}
