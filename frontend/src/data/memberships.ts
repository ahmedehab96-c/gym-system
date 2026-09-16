import type { MembershipPlan } from "../types";

export const membershipPlans: MembershipPlan[] = [
  {
    id: "basic",
    name: "Basic",
    tagline: "For getting started",
    monthlyPrice: 39,
    yearlyPrice: 390,
    features: ["Full gym access", "Locker access", "Basic support", "Access during standard hours"],
  },
  {
    id: "pro",
    name: "Pro",
    tagline: "For serious progress",
    monthlyPrice: 79,
    yearlyPrice: 790,
    features: [
      "Full gym access",
      "Unlimited group classes",
      "Quarterly fitness assessment",
      "15% off personal training",
      "Priority equipment booking",
    ],
    popular: true,
  },
  {
    id: "elite",
    name: "Elite",
    tagline: "For total transformation",
    monthlyPrice: 149,
    yearlyPrice: 1490,
    features: [
      "24/7 unlimited access",
      "Dedicated personal trainer",
      "Custom nutrition plan",
      "Recovery zone access",
      "Priority concierge support",
    ],
  },
];
