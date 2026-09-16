import { useEffect, useState, type FormEvent } from "react";
import { Search } from "lucide-react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import { Avatar } from "../ui/Avatar";
import { memberService } from "../../services/memberService";
import { useDebouncedValue } from "../../hooks/useDebouncedValue";
import type { Payment, Member } from "../../types";
import type { PaymentInput } from "../../services/paymentService";

const methods: NonNullable<Payment["method"]>[] = ["Cash", "Card", "Bank Transfer", "Online"];
const statuses: Payment["status"][] = ["Paid", "Pending", "Failed", "Refunded"];

interface PaymentFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (input: PaymentInput) => void;
  saving?: boolean;
  errors?: Record<string, string[]>;
}

function emptyInput(): PaymentInput {
  return { memberId: "", amount: 0, method: "Cash", date: new Date().toISOString().slice(0, 10), status: "Paid" };
}

export function PaymentFormModal({ open, onClose, onSave, saving, errors }: PaymentFormModalProps) {
  const [form, setForm] = useState<PaymentInput>(emptyInput());
  const [selectedMember, setSelectedMember] = useState<Member | null>(null);
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<Member[]>([]);
  const debouncedQuery = useDebouncedValue(query, 250);

  useEffect(() => {
    setForm(emptyInput());
    setSelectedMember(null);
    setQuery("");
    setResults([]);
  }, [open]);

  useEffect(() => {
    if (debouncedQuery.length < 2) {
      setResults([]);
      return;
    }
    const controller = new AbortController();
    memberService.list({ search: debouncedQuery, perPage: 5 }, controller.signal).then(({ data }) => setResults(data)).catch(() => undefined);
    return () => controller.abort();
  }, [debouncedQuery]);

  function update<K extends keyof PaymentInput>(key: K, value: PaymentInput[K]) {
    setForm((f) => ({ ...f, [key]: value }));
  }

  function fieldError(backendField: string): string | undefined {
    return errors?.[backendField]?.[0];
  }

  function handleSubmit(e: FormEvent) {
    e.preventDefault();
    onSave(form);
  }

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="Record Payment"
      description="Log a new payment for a member"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="payment-form" disabled={saving || !form.memberId}>{saving ? "Saving..." : "Record Payment"}</Button>
        </>
      }
    >
      <form id="payment-form" onSubmit={handleSubmit} className="space-y-4">
        {selectedMember ? (
          <div className="flex items-center gap-3 rounded-xl bg-a-surface-2 p-3 dark:bg-a-dark-surface-2">
            <Avatar src={selectedMember.avatar} name={selectedMember.name} size="sm" />
            <span className="flex-1 text-sm font-medium text-a-text dark:text-a-dark-text">{selectedMember.name}</span>
            <button
              type="button"
              className="text-xs font-medium text-a-accent-2 dark:text-a-accent"
              onClick={() => { setSelectedMember(null); update("memberId", ""); }}
            >
              Change
            </button>
          </div>
        ) : (
          <div className="relative">
            <Search size={15} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-a-muted dark:text-a-dark-muted" />
            <input
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              placeholder="Search member by name..."
              className="w-full rounded-xl border border-a-border bg-a-surface py-2.5 pl-9 pr-3 text-sm text-a-text outline-none transition-colors focus:border-a-accent dark:border-a-dark-border dark:bg-a-dark-surface-2 dark:text-a-dark-text"
            />
            {results.length > 0 && (
              <div className="mt-1.5 max-h-48 overflow-y-auto rounded-xl border border-a-border dark:border-a-dark-border">
                {results.map((m) => (
                  <button
                    key={m.id}
                    type="button"
                    onClick={() => { setSelectedMember(m); update("memberId", m.id); setQuery(""); setResults([]); }}
                    className="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"
                  >
                    <Avatar src={m.avatar} name={m.name} size="sm" />
                    {m.name}
                  </button>
                ))}
              </div>
            )}
            {fieldError("member_id") && <span className="mt-1 block text-xs text-rose-500">{fieldError("member_id")}</span>}
          </div>
        )}

        <div className="grid grid-cols-2 gap-4">
          <Input label="Amount (EGP)" type="number" value={form.amount} onChange={(e) => update("amount", Number(e.target.value))} error={fieldError("amount")} required />
          <Input label="Date" type="date" value={form.date ?? ""} onChange={(e) => update("date", e.target.value)} error={fieldError("date")} />
          <Select label="Method" value={form.method ?? "Cash"} onChange={(e) => update("method", e.target.value as Payment["method"])} options={methods.map((m) => ({ label: m, value: m }))} error={fieldError("method")} />
          <Select label="Status" value={form.status ?? "Paid"} onChange={(e) => update("status", e.target.value as Payment["status"])} options={statuses.map((s) => ({ label: s, value: s }))} error={fieldError("status")} />
        </div>
      </form>
    </Modal>
  );
}
