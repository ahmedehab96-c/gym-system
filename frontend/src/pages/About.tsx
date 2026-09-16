import { Award, Compass, Flame, HeartHandshake } from "lucide-react";
import { PageHero } from "../components/layout/PageHero";
import { Container } from "../components/ui/Container";
import { SectionHeader } from "../components/ui/SectionHeader";
import { Reveal, StaggerGroup, staggerItem } from "../components/ui/Reveal";
import { Counter } from "../components/ui/Counter";
import { CTASection } from "../components/sections/CTASection";
import { aboutStats } from "../data/stats";
import { IMG, unsplash } from "../data/images";
import { motion } from "framer-motion";

const values = [
  { icon: Flame, title: "Relentless Standards", text: "We never settle for average — in equipment, coaching, or results." },
  { icon: HeartHandshake, title: "Real Community", text: "A club where members push each other and celebrate every win." },
  { icon: Award, title: "Proven Expertise", text: "Every coach is certified, experienced, and genuinely invested." },
  { icon: Compass, title: "Purposeful Design", text: "Every space is engineered around performance and recovery." },
];

const timeline = [
  { year: "2011", text: "APEX opens its first strength-only facility downtown." },
  { year: "2015", text: "Personal training division launches with 6 founding coaches." },
  { year: "2019", text: "Recovery zone and functional training rig added." },
  { year: "2023", text: "APEX crosses 4,000 active members across 3 locations." },
  { year: "2026", text: "Named the region's #1 premium fitness club, three years running." },
];

export default function About() {
  return (
    <>
      <PageHero
        eyebrow="Our Story"
        title="Built By Lifters, For Lifters"
        description="APEX Performance Club was founded on one belief: training deserves better spaces, better coaching, and better standards."
        image={unsplash(IMG.moodyPortrait, 1800)}
      />

      <section className="py-24 lg:py-32">
        <Container className="grid grid-cols-1 items-center gap-16 lg:grid-cols-2">
          <Reveal>
            <img src={unsplash(IMG.dumbbellRackMirror, 1200)} alt="APEX gym interior" className="h-[460px] w-full rounded-3xl object-cover" />
          </Reveal>
          <div>
            <SectionHeader eyebrow="Mission & Vision" title="Redefining What A Gym Can Be" align="left" className="mb-8" />
            <p className="mb-6 text-base leading-relaxed text-white/60">
              Our mission is simple: give every member the tools, coaching, and environment to
              become their strongest self — physically and mentally. We believe fitness should
              feel premium, not intimidating.
            </p>
            <p className="text-base leading-relaxed text-white/60">
              Our vision is to be the gold standard for performance clubs — where elite athletes
              and first-time lifters train side by side, supported by the same level of excellence.
            </p>
          </div>
        </Container>
      </section>

      <section className="bg-charcoal py-24 lg:py-32">
        <Container>
          <SectionHeader eyebrow="Why Choose Us" title="Our Core Values" className="mb-14" />
          <StaggerGroup className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {values.map((value) => (
              <motion.div key={value.title} variants={staggerItem} className="glass rounded-2xl p-7 text-center">
                <span className="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gold-400/10 text-gold-300">
                  <value.icon size={22} />
                </span>
                <h3 className="mb-2 font-display text-lg tracking-wide text-white">{value.title}</h3>
                <p className="text-sm text-white/55">{value.text}</p>
              </motion.div>
            ))}
          </StaggerGroup>
        </Container>
      </section>

      <section className="py-24 lg:py-32">
        <Container className="grid grid-cols-2 gap-8 lg:grid-cols-4">
          {aboutStats.map((stat, i) => (
            <Reveal key={stat.id} delay={i * 0.08} className="text-center">
              <div className="font-display text-4xl text-gradient-gold sm:text-5xl">
                <Counter value={stat.value} suffix={stat.suffix} />
              </div>
              <p className="mt-2 text-xs font-semibold uppercase tracking-[0.2em] text-white/50">{stat.label}</p>
            </Reveal>
          ))}
        </Container>
      </section>

      <section className="bg-charcoal py-24 lg:py-32">
        <Container>
          <SectionHeader eyebrow="Our Journey" title="A Legacy Of Excellence" className="mb-16" />
          <div className="mx-auto max-w-2xl">
            {timeline.map((item, i) => (
              <Reveal key={item.year} delay={i * 0.08}>
                <div className="relative flex gap-6 pb-12 last:pb-0">
                  <div className="flex flex-col items-center">
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gold-400 text-sm font-bold text-ink">
                      {i + 1}
                    </span>
                    {i < timeline.length - 1 && <span className="mt-2 w-px flex-1 bg-white/15" />}
                  </div>
                  <div className="pb-2">
                    <p className="font-display text-xl text-gold-300">{item.year}</p>
                    <p className="mt-1 text-sm text-white/60">{item.text}</p>
                  </div>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      <CTASection />
    </>
  );
}
