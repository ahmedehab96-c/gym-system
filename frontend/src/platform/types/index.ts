// Core entity types for the Super Admin Platform Dashboard (Phase 21).
// These mirror the Laravel API responses under /platform/*, entirely
// separate from ../../admin/types, which model a single gym's own data.

export type GymStatus = "Active" | "Suspended" | "Trial" | "Inactive";

export interface GymOwner {
  id: string;
  name: string;
  email: string;
  phone: string | null;
}

export interface GymSubscriptionSummary {
  status: string;
  billingCycle: string;
  planName: string | null;
  nextBillingAt: string | null;
}

export interface GymUsage {
  members: number;
  staff: number;
  trainers: number;
}

export interface Gym {
  id: string;
  name: string;
  slug: string;
  email: string | null;
  phone: string | null;
  address: string | null;
  logo: string | null;
  status: GymStatus;
  timezone: string;
  currency: string;
  createdAt: string;
  staffCount?: number;
  owner?: GymOwner | null;
  subscription?: GymSubscriptionSummary | null;
  usage?: GymUsage;
  revenue?: number;
}

export interface PlatformPlanLimits {
  max_members?: number | null;
  max_staff?: number | null;
  max_trainers?: number | null;
  max_classes?: number | null;
  storage_mb?: number | null;
  ai_requests?: number | null;
}

export type PlanStatus = "Active" | "Inactive";

export interface PlatformPlan {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  monthlyPrice: number;
  yearlyPrice: number;
  trialDays: number;
  features: string[];
  limits: PlatformPlanLimits;
  status: PlanStatus;
  sortOrder: number;
}

export interface TenantRef {
  id: string;
  name: string;
  slug: string;
}

export type SubscriptionStatus = "Trial" | "Active" | "Past Due" | "Cancelled" | "Expired";
export type BillingCycle = "Monthly" | "Yearly";

export interface PlatformSubscription {
  id: string;
  status: SubscriptionStatus;
  billingCycle: BillingCycle;
  price: number;
  plan: PlatformPlan | null;
  tenant: TenantRef | null;
  trialStartsAt: string | null;
  trialEndsAt: string | null;
  startedAt: string | null;
  nextBillingAt: string | null;
  cancelledAt: string | null;
  expiresAt: string | null;
}

export type InvoiceStatus = "Pending" | "Paid" | "Failed" | "Refunded" | "Cancelled";

export interface PlatformInvoice {
  id: string;
  invoiceNumber: string;
  planName: string | null;
  tenant: TenantRef | null;
  amount: number;
  currency: string;
  billingPeriodStart: string;
  billingPeriodEnd: string;
  status: InvoiceStatus;
  issueDate: string;
  dueDate: string;
  paidDate: string | null;
}

export interface PlatformUser {
  id: string;
  name: string;
  photo: string | null;
  email: string;
  phone: string | null;
  status: "Active" | "Inactive";
  role: string;
  lastLoginAt: string | null;
  createdAt: string;
}

export interface AuditLogEntry {
  id: string;
  actorId: string | null;
  actorName: string;
  action: string;
  entityType: string | null;
  entityId: string | null;
  tenant: { id: string; name: string } | null;
  description: string;
  createdAt: string;
}

export interface PlatformSummary {
  totalGyms: number;
  activeGyms: number;
  trialGyms: number;
  suspendedGyms: number;
  activeSubscriptions: number;
  expiredSubscriptions: number;
  mrr: number;
  yearlyRevenue: number;
}

export interface MonthlyPoint {
  month: string;
  count: number;
}

export interface RevenuePoint {
  month: string;
  revenue: number;
}

export interface PlanDistributionSlice {
  plan: string;
  count: number;
}

export interface PlatformCharts {
  tenantGrowth: MonthlyPoint[];
  subscriptionGrowth: MonthlyPoint[];
  revenueTrend: RevenuePoint[];
  planDistribution: PlanDistributionSlice[];
}

export interface PlatformActivity {
  recentGyms: Gym[];
  recentSubscriptions: PlatformSubscription[];
  recentBilling: PlatformInvoice[];
  recentActivity: AuditLogEntry[];
}
