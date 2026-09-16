import { ArrowRight } from "lucide-react";
import { equipment } from "../../data/equipment";
import { Container } from "../ui/Container";
import { SectionHeader } from "../ui/SectionHeader";
import { Button } from "../ui/Button";
import { EquipmentCard } from "../cards/EquipmentCard";

export function EquipmentPreview() {
  const featured = equipment.filter((e) => ["eq-1", "eq-4", "eq-10", "eq-16", "eq-19", "eq-11"].includes(e.id));

  return (
    <section className="bg-charcoal py-24 lg:py-32">
      <Container>
        <SectionHeader
          eyebrow="Equipment Showcase"
          title="Precision Machines. Serious Iron."
          description="A curated arsenal of premium strength and conditioning equipment for every goal."
          className="mb-14"
        />
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {featured.map((item, i) => (
            <EquipmentCard key={item.id} item={item} index={i} />
          ))}
        </div>
        <div className="mt-12 flex justify-center">
          <Button to="/equipment" variant="outline" icon={<ArrowRight size={16} />}>
            Browse Full Catalog
          </Button>
        </div>
      </Container>
    </section>
  );
}
