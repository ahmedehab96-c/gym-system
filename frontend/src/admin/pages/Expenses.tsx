import { useEffect, useState } from "react";
import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import { Plus, Receipt, TrendingDown, MoreVertical, Pencil, Trash2, Paperclip } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { StatCard } from "../components/ui/StatCard";
import { ChartCard } from "../components/ui/ChartCard";
import { SearchBar } from "../components/ui/SearchBar";
import { FilterDropdown } from "../components/ui/FilterDropdown";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { Badge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { Modal } from "../components/ui/Modal";
import { Input } from "../components/ui/Input";
import { Select } from "../components/ui/Select";
import { Dropdown, DropdownItem } from "../components/ui/Dropdown";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { useApiList } from "../hooks/useApiList";
import { useDebouncedValue } from "../hooks/useDebouncedValue";
import { expenseService, type ExpenseStats } from "../services/expenseService";
import type { Expense, ExpenseCategory } from "../types";
import { formatCurrency, formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const categories: ExpenseCategory[] = ["Rent", "Equipment", "Maintenance", "Salaries", "Utilities", "Marketing", "Other"];
const PER_PAGE = 10;

const emptyForm = { title: "", category: "Other" as ExpenseCategory, amount: "", vendor: "" };

export default function Expenses() {
  const [query, setQuery] = useState("");
  const [category, setCategory] = useState("all");
  const [page, setPage] = useState(1);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Expense | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Expense | null>(null);
  const [receiptTarget, setReceiptTarget] = useState<Expense | null>(null);
  const [uploadingReceipt, setUploadingReceipt] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [form, setForm] = useState(emptyForm);
  const [stats, setStats] = useState<ExpenseStats | null>(null);
  const { showToast } = useToast();
  const debouncedQuery = useDebouncedValue(query);

  function loadStats() {
    expenseService.stats().then(setStats).catch(() => undefined);
  }

  useEffect(loadStats, []);
  useEffect(() => setPage(1), [debouncedQuery, category]);

  const { data: expenses, meta, loading, error, refetch } = useApiList(
    (signal) =>
      expenseService.list({ search: debouncedQuery || undefined, category: category === "all" ? undefined : category, page, perPage: PER_PAGE }, signal),
    [debouncedQuery, category, page],
  );

  function openCreate() {
    setEditing(null);
    setForm(emptyForm);
    setFormErrors(undefined);
    setModalOpen(true);
  }

  function openEdit(e: Expense) {
    setEditing(e);
    setForm({ title: e.title, category: e.category, amount: String(e.amount), vendor: e.vendor });
    setFormErrors(undefined);
    setModalOpen(true);
  }

  async function saveExpense() {
    setSaving(true);
    setFormErrors(undefined);
    const input = { title: form.title, category: form.category, amount: Number(form.amount), vendor: form.vendor || undefined };
    try {
      if (editing) {
        await expenseService.update(editing.id, input);
        showToast("Expense updated");
      } else {
        await expenseService.create(input);
        showToast("Expense added");
      }
      setModalOpen(false);
      refetch();
      loadStats();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save expense.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await expenseService.remove(deleteTarget.id);
      showToast("Expense deleted", "error");
      setDeleteTarget(null);
      refetch();
      loadStats();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete expense.", "error");
    }
  }

  async function handleReceiptUpload(file: File) {
    if (!receiptTarget) return;
    setUploadingReceipt(true);
    try {
      await expenseService.uploadReceipt(receiptTarget.id, file);
      showToast("Receipt uploaded");
      setReceiptTarget(null);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not upload receipt.", "error");
    } finally {
      setUploadingReceipt(false);
    }
  }

  const columns: Column<Expense>[] = [
    { key: "title", header: "Title", render: (e) => <span className="font-medium">{e.title}</span> },
    { key: "category", header: "Category", render: (e) => <Badge tone="accent">{e.category}</Badge> },
    { key: "vendor", header: "Vendor", render: (e) => e.vendor },
    { key: "date", header: "Date", render: (e) => formatDate(e.date) },
    { key: "amount", header: "Amount", render: (e) => formatCurrency(e.amount) },
    { key: "receipt", header: "Receipt", render: (e) => e.receipt ? <a href={e.receipt} target="_blank" rel="noreferrer" className="text-a-accent-2 dark:text-a-accent" onClick={(ev) => ev.stopPropagation()}>View</a> : "—" },
    {
      key: "actions",
      header: "",
      className: "text-right",
      render: (e) => (
        <div onClick={(ev) => ev.stopPropagation()} className="flex justify-end">
          <Dropdown align="right" trigger={<button className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2"><MoreVertical size={16} /></button>}>
            <DropdownItem onClick={() => openEdit(e)}><Pencil size={14} /> Edit</DropdownItem>
            <DropdownItem onClick={() => setReceiptTarget(e)}><Paperclip size={14} /> {e.receipt ? "Replace Receipt" : "Upload Receipt"}</DropdownItem>
            <DropdownItem onClick={() => setDeleteTarget(e)} className="text-rose-500"><Trash2 size={14} /> Delete</DropdownItem>
          </Dropdown>
        </div>
      ),
    },
  ];

  return (
    <div>
      <PageHeader
        title="Expenses"
        description="Track operating costs across categories"
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Add Expense</Button>}
      />

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Total Expenses" value={formatCurrency(stats?.total ?? 0)} icon={<Receipt size={18} />} accent="from-rose-400/20 to-rose-500/10" />
        <StatCard label="This Month" value={formatCurrency(stats?.thisMonth ?? 0)} icon={<TrendingDown size={18} />} />
        <StatCard label="Largest Category" value={stats?.largestCategory ?? "—"} icon={<Receipt size={18} />} />
        <StatCard label="Total Records" value={String(stats?.totalRecords ?? "—")} icon={<Receipt size={18} />} />
      </div>

      <div className="my-4">
        <ChartCard title="Monthly Expense Breakdown" subtitle="By category">
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={stats?.byCategory ?? []} layout="vertical" margin={{ left: 20 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-a-border)" horizontal={false} />
              <XAxis type="number" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} tickFormatter={(v) => `${v / 1000}k`} />
              <YAxis type="category" dataKey="category" tick={{ fontSize: 11, fill: "var(--color-a-muted)" }} axisLine={false} tickLine={false} width={90} />
              <Tooltip contentStyle={{ borderRadius: 12, border: "1px solid var(--color-a-border)", fontSize: 12 }} />
              <Bar dataKey="amount" fill="#e0263c" radius={[0, 6, 6, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </ChartCard>
      </div>

      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchBar value={query} onChange={setQuery} placeholder="Search expenses..." className="sm:max-w-xs" />
        <FilterDropdown label="Categories" value={category} onChange={setCategory} options={categories.map((c) => ({ label: c, value: c }))} />
      </div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={expenses} rowKey={(e) => e.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? "Edit Expense" : "Add Expense"}
        size="sm"
        footer={<><Button variant="secondary" onClick={() => setModalOpen(false)}>Cancel</Button><Button disabled={saving} onClick={saveExpense}>{saving ? "Saving..." : editing ? "Save Changes" : "Add Expense"}</Button></>}
      >
        <div className="space-y-4">
          <Input label="Title" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} error={formErrors?.title?.[0]} />
          <Select label="Category" value={form.category} onChange={(e) => setForm((f) => ({ ...f, category: e.target.value as ExpenseCategory }))} options={categories.map((c) => ({ label: c, value: c }))} error={formErrors?.category?.[0]} />
          <Input label="Amount (EGP)" type="number" value={form.amount} onChange={(e) => setForm((f) => ({ ...f, amount: e.target.value }))} error={formErrors?.amount?.[0]} />
          <Input label="Vendor" value={form.vendor} onChange={(e) => setForm((f) => ({ ...f, vendor: e.target.value }))} error={formErrors?.vendor?.[0]} />
        </div>
      </Modal>

      <Modal open={!!receiptTarget} onClose={() => setReceiptTarget(null)} title="Upload Receipt" size="sm">
        <input
          type="file"
          accept="image/*,application/pdf"
          disabled={uploadingReceipt}
          onChange={(e) => { const f = e.target.files?.[0]; if (f) handleReceiptUpload(f); }}
          className="w-full text-sm text-a-muted dark:text-a-dark-muted"
        />
        {uploadingReceipt && <p className="mt-2 text-xs text-a-muted dark:text-a-dark-muted">Uploading...</p>}
      </Modal>

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete expense?"
        description={`This will permanently remove "${deleteTarget?.title}" from your records.`}
        confirmLabel="Delete"
        danger
      />
    </div>
  );
}
