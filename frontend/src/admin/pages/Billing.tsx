import { useEffect, useState } from "react";
import { useSearchParams } from "react-router-dom";
import { CheckCircle2, XCircle, Sparkles, Check } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { StatusBadge } from "../components/ui/Badge";
import { LoadingState, Spinner } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { EmptyState } from "../components/ui/EmptyState";
import { DataTable, type Column } from "../components/ui/DataTable";
import { useApiResource } from "../hooks/useApiResource";
import { useApiList } from "../hooks/useApiList";
import { useAuth } from "../context/AuthContext";
import { useToast } from "../context/ToastContext";
import { formatCurrency, formatDate } from "../utils/format";
import { ApiError } from "../services/apiClient";
import {
  subscriptionService,
  type BillingCycle,
  type SubscriptionInvoice,
  type SubscriptionPlan,
} from "../services/subscriptionService";

const INVOICES_PER_PAGE = 10;

export default function Billing() {
  const { user } = useAuth();
  const { showToast } = useToast();
  const [searchParams, setSearchParams] = useSearchParams();
  const [billingCycle, setBillingCycle] = useState<BillingCycle>("Monthly");
  const [checkingOutPlanId, setCheckingOutPlanId] = useState<string | null>(null);
  const [verifying, setVerifying] = useState(false);
  const [checkoutResult, setCheckoutResult] = useState<"success" | "failed" | null>(null);

  const canManageBilling = user?.role === "Super Admin" || user?.role === "Admin";

  const {
    data: subscription,
    loading: subscriptionLoading,
    error: subscriptionError,
    refetch: refetchSubscription,
  } = useApiResource((signal) => subscriptionService.current(signal), []);

  const { data: plans, loading: plansLoading, error: plansError } = useApiResource((signal) => subscriptionService.plans(signal), []);

  const {
    data: invoices,
    meta: invoicesMeta,
    loading: invoicesLoading,
    error: invoicesError,
    refetch: refetchInvoices,
  } = useApiList((signal) => subscriptionService.invoices({ perPage: INVOICES_PER_PAGE }, signal), []);

  // Verify server-side once the gateway redirects back — the query string
  // alone is never treated as proof of payment (see subscriptionService.verifyCheckout).
  useEffect(() => {
    const sessionId = searchParams.get("session_id");
    const checkout = searchParams.get("checkout");

    if (checkout === "cancelled") {
      setCheckoutResult("failed");
      setSearchParams({}, { replace: true });
      return;
    }

    if (checkout === "success" && sessionId) {
      setVerifying(true);
      subscriptionService
        .verifyCheckout(sessionId)
        .then((result) => {
          setCheckoutResult(result.transaction.status === "Paid" ? "success" : "failed");
          refetchSubscription();
          refetchInvoices();
        })
        .catch(() => setCheckoutResult("failed"))
        .finally(() => {
          setVerifying(false);
          setSearchParams({}, { replace: true });
        });
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function startCheckout(plan: SubscriptionPlan) {
    setCheckingOutPlanId(plan.id);
    try {
      const url = await subscriptionService.checkout(plan.id, billingCycle);
      window.location.href = url;
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not start checkout. Please try again.", "error");
      setCheckingOutPlanId(null);
    }
  }

  const invoiceColumns: Column<SubscriptionInvoice>[] = [
    { key: "invoiceNumber", header: "Invoice #", render: (i) => <span className="font-medium text-a-text dark:text-a-dark-text">{i.invoiceNumber}</span> },
    { key: "planName", header: "Plan", render: (i) => i.planName ?? "—" },
    { key: "amount", header: "Amount", render: (i) => formatCurrency(i.amount) },
    { key: "status", header: "Status", render: (i) => <StatusBadge status={i.status} /> },
    { key: "issueDate", header: "Issued", render: (i) => formatDate(i.issueDate) },
    { key: "paidDate", header: "Paid", render: (i) => (i.paidDate ? formatDate(i.paidDate) : "—") },
  ];

  return (
    <div>
      <PageHeader title="Billing & Subscription" description="Manage your gym's SaaS plan, payment history, and billing details." />

      {verifying && (
        <div className="admin-card mb-6 flex items-center gap-3 rounded-2xl p-4 shadow-sm">
          <Spinner size={18} />
          <p className="text-sm text-a-text dark:text-a-dark-text">Confirming your payment…</p>
        </div>
      )}

      {!verifying && checkoutResult === "success" && (
        <div className="mb-6 flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4">
          <CheckCircle2 size={20} className="shrink-0 text-emerald-500" />
          <p className="text-sm text-emerald-700 dark:text-emerald-400">Payment confirmed — your subscription is now active.</p>
        </div>
      )}

      {!verifying && checkoutResult === "failed" && (
        <div className="mb-6 flex items-center gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-4">
          <XCircle size={20} className="shrink-0 text-rose-500" />
          <p className="text-sm text-rose-600 dark:text-rose-400">Checkout was not completed — no payment was taken.</p>
        </div>
      )}

      <div className="admin-card mb-6 rounded-2xl p-6 shadow-sm">
        <h2 className="mb-4 text-sm font-semibold text-a-text dark:text-a-dark-text">Current Subscription</h2>
        {subscriptionLoading ? (
          <LoadingState rows={2} />
        ) : subscriptionError ? (
          <ErrorState message={subscriptionError} onRetry={refetchSubscription} />
        ) : subscription ? (
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">Plan</p>
              <p className="mt-1 font-medium text-a-text dark:text-a-dark-text">{subscription.plan?.name ?? "—"}</p>
            </div>
            <div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">Status</p>
              <div className="mt-1"><StatusBadge status={subscription.status} /></div>
            </div>
            <div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">Billing Cycle</p>
              <p className="mt-1 font-medium text-a-text dark:text-a-dark-text">{subscription.billingCycle}</p>
            </div>
            <div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">
                {subscription.status === "Trial" ? "Trial Ends" : "Next Billing"}
              </p>
              <p className="mt-1 font-medium text-a-text dark:text-a-dark-text">
                {formatDate((subscription.status === "Trial" ? subscription.trialEndsAt : subscription.nextBillingAt) ?? "")}
              </p>
            </div>
          </div>
        ) : (
          <EmptyState title="No subscription yet" />
        )}
      </div>

      <div className="admin-card mb-6 rounded-2xl p-6 shadow-sm">
        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h2 className="text-sm font-semibold text-a-text dark:text-a-dark-text">Plans</h2>
          <div className="flex items-center gap-1 rounded-full bg-a-surface-2 p-1 dark:bg-a-dark-surface-2">
            {(["Monthly", "Yearly"] as const).map((cycle) => (
              <button
                key={cycle}
                onClick={() => setBillingCycle(cycle)}
                className={`rounded-full px-4 py-1.5 text-xs font-semibold transition-colors ${
                  billingCycle === cycle
                    ? "bg-a-surface text-a-text shadow-sm dark:bg-a-dark-surface dark:text-a-dark-text"
                    : "text-a-muted dark:text-a-dark-muted"
                }`}
              >
                {cycle}
              </button>
            ))}
          </div>
        </div>

        {plansLoading ? (
          <LoadingState rows={3} />
        ) : plansError ? (
          <ErrorState message={plansError} />
        ) : !plans || plans.length === 0 ? (
          <EmptyState title="No plans available" description="Ask the platform team to configure SaaS plans." />
        ) : (
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {plans.map((plan) => {
              const price = billingCycle === "Yearly" ? plan.yearlyPrice : plan.monthlyPrice;
              const isCurrentPlan = subscription?.plan?.id === plan.id && subscription.status === "Active";
              return (
                <div key={plan.id} className="flex flex-col rounded-2xl border border-a-border p-5 dark:border-a-dark-border">
                  <div className="flex items-center gap-2">
                    <Sparkles size={14} className="text-a-accent-2 dark:text-a-accent" />
                    <p className="font-semibold text-a-text dark:text-a-dark-text">{plan.name}</p>
                  </div>
                  {plan.description && <p className="mt-1 text-xs text-a-muted dark:text-a-dark-muted">{plan.description}</p>}
                  <p className="mt-4 text-2xl font-bold text-a-text dark:text-a-dark-text">
                    {formatCurrency(price)}
                    <span className="text-xs font-normal text-a-muted dark:text-a-dark-muted"> / {billingCycle === "Yearly" ? "yr" : "mo"}</span>
                  </p>
                  {plan.trialDays > 0 && <p className="mt-1 text-xs text-a-muted dark:text-a-dark-muted">{plan.trialDays}-day free trial</p>}
                  <ul className="mt-4 flex-1 space-y-1.5">
                    {plan.features.slice(0, 4).map((feature) => (
                      <li key={feature} className="flex items-start gap-1.5 text-xs text-a-muted dark:text-a-dark-muted">
                        <Check size={13} className="mt-0.5 shrink-0 text-emerald-500" />
                        {feature}
                      </li>
                    ))}
                  </ul>
                  {canManageBilling ? (
                    <Button
                      className="mt-4 justify-center"
                      variant={isCurrentPlan ? "secondary" : "primary"}
                      disabled={isCurrentPlan || checkingOutPlanId !== null}
                      onClick={() => startCheckout(plan)}
                    >
                      {checkingOutPlanId === plan.id ? <Spinner size={14} /> : isCurrentPlan ? "Current Plan" : "Subscribe"}
                    </Button>
                  ) : (
                    isCurrentPlan && (
                      <p className="mt-4 text-center text-xs font-medium text-emerald-600 dark:text-emerald-400">Current Plan</p>
                    )
                  )}
                </div>
              );
            })}
          </div>
        )}
        {!canManageBilling && (
          <p className="mt-4 text-xs text-a-muted dark:text-a-dark-muted">Only a Super Admin or Admin can change the subscription.</p>
        )}
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <h2 className="mb-4 px-2 text-sm font-semibold text-a-text dark:text-a-dark-text">Billing History</h2>
        <DataTable
          columns={invoiceColumns}
          rows={invoices}
          rowKey={(i) => i.id}
          loading={invoicesLoading}
          error={invoicesError}
          onRetry={refetchInvoices}
        />
        {invoicesMeta && invoicesMeta.total === 0 && !invoicesLoading && (
          <EmptyState title="No invoices yet" description="Your billing history will appear here once you subscribe to a plan." />
        )}
      </div>
    </div>
  );
}
