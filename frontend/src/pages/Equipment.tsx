import { useMemo, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import { PageHero } from "../components/layout/PageHero";
import { Container } from "../components/ui/Container";
import { EquipmentCard } from "../components/cards/EquipmentCard";
import { CTASection } from "../components/sections/CTASection";
import { equipment, equipmentCategories } from "../data/equipment";
import { IMG, unsplash } from "../data/images";
import { cn } from "../utils/cn";
import { SearchX } from "lucide-react";

export default function Equipment() {
  const [category, setCategory] = useState<(typeof equipmentCategories)[number]>("All");

  const filtered = useMemo(
    () => (category === "All" ? equipment : equipment.filter((e) => e.category === category)),
    [category],
  );

  return (
    <>
      <PageHero
        eyebrow="Equipment Catalog"
        title="Premium Iron. Zero Compromise."
        description="Explore our full arsenal of strength, cardio, and functional training equipment, organized by muscle group."
        image={unsplash(IMG.dumbbellRowsBW, 1800)}
      />

      <section className="py-24 lg:py-32">
        <Container>
          <div className="mb-12 flex flex-wrap justify-center gap-3">
            {equipmentCategories.map((cat) => (
              <button
                key={cat}
                onClick={() => setCategory(cat)}
                className={cn(
                  "rounded-full border px-5 py-2.5 text-xs font-semibold uppercase tracking-wider transition-all",
                  category === cat
                    ? "border-gold-400 bg-gold-400 text-ink"
                    : "border-white/15 text-white/65 hover:border-gold-400/50 hover:text-white",
                )}
              >
                {cat}
              </button>
            ))}
          </div>

          <AnimatePresence mode="wait">
            {filtered.length > 0 ? (
              <motion.div
                key={category}
                layout
                className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
              >
                {filtered.map((item, i) => (
                  <EquipmentCard key={item.id} item={item} index={i} />
                ))}
              </motion.div>
            ) : (
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                className="flex flex-col items-center gap-4 py-20 text-white/55"
              >
                <SearchX size={36} />
                <p>No equipment found in this category.</p>
              </motion.div>
            )}
          </AnimatePresence>
        </Container>
      </section>

      <CTASection />
    </>
  );
}
