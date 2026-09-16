import { PageHero } from "../components/layout/PageHero";
import { Container } from "../components/ui/Container";
import { ProgramCard } from "../components/cards/ProgramCard";
import { CTASection } from "../components/sections/CTASection";
import { programs } from "../data/programs";
import { IMG, unsplash } from "../data/images";

export default function Programs() {
  return (
    <>
      <PageHero
        eyebrow="Training Programs"
        title="A Program For Every Goal"
        description="Structured, coach-designed training tracks — whether you're building muscle, cutting fat, or chasing your first competition."
        image={unsplash(IMG.silhouetteDeadlift, 1800)}
      />

      <section className="py-24 lg:py-32">
        <Container>
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {programs.map((program, i) => (
              <ProgramCard key={program.id} program={program} index={i} />
            ))}
          </div>
        </Container>
      </section>

      <CTASection />
    </>
  );
}
