import { motion } from "framer-motion";
import { Container } from "../ui/Container";
import { Badge } from "../ui/Badge";

interface PageHeroProps {
  eyebrow: string;
  title: string;
  description: string;
  image: string;
}

export function PageHero({ eyebrow, title, description, image }: PageHeroProps) {
  return (
    <section className="relative flex h-[62vh] min-h-[440px] items-end overflow-hidden pb-16 pt-32">
      <motion.img
        src={image}
        alt=""
        initial={{ scale: 1.15 }}
        animate={{ scale: 1 }}
        transition={{ duration: 1.6, ease: [0.22, 1, 0.36, 1] }}
        className="absolute inset-0 h-full w-full object-cover"
      />
      <div className="absolute inset-0 bg-gradient-to-t from-void via-void/70 to-void/40" />
      <div className="absolute inset-0 bg-gradient-to-r from-void/80 via-transparent to-transparent" />

      <Container className="relative z-10">
        <motion.div initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.6, delay: 0.2 }}>
          <Badge className="mb-5">{eyebrow}</Badge>
          <h1 className="mb-4 max-w-3xl font-display text-5xl leading-[1.02] text-white sm:text-6xl lg:text-7xl">
            {title}
          </h1>
          <p className="max-w-xl text-base text-white/60 sm:text-lg">{description}</p>
        </motion.div>
      </Container>
    </section>
  );
}
