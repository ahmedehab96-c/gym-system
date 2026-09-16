import { Hero } from "../components/sections/Hero";
import { StatsBar } from "../components/sections/StatsBar";
import { AboutPreview } from "../components/sections/AboutPreview";
import { FacilitiesPreview } from "../components/sections/FacilitiesPreview";
import { EquipmentPreview } from "../components/sections/EquipmentPreview";
import { ProgramsPreview } from "../components/sections/ProgramsPreview";
import { TrainersPreview } from "../components/sections/TrainersPreview";
import { MembershipPreview } from "../components/sections/MembershipPreview";
import { TestimonialsSection } from "../components/sections/TestimonialsSection";
import { GalleryPreview } from "../components/sections/GalleryPreview";
import { CTASection } from "../components/sections/CTASection";
import { heroStats } from "../data/stats";

export default function Home() {
  return (
    <>
      <Hero />
      <StatsBar stats={heroStats} />
      <AboutPreview />
      <FacilitiesPreview />
      <EquipmentPreview />
      <ProgramsPreview />
      <TrainersPreview />
      <MembershipPreview />
      <TestimonialsSection />
      <GalleryPreview />
      <CTASection />
    </>
  );
}
