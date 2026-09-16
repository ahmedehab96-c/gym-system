import type { Facility } from "../types";
import { IMG, unsplash } from "./images";

export const facilities: Facility[] = [
  {
    id: "strength",
    title: "Strength Area",
    description: "A full arsenal of power racks, plated barbells, and heavy-duty benches built for serious lifters.",
    image: unsplash(IMG.dumbbellRackMirror),
    icon: "Dumbbell",
  },
  {
    id: "cardio",
    title: "Cardio Zone",
    description: "Rows of premium treadmills, bikes, and rowers with skyline views and immersive sound.",
    image: unsplash(IMG.gymInteriorBrightCardio),
    icon: "HeartPulse",
  },
  {
    id: "free-weights",
    title: "Free Weights",
    description: "Dumbbells from 1kg to 80kg, kettlebells, and Olympic plates arranged for zero downtime.",
    image: unsplash(IMG.dumbbellReachHand),
    icon: "Weight",
  },
  {
    id: "functional",
    title: "Functional Training",
    description: "Turf lanes, sleds, battle ropes, and rigs designed for athletic, real-world performance.",
    image: unsplash(IMG.battleRopesUrban),
    icon: "Zap",
  },
  {
    id: "personal-training",
    title: "Personal Training",
    description: "Private coaching suites where our elite trainers build programs tailored to your goals.",
    image: unsplash(IMG.pushupDumbbellsMan),
    icon: "UserCheck",
  },
  {
    id: "group-classes",
    title: "Group Classes",
    description: "High-energy studio sessions — HIIT, spin, and strength circuits led by top-tier coaches.",
    image: unsplash(IMG.womenDumbbellGroupClass),
    icon: "Users",
  },
  {
    id: "recovery",
    title: "Recovery Zone",
    description: "Sauna, cold plunge, and stretch therapy suites to help you recover as hard as you train.",
    image: unsplash(IMG.hotStoneSpa),
    icon: "Sparkles",
  },
  {
    id: "locker-rooms",
    title: "Locker Rooms",
    description: "Spa-grade locker rooms with premium amenities, towel service, and private showers.",
    image: unsplash(IMG.lockerWooden),
    icon: "KeyRound",
  },
];
