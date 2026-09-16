import { motion } from "framer-motion";
import { ArrowRight, ShieldCheck, Sparkles, Target } from "lucide-react";
import { Container } from "../ui/Container";
import { Badge } from "../ui/Badge";
import { Button } from "../ui/Button";
import { Reveal } from "../ui/Reveal";
import { IMG, unsplash } from "../../data/images";

const points = [
  { icon: Target, title: "Results-Driven", text: "Programs engineered around real, measurable progress." },
  { icon: ShieldCheck, title: "Elite Coaching", text: "Certified trainers with proven athletic pedigree." },
  { icon: Sparkles, title: "Luxury Standard", text: "Premium equipment, design, and recovery facilities." },
];

export function AboutPreview() {
  return (
    <section className="py-24 lg:py-32">
      <Container className="grid grid-cols-1 items-center gap-16 lg:grid-cols-2">
        <Reveal>
          <div className="relative">
            <div className="relative overflow-hidden rounded-3xl">
              <img
                src={unsplash(IMG.pullupDramaticBW, 1200)}
                alt="Athlete training with focus inside APEX Club"
                className="h-[520px] w-full object-cover"
              />
            </div>
            <motion.div
              initial={{ opacity: 0, scale: 0.9, y: 20 }}
              whileInView={{ opacity: 1, scale: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ delay: 0.3, duration: 0.6 }}
              className="glass absolute -bottom-8 -right-4 hidden max-w-[240px] rounded-2xl p-5 sm:right-6 sm:block"
            >
              <p className="font-display text-3xl text-gradient-gold">15+</p>
              <p className="text-xs uppercase tracking-widest text-white/60">Years building champions</p>
            </motion.div>
          </div>
        </Reveal>

        <div>
          <Badge className="mb-5">About APEX</Badge>
          <h2 className="mb-6 font-display text-4xl leading-[1.05] text-white sm:text-5xl">
            More Than A Gym — <span className="text-gradient-gold">A Performance Ecosystem</span>
          </h2>
          <p className="mb-8 text-base leading-relaxed text-white/60">
            For over fifteen years, APEX Performance Club has been the training ground for
            athletes, bodybuilders, and everyday members who demand more from their gym.
            Every rack, every trainer, and every square foot is engineered for one purpose:
            your transformation.
          </p>

          <div className="mb-9 flex flex-col gap-5">
            {points.map((point, i) => (
              <Reveal key={point.title} delay={i * 0.1}>
                <div className="flex items-start gap-4">
                  <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gold-400/10 text-gold-300">
                    <point.icon size={20} />
                  </span>
                  <div>
                    <h3 className="text-sm font-semibold uppercase tracking-wide text-white">{point.title}</h3>
                    <p className="text-sm text-white/50">{point.text}</p>
                  </div>
                </div>
              </Reveal>
            ))}
          </div>

          <Button to="/about" variant="outline" icon={<ArrowRight size={16} />}>
            Our Story
          </Button>
        </div>
      </Container>
    </section>
  );
}
