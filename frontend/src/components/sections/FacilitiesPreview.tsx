import { ArrowRight } from "lucide-react";
import { facilities } from "../../data/facilities";
import { Container } from "../ui/Container";
import { SectionHeader } from "../ui/SectionHeader";
import { Button } from "../ui/Button";
import { FacilityCard } from "../cards/FacilityCard";

export function FacilitiesPreview() {
  return (
    <section className="py-24 lg:py-32">
      <Container>
        <SectionHeader
          eyebrow="World-Class Facilities"
          title="Every Zone Built With Purpose"
          description="From heavy iron to recovery suites, every space is designed to elevate performance."
          className="mb-14"
        />
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {facilities.slice(0, 4).map((facility, i) => (
            <FacilityCard key={facility.id} facility={facility} index={i} />
          ))}
        </div>
        <div className="mt-12 flex justify-center">
          <Button to="/facilities" variant="outline" icon={<ArrowRight size={16} />}>
            View All Facilities
          </Button>
        </div>
      </Container>
    </section>
  );
}
