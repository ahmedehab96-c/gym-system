import { ArrowRight } from "lucide-react";
import { Container } from "../ui/Container";
import { Button } from "../ui/Button";
import { Reveal } from "../ui/Reveal";
import { IMG, unsplash } from "../../data/images";

export function CTASection() {
  return (
    <section className="relative overflow-hidden py-28">
      <img
        src={unsplash(IMG.pullupBackMuscular, 1800)}
        alt="Athlete training at APEX Performance Club"
        className="absolute inset-0 h-full w-full object-cover object-top"
      />
      <div className="absolute inset-0 bg-gradient-to-r from-ink via-ink/85 to-ink/50" />
      <div className="absolute inset-0 bg-gradient-to-t from-ink via-transparent to-ink/40" />

      <Container className="relative z-10">
        <Reveal className="max-w-2xl">
          <h2 className="mb-5 font-display text-4xl leading-[1.05] text-white sm:text-5xl lg:text-6xl">
            Your Strongest Self Is <span className="text-gradient-gold">One Decision Away</span>
          </h2>
          <p className="mb-9 text-base text-white/65 sm:text-lg">
            Join APEX Performance Club today and train inside a facility built for champions.
            No contracts. No excuses. Just results.
          </p>
          <div className="flex flex-wrap gap-4">
            <Button to="/membership" size="lg" icon={<ArrowRight size={18} />}>
              Join Now
            </Button>
            <Button to="/contact" variant="outline" size="lg">
              Book A Tour
            </Button>
          </div>
        </Reveal>
      </Container>
    </section>
  );
}
