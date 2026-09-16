import type { Stat } from "../../types";
import { Container } from "../ui/Container";
import { Counter } from "../ui/Counter";
import { Reveal } from "../ui/Reveal";

export function StatsBar({ stats }: { stats: Stat[] }) {
  return (
    <section className="relative border-y border-white/10 bg-charcoal py-14">
      <Container className="grid grid-cols-2 gap-8 lg:grid-cols-4">
        {stats.map((stat, i) => (
          <Reveal key={stat.id} delay={i * 0.08} className="text-center">
            <div className="font-display text-4xl text-gradient-gold sm:text-5xl">
              <Counter value={stat.value} suffix={stat.suffix} />
            </div>
            <p className="mt-2 text-xs font-semibold uppercase tracking-[0.2em] text-white/50">{stat.label}</p>
          </Reveal>
        ))}
      </Container>
    </section>
  );
}
