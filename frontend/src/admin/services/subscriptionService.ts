import { apiClient, type ApiItemResponse, type ApiListResponse, type QueryParams } from "./apiClient";

export interface SubscriptionPlan {
  id: string;
  name: string;
  slug: string;
  description: string | null;
  monthlyPrice: number;
  yearlyPrice: number;
  trialDays: number;
  features: string[];
  limits: Record<string, number | null>;
  status: "Active" | "Inactive";
  sortOrder: number;
}

export type SubscriptionStatus = "Trial" | "Active" | "Past Due" | "Cancelled" | "Expired";
export type BillingCycle = "Monthly" | "Yearly";

export interface TenantSubscription {
  id: string;
  status: SubscriptionStatus;
  billingCycle: BillingCycle;
  price: number;
  plan: SubscriptionPlan | null;
  trialStartsAt: string | null;
  trialEndsAt: string | null;
  startedAt: string | null;
  nextBillingAt: string | null;
  cancelledAt: string | null;
  expiresAt: string | null;
}

export type InvoiceStatus = "Pending" | "Paid" | "Failed" | "Refunded" | "Cancelled";

export interface SubscriptionInvoice {
  id: string;
  invoiceNumber: string;
  planName: string | null;
  amount: number;
  currency: string;
  billingPeriodStart: string;
  billingPeriodEnd: string;
  status: InvoiceStatus;
  issueDate: string;
  dueDate: string;
  paidDate: string | null;
}

export type TransactionStatus = "Pending" | "Paid" | "Failed" | "Refunded";

export interface PaymentTransaction {
  id: string;
  gateway: string;
  type: "Charge" | "Refund";
  reference: string;
  amount: number;
  currency: string;
  status: TransactionStatus;
  paidAt: string | null;
  planName: string | null;
  billingCycle: BillingCycle | null;
  invoiceNumber: string | null;
  createdAt: string;
}

interface RawPlan extends Omit<SubscriptionPlan, "id"> {
  id: number;
}
function toPlan(raw: RawPlan): SubscriptionPlan {
  return { ...raw, id: String(raw.id) };
}

interface RawSubscription extends Omit<TenantSubscription, "id" | "plan"> {
  id: number;
  plan: RawPlan | null;
}
function toSubscription(raw: RawSubscription): TenantSubscription {
  return { ...raw, id: String(raw.id), plan: raw.plan ? toPlan(raw.plan) : null };
}

interface RawInvoice extends Omit<SubscriptionInvoice, "id"> {
  id: number;
}
function toInvoice(raw: RawInvoice): SubscriptionInvoice {
  return { ...raw, id: String(raw.id) };
}

interface RawTransaction extends Omit<PaymentTransaction, "id"> {
  id: number;
}
function toTransaction(raw: RawTransaction): PaymentTransaction {
  return { ...raw, id: String(raw.id) };
}

export const subscriptionService = {
  async current(signal?: AbortSignal): Promise<ApiItemResponse<TenantSubscription>> {
    const response = await apiClient.get<ApiItemResponse<RawSubscription>>("/subscription", { signal });
    return { data: toSubscription(response.data) };
  },

  async plans(signal?: AbortSignal): Promise<ApiItemResponse<SubscriptionPlan[]>> {
    const response = await apiClient.get<ApiItemResponse<RawPlan[]>>("/subscription/plans", { signal });
    return { data: response.data.map(toPlan) };
  },

  async invoices(params: { page?: number; perPage?: number } = {}, signal?: AbortSignal): Promise<ApiListResponse<SubscriptionInvoice>> {
    const response = await apiClient.get<ApiListResponse<RawInvoice>>("/subscription/invoices", {
      signal,
      params: { page: params.page, per_page: params.perPage } satisfies QueryParams,
    });
    return { data: response.data.map(toInvoice), meta: response.meta };
  },

  /** Creates a gateway checkout session and returns the URL to redirect the browser to. */
  async checkout(planId: string, billingCycle: BillingCycle): Promise<string> {
    const response = await apiClient.post<ApiItemResponse<{ checkoutUrl: string }>>("/subscription/checkout", {
      plan_id: Number(planId),
      billing_cycle: billingCycle,
    });
    return response.data.checkoutUrl;
  },

  /** Server-side verification after the gateway redirects back — never trust the redirect alone. */
  async verifyCheckout(sessionId: string): Promise<{ transaction: PaymentTransaction; subscription: TenantSubscription | null }> {
    const response = await apiClient.get<ApiItemResponse<{ transaction: RawTransaction; subscription: RawSubscription | null }>>(
      "/subscription/checkout/verify",
      { params: { session_id: sessionId } },
    );
    return {
      transaction: toTransaction(response.data.transaction),
      subscription: response.data.subscription ? toSubscription(response.data.subscription) : null,
    };
  },
};
