import { useEffect, useState } from "react";
import { AlertTriangle, Clock, CheckCircle2, Wrench, Plus, Pencil } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { StatCard } from "../components/ui/StatCard";
import { Tabs } from "../components/ui/Tabs";
import { DataTable, type Column } from "../components/ui/DataTable";
import { Pagination } from "../components/ui/Pagination";
import { StatusBadge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { MaintenanceFormModal } from "../components/maintenance/MaintenanceFormModal";
import { useApiList } from "../hooks/useApiList";
import { maintenanceService, type MaintenanceInput, type MaintenanceStats } from "../services/maintenanceService";
import { equipmentService } from "../services/equipmentService";
import type { MaintenanceRecord, EquipmentItem } from "../types";
import { formatCurrency, formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const tabs = ["All", "Upcoming", "Overdue", "In Progress", "Completed"];
const PER_PAGE = 10;

export default function Maintenance() {
  const [tab, setTab] = useState("All");
  const [page, setPage] = useState(1);
  const [stats, setStats] = useState<MaintenanceStats | null>(null);
  const [equipment, setEquipment] = useState<EquipmentItem[]>([]);
  const [formOpen, setFormOpen] = useState(false);
  const [editing, setEditing] = useState<MaintenanceRecord | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const { showToast } = useToast();

  function loadStats() {
    maintenanceService.stats().then(setStats).catch(() => undefined);
  }

  useEffect(loadStats, []);
  useEffect(() => setPage(1), [tab]);
  useEffect(() => {
    equipmentService.list({ perPage: 100 }).then((res) => setEquipment(res.data)).catch(() => undefined);
  }, []);

  const { data: records, meta, loading, error, refetch } = useApiList(
    (signal) => maintenanceService.list({ status: tab === "All" ? undefined : tab, page, perPage: PER_PAGE }, signal),
    [tab, page],
  );

  function openCreate() {
    setEditing(null);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  function openEdit(m: MaintenanceRecord) {
    setEditing(m);
    setFormErrors(undefined);
    setFormOpen(true);
  }

  async function handleSave(input: MaintenanceInput) {
    setSaving(true);
    setFormErrors(undefined);
    try {
      if (editing) {
        await maintenanceService.update(editing.id, input);
        showToast("Maintenance record updated");
      } else {
        await maintenanceService.create(input);
        showToast("Maintenance scheduled");
      }
      setFormOpen(false);
      setEditing(null);
      refetch();
      loadStats();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save maintenance record.", "error");
    } finally {
      setSaving(false);
    }
  }

  const columns: Column<MaintenanceRecord>[] = [
    { key: "equipment", header: "Equipment", render: (m) => m.equipmentName },
    { key: "type", header: "Type", render: (m) => m.type },
    { key: "technician", header: "Technician", render: (m) => m.technician },
    { key: "date", header: "Date", render: (m) => formatDate(m.date) },
    { key: "cost", header: "Cost", render: (m) => formatCurrency(m.cost) },
    { key: "status", header: "Status", render: (m) => <StatusBadge status={m.status} /> },
    { key: "actions", header: "", className: "text-right", render: (m) => (
      <div className="flex justify-end" onClick={(e) => e.stopPropagation()}>
        <button className="rounded-lg p-1.5 text-a-muted hover:bg-a-surface-2 dark:hover:bg-a-dark-surface-2" onClick={() => openEdit(m)}><Pencil size={14} /></button>
      </div>
    ) },
  ];

  return (
    <div>
      <PageHeader
        title="Maintenance"
        description="Track equipment servicing and repairs"
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>Schedule Maintenance</Button>}
      />

      <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
        <StatCard label="Upcoming" value={String(stats?.upcoming ?? "—")} icon={<Clock size={18} />} accent="from-amber-400/20 to-amber-500/10" />
        <StatCard label="Overdue" value={String(stats?.overdue ?? "—")} icon={<AlertTriangle size={18} />} accent="from-rose-400/20 to-rose-500/10" />
        <StatCard label="Completed" value={String(stats?.completed ?? "—")} icon={<CheckCircle2 size={18} />} accent="from-emerald-400/20 to-emerald-500/10" />
        <StatCard label="Total Records" value={String(stats?.total ?? "—")} icon={<Wrench size={18} />} />
      </div>

      <div className="my-4"><Tabs tabs={tabs} active={tab} onChange={setTab} /></div>

      <div className="admin-card rounded-2xl p-4 shadow-sm">
        <DataTable columns={columns} rows={records} rowKey={(m) => m.id} loading={loading} error={error} onRetry={refetch} />
        {meta && meta.totalPages > 1 && (
          <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
        )}
      </div>

      <MaintenanceFormModal
        open={formOpen}
        onClose={() => { setFormOpen(false); setEditing(null); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={editing}
        equipment={equipment}
        saving={saving}
        errors={formErrors}
      />
    </div>
  );
}
