import type { GalleryImage } from "../types";
import { IMG, unsplash } from "./images";

export const galleryImages: GalleryImage[] = [
  { id: "g1", src: unsplash(IMG.gymInteriorBrightCardio), alt: "Premium gym interior with cardio and strength equipment", category: "Interior", span: "row-span-2" },
  { id: "g2", src: unsplash(IMG.deadliftFeetCloseup), alt: "Athlete performing a barbell deadlift", category: "Training" },
  { id: "g3", src: unsplash(IMG.pullupBackMuscular), alt: "Athlete performing weighted pull-ups", category: "Athletes" },
  { id: "g4", src: unsplash(IMG.weightPlatesTopView), alt: "Colorful Olympic weight plates", category: "Equipment" },
  { id: "g5", src: unsplash(IMG.trainerJordan, 1200), alt: "Trainer coaching outdoors", category: "Trainers" },
  { id: "g6", src: unsplash(IMG.womenDumbbellGroupClass), alt: "Group fitness class in session", category: "Classes", span: "col-span-2" },
  { id: "g7", src: unsplash(IMG.dumbbellReachHand), alt: "Reaching for dumbbells on the rack", category: "Equipment" },
  { id: "g8", src: unsplash(IMG.chalkDeadliftGarage), alt: "Athlete chalking up before a heavy lift", category: "Athletes" },
  { id: "g9", src: unsplash(IMG.dumbbellRackMirror), alt: "Dumbbell rack lined up in the strength area", category: "Interior", span: "row-span-2" },
  { id: "g10", src: unsplash(IMG.groupPlankClassPink), alt: "Group class holding a plank position", category: "Classes" },
  { id: "g11", src: unsplash(IMG.trainerSofia, 1200), alt: "Trainer portrait with battle rope", category: "Trainers" },
  { id: "g12", src: unsplash(IMG.dumbbellRowsBW), alt: "Rows of dumbbells in the free weights area", category: "Equipment" },
  { id: "g13", src: unsplash(IMG.rackMoodyPlates), alt: "Moody shot of weight plates through a power rack", category: "Interior" },
  { id: "g14", src: unsplash(IMG.battleRopesUrban), alt: "Athlete training with battle ropes", category: "Training", span: "col-span-2" },
  { id: "g15", src: unsplash(IMG.groupClassChatMats), alt: "Members chatting after a group class", category: "Classes" },
  { id: "g16", src: unsplash(IMG.studioJumpWhiteBg), alt: "Athlete mid-jump in a dynamic pose", category: "Athletes" },
];

export const galleryCategories = ["All", "Interior", "Equipment", "Training", "Athletes", "Trainers", "Classes"] as const;
