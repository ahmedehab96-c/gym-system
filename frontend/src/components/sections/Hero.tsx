import { useRef } from "react";
import { motion, useScroll, useTransform } from "framer-motion";
import { ArrowRight, ChevronDown, PlayCircle } from "lucide-react";
import { Container } from "../ui/Container";
import { Button } from "../ui/Button";
import { IMG, unsplash } from "../../data/images";

const words = ["BUILD", "YOUR", "STRONGEST", "SELF"];

export function Hero() {
  const ref = useRef<HTMLDivElement>(null);
  const { scrollYProgress } = useScroll({ target: ref, offset: ["start start", "end start"] });
  const y = useTransform(scrollYProgress, [0, 1], ["0%", "35%"]);
  const opacity = useTransform(scrollYProgress, [0, 0.8], [1, 0]);

  return (
    <section ref={ref} className="relative flex h-[100svh] min-h-[640px] items-center overflow-hidden">
      <motion.div style={{ y }} className="absolute inset-0">
        <img
          src={unsplash(IMG.deadliftDramatic, 2000, 85)}
          alt="Cinematic view of a premium gym floor at dusk"
          className="h-[120%] w-full object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-t from-void via-void/70 to-void/50" />
        <div className="absolute inset-0 bg-gradient-to-r from-void/90 via-void/30 to-void/70" />
        <div className="absolute inset-0 bg-noise" />
      </motion.div>

      <Container className="relative z-10">
        <motion.div style={{ opacity }} className="max-w-3xl">
          <motion.span
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.2, duration: 0.6 }}
            className="mb-6 inline-flex items-center gap-2 rounded-full border border-gold-400/30 bg-gold-400/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.25em] text-gold-300"
          >
            Premium Performance Club
          </motion.span>

          <h1 className="font-display flex flex-wrap gap-x-4 text-5xl leading-[0.95] text-white sm:text-6xl md:text-7xl lg:text-8xl">
            {words.map((word, i) => (
              <motion.span
                key={word}
                initial={{ opacity: 0, y: 60 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.3 + i * 0.1, duration: 0.7, ease: [0.22, 1, 0.36, 1] }}
                className={word === "STRONGEST" ? "text-gradient-gold" : undefined}
              >
                {word}
              </motion.span>
            ))}
          </h1>

          <motion.p
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.8, duration: 0.6 }}
            className="mt-7 max-w-lg text-base text-white/65 sm:text-lg"
          >
            Train harder. Move stronger. Become unstoppable — inside a world-class
            facility built for lifters, athletes, and everyone chasing their best self.
          </motion.p>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.95, duration: 0.6 }}
            className="mt-10 flex flex-wrap items-center gap-4"
          >
            <Button to="/membership" size="lg" icon={<ArrowRight size={18} />}>
              Join Now
            </Button>
            <Button to="/facilities" variant="outline" size="lg" icon={<PlayCircle size={18} />}>
              Explore Gym
            </Button>
          </motion.div>
        </motion.div>
      </Container>

      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 1.4, duration: 0.8 }}
        className="absolute bottom-8 left-1/2 z-10 -translate-x-1/2"
      >
        <motion.div
          animate={{ y: [0, 10, 0] }}
          transition={{ duration: 1.8, repeat: 3, ease: "easeInOut" }}
          className="flex flex-col items-center gap-2 text-white/50"
        >
          <span className="text-[10px] font-semibold uppercase tracking-[0.3em]">Scroll</span>
          <ChevronDown size={18} />
        </motion.div>
      </motion.div>
    </section>
  );
}
