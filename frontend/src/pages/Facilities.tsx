import { PageHero } from "../components/layout/PageHero";
import { Container } from "../components/ui/Container";
import { FacilityCard } from "../components/cards/FacilityCard";
import { CTASection } from "../components/sections/CTASection";
import { facilities } from "../data/facilities";
import { IMG, unsplash } from "../data/images";

export default function Facilities() {
  return (
    <>
      <PageHero
        eyebrow="Facilities"
        title="Spaces Engineered To Perform"
        description="Eight dedicated zones, each designed around a specific training discipline — nothing generic, nothing wasted."
        image={unsplash(IMG.gymInteriorBrightCardio, 1800)}
      />

      <section className="py-24 lg:py-32">
        <Container>
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {facilities.map((facility, i) => (
              <FacilityCard key={facility.id} facility={facility} index={i} />
            ))}
          </div>
        </Container>
      </section>

      <CTASection />
    </>
  );
}
