import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { Mail, Phone, Star, Pencil } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge, Badge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { ChartCard } from "../components/ui/ChartCard";
import { EmptyState } from "../components/ui/EmptyState";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { TrainerFormModal } from "../components/trainers/TrainerFormModal";
import { useApiResource } from "../hooks/useApiResource";
import { trainerService, type TrainerInput } from "../services/trainerService";
import { memberService } from "../services/memberService";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";
import type { Member } from "../types";

export default function TrainerDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showToast } = useToast();
  const [assignedMembers, setAssignedMembers] = useState<Member[]>([]);
  const [editOpen, setEditOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);

  const {
    data: trainer,
    loading,
    error,
    refetch,
  } = useApiResource((signal) => trainerService.getById(id!, signal).then((data) => ({ data })), [id], { enabled: Boolean(id) });

  useEffect(() => {
    if (!id) return;
    memberService.list({ trainerId: id, perPage: 6 }).then((res) => setAssignedMembers(res.data)).catch(() => undefined);
  }, [id]);

  async function handleSave(input: TrainerInput, photoFile?: File) {
    if (!trainer) return;
    setSaving(true);
    setFormErrors(undefined);
    try {
      await trainerService.update(trainer.id, input);
      if (photoFile) await trainerService.uploadPhoto(trainer.id, photoFile);
      showToast("Trainer updated");
      setEditOpen(false);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) setFormErrors(err.errors);
      else showToast(err instanceof ApiError ? err.message : "Could not update trainer.", "error");
    } finally {
      setSaving(false);
    }
  }

  if (loading) return <LoadingState rows={6} />;
  if (error) return <ErrorState message={error} onRetry={refetch} />;

  if (!trainer) {
    return <EmptyState title="Trainer not found" action={<Button onClick={() => navigate("/admin/trainers")}>Back to Trainers</Button>} />;
  }

  return (
    <div>
      <PageHeader
        title={trainer.name}
        breadcrumb={[{ label: "Trainers", to: "/admin/trainers" }, { label: trainer.name }]}
        action={<Button icon={<Pencil size={15} />} onClick={() => { setFormErrors(undefined); setEditOpen(true); }}>Edit</Button>}
      />

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div className="admin-card rounded-2xl p-6 shadow-sm lg:col-span-1">
          <div className="flex flex-col items-center text-center">
            <Avatar src={trainer.photo} name={trainer.name} size="xl" />
            <h2 className="mt-4 text-lg font-semibold text-a-text dark:text-a-dark-text">{trainer.name}</h2>
            <p className="text-xs text-a-muted dark:text-a-dark-muted">{trainer.specialty}</p>
            <div className="mt-2"><StatusBadge status={trainer.status} /></div>
          </div>
          <div className="mt-5 flex flex-wrap justify-center gap-1.5">
            {trainer.specialties.map((s) => <Badge key={s} tone="accent">{s}</Badge>)}
          </div>
          <div className="mt-6 space-y-3 border-t border-a-border pt-4 text-sm dark:border-a-dark-border">
            <div className="flex items-center gap-3 text-a-muted dark:text-a-dark-muted"><Phone size={15} /> {trainer.phone}</div>
            <div className="flex items-center gap-3 text-a-muted dark:text-a-dark-muted"><Mail size={15} /> {trainer.email}</div>
          </div>
          <p className="mt-4 border-t border-a-border pt-4 text-sm text-a-muted dark:border-a-dark-border dark:text-a-dark-muted">{trainer.bio}</p>
        </div>

        <div className="space-y-5 lg:col-span-2">
          <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
            {[
              { label: "Rating", value: trainer.rating, icon: Star },
              { label: "Experience", value: trainer.experience },
              { label: "Members", value: trainer.assignedMembers },
              { label: "Sessions", value: trainer.sessionsCompleted },
            ].map((s) => (
              <div key={s.label} className="admin-card rounded-2xl p-4 text-center shadow-sm">
                <p className="text-xl font-bold text-a-text dark:text-a-dark-text">{s.value}</p>
                <p className="text-xs text-a-muted dark:text-a-dark-muted">{s.label}</p>
              </div>
            ))}
          </div>

          <ChartCard title="Weekly Schedule">
            <div className="space-y-2.5">
              {trainer.schedule.map((s, i) => (
                <div key={i} className="flex items-center justify-between rounded-xl bg-a-surface-2 px-4 py-2.5 text-sm dark:bg-a-dark-surface-2">
                  <span className="font-medium text-a-text dark:text-a-dark-text">{s.day}</span>
                  <span className="text-a-muted dark:text-a-dark-muted">{s.time}</span>
                  <span className="text-a-text dark:text-a-dark-text">{s.activity}</span>
                </div>
              ))}
            </div>
          </ChartCard>

          <ChartCard title="Assigned Members">
            {assignedMembers.length === 0 ? <EmptyState title="No members assigned yet" /> : (
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                {assignedMembers.map((m) => (
                  <div key={m.id} className="flex items-center gap-2.5 rounded-xl bg-a-surface-2 p-2.5 dark:bg-a-dark-surface-2">
                    <Avatar src={m.avatar} name={m.name} size="sm" />
                    <span className="truncate text-sm text-a-text dark:text-a-dark-text">{m.name}</span>
                  </div>
                ))}
              </div>
            )}
          </ChartCard>
        </div>
      </div>

      <TrainerFormModal
        open={editOpen}
        onClose={() => { setEditOpen(false); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={trainer}
        saving={saving}
        errors={formErrors}
        uploadPhoto={(file) => trainerService.uploadPhoto(trainer.id, file)}
      />
    </div>
  );
}
