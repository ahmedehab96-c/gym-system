import { testimonials } from "../../data/testimonials";
import { Container } from "../ui/Container";
import { SectionHeader } from "../ui/SectionHeader";
import { TestimonialCard } from "../cards/TestimonialCard";

export function TestimonialsSection() {
  return (
    <section className="bg-charcoal py-24 lg:py-32">
      <Container>
        <SectionHeader
          eyebrow="Member Stories"
          title="Real Results, Real Members"
          description="Thousands of transformations. Here's what a few of our members have to say."
          className="mb-14"
        />
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
          {testimonials.slice(0, 3).map((testimonial, i) => (
            <TestimonialCard key={testimonial.id} testimonial={testimonial} index={i} />
          ))}
        </div>
      </Container>
    </section>
  );
}
