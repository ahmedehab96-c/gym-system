import { motion } from "framer-motion";
import { Clock, Signal } from "lucide-react";
import type { Program } from "../../types";
import { Button } from "../ui/Button";

export function ProgramCard({ program, index = 0 }: { program: Program; index?: number }) {
  return (
    <motion.article
      initial={{ opacity: 0, y: 36 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-60px" }}
      transition={{ duration: 0.6, delay: (index % 3) * 0.1, ease: [0.22, 1, 0.36, 1] }}
      className="group flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-surface"
    >
      <div className="relative h-56 overflow-hidden">
        <img
          src={program.image}
          alt={program.title}
          loading="lazy"
          className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-ink via-ink/10 to-transparent" />
        <span className="absolute left-4 top-4 rounded-full bg-gold-400 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-ink">
          {program.difficulty}
        </span>
      </div>
      <div className="flex flex-1 flex-col p-6">
        <h3 className="mb-2 font-display text-2xl tracking-wide text-white">{program.title}</h3>
        <p className="mb-5 flex-1 text-sm leading-relaxed text-white/55">{program.description}</p>
        <div className="mb-5 flex items-center gap-5 text-xs text-white/45">
          <span className="flex items-center gap-1.5">
            <Clock size={14} className="text-gold-400" /> {program.duration}
          </span>
          <span className="flex items-center gap-1.5">
            <Signal size={14} className="text-gold-400" /> {program.sessions}
          </span>
        </div>
        <Button to="/programs" variant="outline" size="sm" className="w-full">
          Learn More
        </Button>
      </div>
    </motion.article>
  );
}
