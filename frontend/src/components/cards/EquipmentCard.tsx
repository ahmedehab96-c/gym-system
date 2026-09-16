import { motion } from "framer-motion";
import type { Equipment } from "../../types";

export function EquipmentCard({ item, index = 0 }: { item: Equipment; index?: number }) {
  return (
    <motion.article
      layout
      initial={{ opacity: 0, y: 30 }}
      animate={{ opacity: 1, y: 0 }}
      exit={{ opacity: 0, y: -20 }}
      transition={{ duration: 0.45, delay: (index % 6) * 0.06, ease: [0.22, 1, 0.36, 1] }}
      className="group relative overflow-hidden rounded-2xl border border-white/10 bg-surface"
    >
      <div className="relative h-56 overflow-hidden">
        <img
          src={item.image}
          alt={item.name}
          loading="lazy"
          className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-ink/95 via-ink/10 to-transparent" />
        <span className="absolute right-4 top-4 rounded-full border border-gold-400/30 bg-black/50 px-3 py-1 text-[10px] font-semibold uppercase tracking-widest text-gold-300 backdrop-blur-md">
          {item.category}
        </span>
      </div>
      <div className="p-5">
        <h3 className="mb-1.5 font-display text-lg tracking-wide text-white">{item.name}</h3>
        <p className="text-sm leading-relaxed text-white/55">{item.description}</p>
      </div>
    </motion.article>
  );
}
