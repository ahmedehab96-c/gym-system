import { ArrowRight } from "lucide-react";
import { motion } from "framer-motion";
import { galleryImages } from "../../data/gallery";
import { Container } from "../ui/Container";
import { SectionHeader } from "../ui/SectionHeader";
import { Button } from "../ui/Button";

export function GalleryPreview() {
  const items = galleryImages.slice(0, 6);

  return (
    <section className="py-24 lg:py-32">
      <Container>
        <SectionHeader eyebrow="Inside APEX" title="The Gallery" className="mb-14" />
        <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
          {items.map((img, i) => (
            <motion.div
              key={img.id}
              initial={{ opacity: 0, scale: 0.94 }}
              whileInView={{ opacity: 1, scale: 1 }}
              viewport={{ once: true, margin: "-60px" }}
              transition={{ duration: 0.5, delay: (i % 6) * 0.07 }}
              className="group relative aspect-square overflow-hidden rounded-2xl"
            >
              <img
                src={img.src}
                alt={img.alt}
                loading="lazy"
                className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-110"
              />
              <div className="absolute inset-0 flex items-end bg-gradient-to-t from-ink/80 via-ink/0 to-ink/0 p-4 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                <span className="text-xs font-semibold uppercase tracking-widest text-gold-300">{img.category}</span>
              </div>
            </motion.div>
          ))}
        </div>
        <div className="mt-12 flex justify-center">
          <Button to="/gallery" variant="outline" icon={<ArrowRight size={16} />}>
            View Full Gallery
          </Button>
        </div>
      </Container>
    </section>
  );
}
