import type { LucideIcon } from "lucide-react";
import { LayoutDashboard, Building2, ListChecks, CreditCard, Receipt, ShieldCheck, ScrollText, MessageCircle } from "lucide-react";

export interface PlatformNavItem {
  label: string;
  path: string;
  icon: LucideIcon;
}

export interface PlatformNavSection {
  title: string;
  items: PlatformNavItem[];
}

export const platformNavSections: PlatformNavSection[] = [
  { title: "Overview", items: [{ label: "Dashboard", path: "/platform/dashboard", icon: LayoutDashboard }] },
  {
    title: "Gyms",
    items: [{ label: "Gyms", path: "/platform/gyms", icon: Building2 }],
  },
  {
    title: "Billing",
    items: [
      { label: "SaaS Plans", path: "/platform/plans", icon: ListChecks },
      { label: "Subscriptions", path: "/platform/subscriptions", icon: CreditCard },
      { label: "Billing History", path: "/platform/billing", icon: Receipt },
    ],
  },
  {
    title: "Platform",
    items: [
      { label: "Platform Users", path: "/platform/users", icon: ShieldCheck },
      { label: "Audit Logs", path: "/platform/audit-logs", icon: ScrollText },
      { label: "Communication", path: "/platform/communication", icon: MessageCircle },
    ],
  },
];
