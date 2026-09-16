import { motion } from "framer-motion";
import type { Trainer } from "../../types";
import { InstagramIcon, LinkedinIcon, TwitterIcon } from "../ui/SocialIcons";

export function TrainerCard({ trainer, index = 0, onSelect }: { trainer: Trainer; index?: number; onSelect?: (t: Trainer) => void }) {
  return (
    <motion.article
      initial={{ opacity: 0, y: 36 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-60px" }}
      transition={{ duration: 0.6, delay: (index % 3) * 0.1, ease: [0.22, 1, 0.36, 1] }}
      className="group relative overflow-hidden rounded-2xl border border-white/10 bg-surface"
    >
      <div className="relative h-80 overflow-hidden">
        <img
          src={trainer.image}
          alt={trainer.name}
          loading="lazy"
          className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-ink via-ink/40 to-transparent" />
        <div className="absolute inset-x-0 bottom-0 flex items-center justify-center gap-3 pb-5 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
          {trainer.social.instagram && (
            <a href={trainer.social.instagram} aria-label={`${trainer.name} on Instagram`} className="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md hover:bg-gold-400 hover:text-ink">
              <InstagramIcon size={15} />
            </a>
          )}
          {trainer.social.twitter && (
            <a href={trainer.social.twitter} aria-label={`${trainer.name} on Twitter`} className="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md hover:bg-gold-400 hover:text-ink">
              <TwitterIcon size={15} />
            </a>
          )}
          {trainer.social.linkedin && (
            <a href={trainer.social.linkedin} aria-label={`${trainer.name} on LinkedIn`} className="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white backdrop-blur-md hover:bg-gold-400 hover:text-ink">
              <LinkedinIcon size={15} />
            </a>
          )}
        </div>
      </div>
      <div className="p-6 text-center">
        <h3 className="font-display text-xl tracking-wide text-white">{trainer.name}</h3>
        <p className="mb-1 text-xs font-semibold uppercase tracking-widest text-gold-300">{trainer.position}</p>
        <p className="mb-4 text-sm text-white/50">{trainer.specialty} · {trainer.experience}</p>
        <button
          onClick={() => onSelect?.(trainer)}
          className="text-xs font-semibold uppercase tracking-widest text-white/70 underline-offset-4 transition-colors hover:text-gold-300 hover:underline"
        >
          View Profile
        </button>
      </div>
    </motion.article>
  );
}
