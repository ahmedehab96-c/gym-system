export interface NavLink {
  label: string;
  path: string;
}

export interface Facility {
  id: string;
  title: string;
  description: string;
  image: string;
  icon: string;
}

export type EquipmentCategory =
  | "Chest"
  | "Back"
  | "Shoulders"
  | "Legs"
  | "Arms"
  | "Cardio"
  | "Functional";

export interface Equipment {
  id: string;
  name: string;
  category: EquipmentCategory;
  description: string;
  image: string;
}

export type ProgramDifficulty = "Beginner" | "Intermediate" | "Advanced" | "All Levels";

export interface Program {
  id: string;
  title: string;
  description: string;
  image: string;
  duration: string;
  difficulty: ProgramDifficulty;
  sessions: string;
}

export interface Trainer {
  id: string;
  name: string;
  position: string;
  specialty: string;
  experience: string;
  bio: string;
  image: string;
  certifications: string[];
  social: {
    instagram?: string;
    twitter?: string;
    linkedin?: string;
  };
}

export interface MembershipPlan {
  id: string;
  name: string;
  tagline: string;
  monthlyPrice: number;
  yearlyPrice: number;
  features: string[];
  popular?: boolean;
}

export interface Testimonial {
  id: string;
  name: string;
  role: string;
  quote: string;
  avatar: string;
  rating: number;
}

export type GalleryCategory = "Interior" | "Equipment" | "Training" | "Athletes" | "Trainers" | "Classes";

export interface GalleryImage {
  id: string;
  src: string;
  alt: string;
  category: GalleryCategory;
  span?: "row-span-2" | "col-span-2";
}

export interface Stat {
  id: string;
  label: string;
  value: number;
  suffix?: string;
}
