import { useState } from "react";
import { PageHero } from "../components/layout/PageHero";
import { Container } from "../components/ui/Container";
import { TrainerCard } from "../components/cards/TrainerCard";
import { Modal } from "../components/ui/Modal";
import { CTASection } from "../components/sections/CTASection";
import { trainers } from "../data/trainers";
import { IMG, unsplash } from "../data/images";
import type { Trainer } from "../types";
import { InstagramIcon, LinkedinIcon, TwitterIcon } from "../components/ui/SocialIcons";

export default function Trainers() {
  const [selected, setSelected] = useState<Trainer | null>(null);

  return (
    <>
      <PageHero
        eyebrow="Our Coaches"
        title="Meet The APEX Team"
        description="Certified, experienced, and genuinely invested in your progress — meet the coaches behind every transformation."
        image={unsplash(IMG.pushupDumbbellsMan, 1800)}
      />

      <section className="py-24 lg:py-32">
        <Container>
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {trainers.map((trainer, i) => (
              <TrainerCard key={trainer.id} trainer={trainer} index={i} onSelect={setSelected} />
            ))}
          </div>
        </Container>
      </section>

      <Modal open={!!selected} onClose={() => setSelected(null)}>
        {selected && (
          <div className="grid grid-cols-1 sm:grid-cols-2">
            <img src={selected.image} alt={selected.name} className="h-64 w-full object-cover sm:h-full" />
            <div className="p-8">
              <h3 className="font-display text-3xl tracking-wide text-white">{selected.name}</h3>
              <p className="mb-4 text-sm font-semibold uppercase tracking-widest text-gold-300">{selected.position}</p>
              <p className="mb-5 text-sm leading-relaxed text-white/60">{selected.bio}</p>

              <p className="mb-2 text-xs font-semibold uppercase tracking-widest text-white/55">Specialty</p>
              <p className="mb-5 text-sm text-white/70">{selected.specialty} · {selected.experience} experience</p>

              <p className="mb-2 text-xs font-semibold uppercase tracking-widest text-white/55">Certifications</p>
              <ul className="mb-6 flex flex-wrap gap-2">
                {selected.certifications.map((cert) => (
                  <li key={cert} className="rounded-full border border-white/15 px-3 py-1 text-xs text-white/65">
                    {cert}
                  </li>
                ))}
              </ul>

              <div className="flex gap-3">
                {selected.social.instagram && (
                  <a href={selected.social.instagram} aria-label="Instagram" className="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-white/70 hover:border-gold-400 hover:text-gold-300">
                    <InstagramIcon size={15} />
                  </a>
                )}
                {selected.social.twitter && (
                  <a href={selected.social.twitter} aria-label="Twitter" className="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-white/70 hover:border-gold-400 hover:text-gold-300">
                    <TwitterIcon size={15} />
                  </a>
                )}
                {selected.social.linkedin && (
                  <a href={selected.social.linkedin} aria-label="LinkedIn" className="flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-white/70 hover:border-gold-400 hover:text-gold-300">
                    <LinkedinIcon size={15} />
                  </a>
                )}
              </div>
            </div>
          </div>
        )}
      </Modal>

      <CTASection />
    </>
  );
}
