import { useEffect, useState } from "react";
import { Plus, Pencil, Trash2, Megaphone } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Button } from "../components/ui/Button";
import { StatusBadge, Badge } from "../components/ui/Badge";
import { Pagination } from "../components/ui/Pagination";
import { ConfirmDialog } from "../components/ui/ConfirmDialog";
import { Modal } from "../components/ui/Modal";
import { Input } from "../components/ui/Input";
import { Select } from "../components/ui/Select";
import { ImageUploader } from "../components/ui/ImageUploader";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { EmptyState } from "../components/ui/EmptyState";
import { useApiList } from "../hooks/useApiList";
import { announcementService, type AnnouncementInput } from "../services/announcementService";
import { membershipPlanService } from "../services/membershipPlanService";
import type { Announcement, AnnouncementAudience, MembershipPlan } from "../types";
import { formatDate } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";

const audiences: AnnouncementAudience[] = ["All Members", "Trainers", "Staff", "Specific Plan"];
const PER_PAGE = 12;

function emptyForm(): AnnouncementInput {
  return { title: "", description: "", audience: "All Members", publishDate: new Date().toISOString().slice(0, 10), status: "Published" };
}

export default function Announcements() {
  const [deleteTarget, setDeleteTarget] = useState<Announcement | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [editing, setEditing] = useState<Announcement | null>(null);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);
  const [form, setForm] = useState<AnnouncementInput>(emptyForm());
  const [image, setImage] = useState<string | undefined>(undefined);
  const [pendingFile, setPendingFile] = useState<File | undefined>(undefined);
  const [plans, setPlans] = useState<MembershipPlan[]>([]);
  const [page, setPage] = useState(1);
  const { showToast } = useToast();

  const { data: announcements, meta, loading, error, refetch } = useApiList(
    (signal) => announcementService.list({ page, perPage: PER_PAGE }, signal),
    [page],
  );

  useEffect(() => {
    membershipPlanService.list().then(setPlans).catch(() => undefined);
  }, []);

  function openCreate() {
    setEditing(null);
    setForm(emptyForm());
    setImage(undefined);
    setPendingFile(undefined);
    setFormErrors(undefined);
    setModalOpen(true);
  }

  function openEdit(a: Announcement) {
    setEditing(a);
    setForm({ title: a.title, description: a.description, audience: a.audience, planId: a.planId || undefined, status: a.status, publishDate: a.publishDate });
    setImage(a.image || undefined);
    setPendingFile(undefined);
    setFormErrors(undefined);
    setModalOpen(true);
  }

  async function handleSave() {
    setSaving(true);
    setFormErrors(undefined);
    try {
      const saved = editing ? await announcementService.update(editing.id, form) : await announcementService.create(form);
      if (pendingFile) await announcementService.uploadImage(saved.id, pendingFile);
      showToast(editing ? "Announcement updated" : "Announcement published");
      setModalOpen(false);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not save announcement.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await announcementService.remove(deleteTarget.id);
      showToast("Announcement deleted", "error");
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not delete announcement.", "error");
    }
  }

  return (
    <div>
      <PageHeader
        title="Announcements"
        description="Broadcast updates to members, trainers, and staff"
        action={<Button icon={<Plus size={16} />} onClick={openCreate}>New Announcement</Button>}
      />

      {loading ? (
        <LoadingState rows={4} />
      ) : error ? (
        <ErrorState message={error} onRetry={refetch} />
      ) : announcements.length === 0 ? (
        <EmptyState title="No announcements yet" />
      ) : (
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          {announcements.map((a) => (
            <div key={a.id} className="admin-card overflow-hidden rounded-2xl shadow-sm">
              <div className="relative h-32 overflow-hidden">
                <img src={a.image || "https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=600&q=80"} alt={a.title} className="h-full w-full object-cover" />
                <div className="absolute right-3 top-3"><StatusBadge status={a.status} /></div>
              </div>
              <div className="p-4">
                <h3 className="font-semibold text-a-text dark:text-a-dark-text">{a.title}</h3>
                <p className="mt-1 line-clamp-2 text-xs text-a-muted dark:text-a-dark-muted">{a.description}</p>
                <div className="mt-3 flex items-center justify-between">
                  <Badge tone="accent"><Megaphone size={11} className="mr-1 inline" />{a.audience === "Specific Plan" && a.planName ? a.planName : a.audience}</Badge>
                  <span className="text-xs text-a-muted dark:text-a-dark-muted">{formatDate(a.publishDate)}</span>
                </div>
                <div className="mt-3 flex gap-2">
                  <Button variant="secondary" size="sm" className="flex-1" icon={<Pencil size={13} />} onClick={() => openEdit(a)}>Edit</Button>
                  <Button variant="danger" size="icon" onClick={() => setDeleteTarget(a)}><Trash2 size={14} /></Button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {meta && meta.totalPages > 1 && (
        <Pagination page={meta.page} totalPages={meta.totalPages} onChange={setPage} totalItems={meta.total} pageSize={PER_PAGE} />
      )}

      <Modal
        open={modalOpen}
        onClose={() => setModalOpen(false)}
        title={editing ? "Edit Announcement" : "New Announcement"}
        size="md"
        footer={<><Button variant="secondary" onClick={() => setModalOpen(false)}>Cancel</Button><Button disabled={saving} onClick={handleSave}>{saving ? "Saving..." : editing ? "Save Changes" : "Publish"}</Button></>}
      >
        <div className="space-y-4">
          <ImageUploader value={image} onChange={setImage} onFileSelected={setPendingFile} />
          <Input label="Title" value={form.title} onChange={(e) => setForm((f) => ({ ...f, title: e.target.value }))} error={formErrors?.title?.[0]} />
          <Input label="Description" value={form.description ?? ""} onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))} error={formErrors?.description?.[0]} />
          <Select
            label="Audience"
            value={form.audience}
            onChange={(e) => setForm((f) => ({ ...f, audience: e.target.value as AnnouncementAudience, planId: undefined }))}
            options={audiences.map((a) => ({ label: a, value: a }))}
            error={formErrors?.audience?.[0]}
          />
          {form.audience === "Specific Plan" && (
            <Select
              label="Plan"
              value={form.planId ?? ""}
              onChange={(e) => setForm((f) => ({ ...f, planId: e.target.value }))}
              options={[{ label: "Select a plan…", value: "" }, ...plans.map((p) => ({ label: p.name, value: p.id }))]}
              error={formErrors?.plan_id?.[0]}
            />
          )}
          <Input label="Publish Date" type="date" value={form.publishDate} onChange={(e) => setForm((f) => ({ ...f, publishDate: e.target.value }))} error={formErrors?.publish_date?.[0]} />
          <Select
            label="Status"
            value={form.status ?? "Published"}
            onChange={(e) => setForm((f) => ({ ...f, status: e.target.value as Announcement["status"] }))}
            options={["Published", "Scheduled", "Draft"].map((s) => ({ label: s, value: s }))}
            error={formErrors?.status?.[0]}
          />
        </div>
      </Modal>

      <ConfirmDialog
        open={!!deleteTarget}
        onClose={() => setDeleteTarget(null)}
        onConfirm={handleDelete}
        title="Delete announcement?"
        description={`${deleteTarget?.title} will be removed.`}
        confirmLabel="Delete"
        danger
      />
    </div>
  );
}
