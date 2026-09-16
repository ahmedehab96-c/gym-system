import type { Equipment } from "../types";
import { IMG, unsplash } from "./images";

export const equipment: Equipment[] = [
  { id: "eq-1", name: "Olympic Bench Press", category: "Chest", description: "Heavy-gauge steel bench with adjustable racking for controlled pressing movements.", image: unsplash(IMG.benchPressSpotter) },
  { id: "eq-2", name: "Incline Press Station", category: "Chest", description: "Fixed-angle incline press built to isolate the upper chest under load.", image: unsplash(IMG.rackMoodyPlates) },
  { id: "eq-3", name: "Pec Deck Fly Machine", category: "Chest", description: "Cam-driven fly machine for a deep chest stretch and peak contraction.", image: unsplash(IMG.barbellGripFloor) },
  { id: "eq-4", name: "Lat Pulldown Rig", category: "Back", description: "Dual-pulley pulldown station with swappable grips for total back development.", image: unsplash(IMG.cableRowWoman) },
  { id: "eq-5", name: "Seated Cable Row", category: "Back", description: "Low-pulley rowing station engineered for strict, controlled back tension.", image: unsplash(IMG.pullupDramaticBW) },
  { id: "eq-6", name: "T-Bar Row Platform", category: "Back", description: "Landmine-style row platform for heavy, thickness-building back pulls.", image: unsplash(IMG.barbellGroupClassBW) },
  { id: "eq-7", name: "Smith Machine", category: "Shoulders", description: "Guided barbell system for safe, high-load pressing and squatting.", image: unsplash(IMG.squatRackBrightStudio) },
  { id: "eq-8", name: "Lateral Raise Machine", category: "Shoulders", description: "Isolation machine that carves capped delts with a fixed, safe arc.", image: unsplash(IMG.silhouetteDeadlift) },
  { id: "eq-9", name: "Overhead Press Rack", category: "Shoulders", description: "Dedicated rack for strict and push-press overhead pressing.", image: unsplash(IMG.overheadPressWoman) },
  { id: "eq-10", name: "Leg Press 45°", category: "Legs", description: "Angled sled press loaded for maximal quad and glute overload.", image: unsplash(IMG.legPressMachineDark) },
  { id: "eq-11", name: "Squat Rack", category: "Legs", description: "Competition-grade power rack with safety arms for heavy back squats.", image: unsplash(IMG.dumbbellRackMirror) },
  { id: "eq-12", name: "Leg Extension / Curl", category: "Legs", description: "Dual-function machine isolating quads and hamstrings independently.", image: unsplash(IMG.legPressManBlue) },
  { id: "eq-13", name: "Preacher Curl Bench", category: "Arms", description: "Angled pad that locks the elbow for strict, cheat-free bicep curls.", image: unsplash(IMG.legPressCloseupBlue) },
  { id: "eq-14", name: "Tricep Cable Station", category: "Arms", description: "Overhead and pushdown cable rig for complete triceps isolation.", image: unsplash(IMG.bandGroupClass) },
  { id: "eq-15", name: "EZ Curl Bars", category: "Arms", description: "Ergonomic curved bars that reduce wrist strain on curls and extensions.", image: unsplash(IMG.deadliftGripCloseup) },
  { id: "eq-16", name: "Treadmill Pro Series", category: "Cardio", description: "Studio-grade treadmill with incline simulation and shock-absorbing deck.", image: unsplash(IMG.runnerLegsRoad) },
  { id: "eq-17", name: "Assault Air Bike", category: "Cardio", description: "Fan-resisted bike built for brutal, all-out conditioning intervals.", image: unsplash(IMG.assaultBikeCloseup) },
  { id: "eq-18", name: "Rowing Ergometer", category: "Cardio", description: "Full-body rowing machine for engine-building, low-impact cardio.", image: unsplash(IMG.runningStepsFeet) },
  { id: "eq-19", name: "Battle Ropes", category: "Functional", description: "Heavy-gauge ropes for explosive power and conditioning circuits.", image: unsplash(IMG.battleRopesUrban) },
  { id: "eq-20", name: "Weighted Sled", category: "Functional", description: "Push/pull sled loaded for raw strength and athletic conditioning.", image: unsplash(IMG.crossfitWallBallGroup) },
  { id: "eq-21", name: "Kettlebell Wall", category: "Functional", description: "Full range of kettlebells for swings, cleans, and dynamic strength work.", image: unsplash(IMG.kettlebellSwingAthlete) },
];

export const equipmentCategories = ["All", "Chest", "Back", "Shoulders", "Legs", "Arms", "Cardio", "Functional"] as const;
