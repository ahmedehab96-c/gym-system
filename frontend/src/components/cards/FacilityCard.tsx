import * as Icons from "lucide-react";
import { motion } from "framer-motion";
import type { LucideIcon } from "lucide-react";
import type { Facility } from "../../types";

export function FacilityCard({ facility, index = 0 }: { facility: Facility; index?: number }) {
  const Icon = (Icons as unknown as Record<string, LucideIcon>)[facility.icon] ?? Icons.Dumbbell;

  return (
    <motion.article
      initial={{ opacity: 0, y: 36 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-60px" }}
      transition={{ duration: 0.6, delay: (index % 4) * 0.08, ease: [0.22, 1, 0.36, 1] }}
      className="group relative overflow-hidden rounded-2xl border border-white/10 bg-surface"
    >
      <div className="relative h-64 overflow-hidden">
        <img
          src={facility.image}
          alt={facility.title}
          loading="lazy"
          className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-ink via-ink/20 to-transparent" />
        <div className="absolute left-5 top-5 flex h-11 w-11 items-center justify-center rounded-xl border border-white/15 bg-black/40 text-gold-300 backdrop-blur-md">
          <Icon size={20} />
        </div>
      </div>
      <div className="p-6">
        <h3 className="mb-2 font-display text-xl tracking-wide text-white">{facility.title}</h3>
        <p className="text-sm leading-relaxed text-white/55">{facility.description}</p>
      </div>
      <div className="pointer-events-none absolute inset-0 rounded-2xl ring-1 ring-inset ring-gold-400/0 transition-all duration-500 group-hover:ring-gold-400/40" />
    </motion.article>
  );
}
