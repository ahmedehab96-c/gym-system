import { useMemo, useState } from "react";
import { motion } from "framer-motion";
import { Expand, SearchX } from "lucide-react";
import { PageHero } from "../components/layout/PageHero";
import { Container } from "../components/ui/Container";
import { Lightbox } from "../components/ui/Lightbox";
import { galleryCategories, galleryImages } from "../data/gallery";
import { IMG, unsplash } from "../data/images";
import { cn } from "../utils/cn";

export default function Gallery() {
  const [category, setCategory] = useState<(typeof galleryCategories)[number]>("All");
  const [activeIndex, setActiveIndex] = useState<number | null>(null);

  const filtered = useMemo(
    () => (category === "All" ? galleryImages : galleryImages.filter((g) => g.category === category)),
    [category],
  );

  return (
    <>
      <PageHero
        eyebrow="Gallery"
        title="A Look Inside APEX"
        description="From the strength floor to fight night classes — explore the club through our members' lens."
        image={unsplash(IMG.squatRackBrightStudio, 1800)}
      />

      <section className="py-24 lg:py-32">
        <Container>
          <div className="mb-12 flex flex-wrap justify-center gap-3">
            {galleryCategories.map((cat) => (
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

          {filtered.length > 0 ? (
            <div className="grid auto-rows-[220px] grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
              {filtered.map((img, i) => (
                <motion.button
                  key={img.id}
                  layout
                  initial={{ opacity: 0, scale: 0.94 }}
                  animate={{ opacity: 1, scale: 1 }}
                  transition={{ duration: 0.4, delay: (i % 8) * 0.05 }}
                  onClick={() => setActiveIndex(i)}
                  className={cn(
                    "group relative overflow-hidden rounded-2xl text-left",
                    img.span === "row-span-2" && "row-span-2",
                    img.span === "col-span-2" && "col-span-2",
                  )}
                >
                  <img
                    src={img.src}
                    alt={img.alt}
                    loading="lazy"
                    className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
                  />
                  <div className="absolute inset-0 flex items-center justify-center bg-black/0 transition-colors duration-300 group-hover:bg-black/50">
                    <Expand className="scale-75 text-white opacity-0 transition-all duration-300 group-hover:scale-100 group-hover:opacity-100" size={22} />
                  </div>
                </motion.button>
              ))}
            </div>
          ) : (
            <div className="flex flex-col items-center gap-4 py-20 text-white/55">
              <SearchX size={36} />
              <p>No images found in this category.</p>
            </div>
          )}
        </Container>
      </section>

      <Lightbox images={filtered} index={activeIndex} onClose={() => setActiveIndex(null)} onNavigate={setActiveIndex} />
    </>
  );
}
