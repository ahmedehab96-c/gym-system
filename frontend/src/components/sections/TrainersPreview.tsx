import { ArrowRight } from "lucide-react";
import { trainers } from "../../data/trainers";
import { Container } from "../ui/Container";
import { SectionHeader } from "../ui/SectionHeader";
import { Button } from "../ui/Button";
import { TrainerCard } from "../cards/TrainerCard";

export function TrainersPreview() {
  return (
    <section className="bg-charcoal py-24 lg:py-32">
      <Container>
        <SectionHeader
          eyebrow="Elite Coaching Staff"
          title="Train With The Best"
          description="Certified experts in strength, physique, and performance — invested in your progress."
          className="mb-14"
        />
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {trainers.slice(0, 3).map((trainer, i) => (
            <TrainerCard key={trainer.id} trainer={trainer} index={i} />
          ))}
        </div>
        <div className="mt-12 flex justify-center">
          <Button to="/trainers" variant="outline" icon={<ArrowRight size={16} />}>
            Meet The Full Team
          </Button>
        </div>
      </Container>
    </section>
  );
}
