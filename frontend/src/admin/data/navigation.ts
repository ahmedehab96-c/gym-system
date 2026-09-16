import type { LucideIcon } from "lucide-react";
import {
  LayoutDashboard, Users, IdCard, ListChecks, CalendarCheck2, Dumbbell,
  ClipboardList, UserRound, CalendarRange, Wrench, Building2, Hammer,
  CreditCard, FileText, Receipt, TrendingUp, Bell, Megaphone, BarChart3,
  PieChart, UsersRound, ShieldCheck, Settings, Sparkles, MessageSquareText, Wallet, ScanLine,
} from "lucide-react";

export interface NavItem {
  label: string;
  path: string;
  icon: LucideIcon;
  /** Permission module (see backend `permission:module` route middleware) gating this page. Omit for pages every authenticated staff member can view. */
  module?: string;
}

export interface NavSection {
  title: string;
  items: NavItem[];
}

export const navSections: NavSection[] = [
  { title: "Main", items: [{ label: "Dashboard", path: "/admin/dashboard", icon: LayoutDashboard }] },
  {
    title: "Members",
    items: [
      { label: "Members", path: "/admin/members", icon: Users, module: "Members" },
      { label: "Memberships", path: "/admin/memberships", icon: IdCard, module: "Memberships" },
      { label: "Membership Plans", path: "/admin/membership-plans", icon: ListChecks, module: "Memberships" },
      { label: "Attendance", path: "/admin/attendance", icon: CalendarCheck2, module: "Attendance" },
      { label: "Check-in Scanner", path: "/admin/attendance/scanner", icon: ScanLine, module: "Attendance" },
    ],
  },
  {
    title: "Training",
    items: [
      { label: "Trainers", path: "/admin/trainers", icon: Dumbbell, module: "Trainers" },
      { label: "Training Programs", path: "/admin/programs", icon: ClipboardList, module: "Trainers" },
      { label: "Personal Training", path: "/admin/personal-training", icon: UserRound },
      { label: "Classes", path: "/admin/classes", icon: CalendarRange, module: "Classes" },
      { label: "Class Schedule", path: "/admin/schedule", icon: CalendarRange, module: "Classes" },
    ],
  },
  {
    title: "Gym",
    items: [
      { label: "Equipment", path: "/admin/equipment", icon: Wrench, module: "Equipment" },
      { label: "Facilities", path: "/admin/facilities", icon: Building2 },
      { label: "Maintenance", path: "/admin/maintenance", icon: Hammer, module: "Maintenance" },
    ],
  },
  {
    title: "Finance",
    items: [
      { label: "Payments", path: "/admin/payments", icon: CreditCard, module: "Payments" },
      { label: "Invoices", path: "/admin/invoices", icon: FileText, module: "Invoices" },
      { label: "Expenses", path: "/admin/expenses", icon: Receipt, module: "Expenses" },
      { label: "Revenue", path: "/admin/revenue", icon: TrendingUp, module: "Reports" },
      { label: "Billing", path: "/admin/billing", icon: Wallet },
    ],
  },
  {
    title: "Communication",
    items: [
      { label: "Notifications", path: "/admin/notifications", icon: Bell },
      { label: "Announcements", path: "/admin/announcements", icon: Megaphone, module: "Announcements" },
    ],
  },
  {
    title: "Reports",
    items: [
      { label: "Reports", path: "/admin/reports", icon: BarChart3, module: "Reports" },
      { label: "Analytics", path: "/admin/analytics", icon: PieChart, module: "Reports" },
    ],
  },
  {
    title: "AI",
    items: [
      { label: "AI Assistant", path: "/admin/ai/assistant", icon: MessageSquareText, module: "AI" },
      { label: "AI Insights", path: "/admin/ai/insights", icon: Sparkles, module: "AI" },
    ],
  },
  {
    title: "Management",
    items: [
      { label: "Staff", path: "/admin/staff", icon: UsersRound, module: "Staff" },
      { label: "Roles & Permissions", path: "/admin/roles", icon: ShieldCheck, module: "Settings" },
      { label: "Settings", path: "/admin/settings", icon: Settings, module: "Settings" },
    ],
  },
];
