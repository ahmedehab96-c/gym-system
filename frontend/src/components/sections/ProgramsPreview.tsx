import { ArrowRight } from "lucide-react";
import { programs } from "../../data/programs";
import { Container } from "../ui/Container";
import { SectionHeader } from "../ui/SectionHeader";
import { Button } from "../ui/Button";
import { ProgramCard } from "../cards/ProgramCard";

export function ProgramsPreview() {
  return (
    <section className="py-24 lg:py-32">
      <Container>
        <SectionHeader
          eyebrow="Training Programs"
          title="Programs Built Around Your Goal"
          description="Structured, coach-designed programs for every stage of your fitness journey."
          className="mb-14"
        />
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {programs.slice(0, 3).map((program, i) => (
            <ProgramCard key={program.id} program={program} index={i} />
          ))}
        </div>
        <div className="mt-12 flex justify-center">
          <Button to="/programs" variant="outline" icon={<ArrowRight size={16} />}>
            View All Programs
          </Button>
        </div>
      </Container>
    </section>
  );
}
