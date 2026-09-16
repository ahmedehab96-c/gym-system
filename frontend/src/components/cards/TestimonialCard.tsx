import { motion } from "framer-motion";
import { Quote, Star } from "lucide-react";
import type { Testimonial } from "../../types";

export function TestimonialCard({ testimonial, index = 0 }: { testimonial: Testimonial; index?: number }) {
  return (
    <motion.article
      initial={{ opacity: 0, y: 30 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true, margin: "-60px" }}
      transition={{ duration: 0.55, delay: (index % 3) * 0.1 }}
      className="glass flex h-full flex-col rounded-2xl p-7"
    >
      <Quote className="mb-4 text-gold-400" size={28} />
      <p className="mb-6 flex-1 text-sm leading-relaxed text-white/75">"{testimonial.quote}"</p>
      <div className="mb-4 flex gap-1">
        {Array.from({ length: 5 }).map((_, i) => (
          <Star key={i} size={14} className={i < testimonial.rating ? "fill-gold-400 text-gold-400" : "text-white/20"} />
        ))}
      </div>
      <div className="flex items-center gap-3">
        <img src={testimonial.avatar} alt={testimonial.name} className="h-11 w-11 rounded-full object-cover" />
        <div>
          <p className="text-sm font-semibold text-white">{testimonial.name}</p>
          <p className="text-xs text-white/45">{testimonial.role}</p>
        </div>
      </div>
    </motion.article>
  );
}
