import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import { Mail, Phone, MapPin, Cake, Pencil, Ban, RefreshCcw, Plus } from "lucide-react";
import { PageHeader } from "../components/layout/PageHeader";
import { Avatar } from "../components/ui/Avatar";
import { StatusBadge } from "../components/ui/Badge";
import { Button } from "../components/ui/Button";
import { Tabs } from "../components/ui/Tabs";
import { EmptyState } from "../components/ui/EmptyState";
import { LoadingState } from "../components/ui/LoadingState";
import { ErrorState } from "../components/ui/ErrorState";
import { useApiResource } from "../hooks/useApiResource";
import { memberService, type MemberInput } from "../services/memberService";
import { membershipPlanService } from "../services/membershipPlanService";
import { MemberFormModal } from "../components/members/MemberFormModal";
import { MemberAIInsightsPanel } from "../components/ai/MemberAIInsightsPanel";
import { formatCurrency, formatDate, formatDateTime } from "../utils/format";
import { useToast } from "../context/ToastContext";
import { ApiError } from "../services/apiClient";
import type { AttendanceRecord, Member, MembershipPlan, Payment } from "../types";

const tabs = ["Overview", "Attendance", "Payments", "Training", "Notes", "Activity", "AI Insights"];

export default function MemberDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showToast } = useToast();
  const [tab, setTab] = useState("Overview");
  const [noteText, setNoteText] = useState("");
  const [savingNote, setSavingNote] = useState(false);
  const [memberAttendance, setMemberAttendance] = useState<AttendanceRecord[]>([]);
  const [memberPayments, setMemberPayments] = useState<Payment[]>([]);
  const [plans, setPlans] = useState<MembershipPlan[]>([]);
  const [editOpen, setEditOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [formErrors, setFormErrors] = useState<Record<string, string[]> | undefined>(undefined);

  const {
    data: member,
    loading,
    error,
    refetch,
  } = useApiResource((signal) => memberService.getById(id!, signal).then((data) => ({ data })), [id], { enabled: Boolean(id) });

  useEffect(() => {
    if (!id) return;
    memberService.listAttendance(id, 10).then(setMemberAttendance).catch(() => undefined);
    memberService.listPayments(id, 10).then(setMemberPayments).catch(() => undefined);
  }, [id]);

  useEffect(() => {
    membershipPlanService.list().then(setPlans).catch(() => undefined);
  }, []);

  if (loading) return <LoadingState rows={6} />;

  if (error) return <ErrorState message={error} onRetry={refetch} />;

  if (!member) {
    return <EmptyState title="Member not found" description="This member may have been removed." action={<Button onClick={() => navigate("/admin/members")}>Back to Members</Button>} />;
  }

  async function renew() {
    if (!member) return;
    try {
      await memberService.updateStatus(member.id, "Active");
      showToast(`${member.name}'s membership renewed`);
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not renew membership.", "error");
    }
  }

  async function suspend() {
    if (!member) return;
    try {
      await memberService.updateStatus(member.id, "Suspended");
      showToast(`${member.name} suspended`, "warning");
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not suspend member.", "error");
    }
  }

  async function handleSave(updated: Member) {
    setSaving(true);
    setFormErrors(undefined);
    const input: MemberInput = {
      name: updated.name,
      gender: updated.gender,
      phone: updated.phone,
      email: updated.email,
      address: updated.address,
      dob: updated.dob,
      planId: updated.planId,
      startDate: updated.startDate,
      expiryDate: updated.expiryDate,
      status: updated.status,
      emergencyContact: updated.emergencyContact,
    };
    try {
      await memberService.update(updated.id, input);
      showToast("Member updated successfully");
      setEditOpen(false);
      refetch();
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setFormErrors(err.errors);
      } else {
        showToast(err instanceof ApiError ? err.message : "Could not save member.", "error");
      }
    } finally {
      setSaving(false);
    }
  }

  async function handleAddNote() {
    if (!noteText.trim() || !member) return;
    setSavingNote(true);
    try {
      await memberService.addNote(member.id, noteText.trim());
      showToast("Note added");
      setNoteText("");
      refetch();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Could not add note.", "error");
    } finally {
      setSavingNote(false);
    }
  }

  return (
    <div>
      <PageHeader
        title={member.name}
        breadcrumb={[{ label: "Members", to: "/admin/members" }, { label: member.name }]}
        action={
          <>
            <Button variant="secondary" icon={<RefreshCcw size={15} />} onClick={renew}>Renew</Button>
            <Button variant="secondary" icon={<Ban size={15} />} onClick={suspend}>Suspend</Button>
            <Button icon={<Pencil size={15} />} onClick={() => { setFormErrors(undefined); setEditOpen(true); }}>Edit</Button>
          </>
        }
      />

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div className="admin-card rounded-2xl p-6 shadow-sm lg:col-span-1">
          <div className="flex flex-col items-center text-center">
            <Avatar src={member.avatar} name={member.name} size="xl" />
            <h2 className="mt-4 text-lg font-semibold text-a-text dark:text-a-dark-text">{member.name}</h2>
            <p className="text-xs text-a-muted dark:text-a-dark-muted">{member.memberId}</p>
            <div className="mt-2"><StatusBadge status={member.status} /></div>
          </div>
          <div className="mt-6 space-y-3 border-t border-a-border pt-4 text-sm dark:border-a-dark-border">
            <div className="flex items-center gap-3 text-a-muted dark:text-a-dark-muted"><Phone size={15} /> {member.phone}</div>
            <div className="flex items-center gap-3 text-a-muted dark:text-a-dark-muted"><Mail size={15} /> {member.email}</div>
            <div className="flex items-center gap-3 text-a-muted dark:text-a-dark-muted"><MapPin size={15} /> {member.address}</div>
            <div className="flex items-center gap-3 text-a-muted dark:text-a-dark-muted"><Cake size={15} /> {formatDate(member.dob)}</div>
          </div>
          <div className="mt-5 grid grid-cols-2 gap-3 border-t border-a-border pt-4 dark:border-a-dark-border">
            <div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">Plan</p>
              <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">{member.planName}</p>
            </div>
            <div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">Attendance</p>
              <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">{member.attendanceRate}%</p>
            </div>
            <div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">Trainer</p>
              <p className="text-sm font-semibold text-a-text dark:text-a-dark-text">{member.trainerName ?? "Unassigned"}</p>
            </div>
            <div>
              <p className="text-xs text-a-muted dark:text-a-dark-muted">Balance Due</p>
              <p className={`text-sm font-semibold ${member.balanceDue > 0 ? "text-rose-500" : "text-emerald-500"}`}>
                {formatCurrency(member.balanceDue)}
              </p>
            </div>
          </div>
        </div>

        <div className="lg:col-span-2">
          <Tabs tabs={tabs} active={tab} onChange={setTab} className="mb-4 w-fit" />

          {tab === "Overview" && (
            <div className="admin-card space-y-4 rounded-2xl p-6 shadow-sm">
              <div className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Join Date</p><p className="font-medium text-a-text dark:text-a-dark-text">{formatDate(member.joinDate)}</p></div>
                <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Start Date</p><p className="font-medium text-a-text dark:text-a-dark-text">{formatDate(member.startDate)}</p></div>
                <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Expiry Date</p><p className="font-medium text-a-text dark:text-a-dark-text">{formatDate(member.expiryDate)}</p></div>
                <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Gender</p><p className="font-medium text-a-text dark:text-a-dark-text">{member.gender}</p></div>
                <div><p className="text-xs text-a-muted dark:text-a-dark-muted">Emergency Contact</p><p className="font-medium text-a-text dark:text-a-dark-text">{member.emergencyContact}</p></div>
              </div>
            </div>
          )}

          {tab === "Attendance" && (
            <div className="admin-card rounded-2xl p-6 shadow-sm">
              {memberAttendance.length === 0 ? <EmptyState title="No attendance records" /> : (
                <div className="space-y-3">
                  {memberAttendance.map((a) => (
                    <div key={a.id} className="flex items-center justify-between border-b border-a-border/60 pb-3 text-sm last:border-0 dark:border-a-dark-border/60">
                      <span className="text-a-text dark:text-a-dark-text">{formatDate(a.date)}</span>
                      <span className="text-a-muted dark:text-a-dark-muted">{a.checkIn} - {a.checkOut ?? "—"}</span>
                      <span className="text-a-muted dark:text-a-dark-muted">{a.method}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {tab === "Payments" && (
            <div className="admin-card rounded-2xl p-6 shadow-sm">
              {memberPayments.length === 0 ? <EmptyState title="No payment records" /> : (
                <div className="space-y-3">
                  {memberPayments.map((p) => (
                    <div key={p.id} className="flex items-center justify-between border-b border-a-border/60 pb-3 text-sm last:border-0 dark:border-a-dark-border/60">
                      <span className="text-a-text dark:text-a-dark-text">{p.invoiceId}</span>
                      <span className="text-a-muted dark:text-a-dark-muted">{formatDate(p.date)}</span>
                      <span className="font-medium text-a-text dark:text-a-dark-text">{formatCurrency(p.amount)}</span>
                      <StatusBadge status={p.status} />
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {tab === "Training" && (
            <div className="admin-card rounded-2xl p-6 shadow-sm">
              <div className="flex items-center gap-3">
                <Avatar name={member.trainerName ?? "Unassigned"} size="md" />
                <div>
                  <p className="text-sm font-medium text-a-text dark:text-a-dark-text">{member.trainerName ?? "No trainer assigned"}</p>
                  <p className="text-xs text-a-muted dark:text-a-dark-muted">Personal Trainer</p>
                </div>
              </div>
            </div>
          )}

          {tab === "Notes" && (
            <div className="admin-card rounded-2xl p-6 shadow-sm">
              <div className="mb-4 flex gap-2">
                <input
                  value={noteText}
                  onChange={(e) => setNoteText(e.target.value)}
                  placeholder="Add a note about this member..."
                  className="flex-1 rounded-xl border border-a-border bg-a-surface px-3.5 py-2.5 text-sm outline-none focus:border-a-accent dark:border-a-dark-border dark:bg-a-dark-surface-2"
                />
                <Button icon={<Plus size={15} />} disabled={savingNote} onClick={handleAddNote}>
                  {savingNote ? "Adding..." : "Add"}
                </Button>
              </div>
              {member.notes.length === 0 ? <EmptyState title="No notes yet" /> : (
                <div className="space-y-3">
                  {member.notes.map((n) => (
                    <div key={n.id} className="rounded-xl bg-a-surface-2 p-3 dark:bg-a-dark-surface-2">
                      <p className="text-sm text-a-text dark:text-a-dark-text">{n.text}</p>
                      <p className="mt-1 text-xs text-a-muted dark:text-a-dark-muted">{n.author} · {formatDate(n.date)}</p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}

          {tab === "AI Insights" && (
            <div className="admin-card rounded-2xl p-6 shadow-sm">
              <MemberAIInsightsPanel memberId={member.id} />
            </div>
          )}

          {tab === "Activity" && (
            <div className="admin-card rounded-2xl p-6 shadow-sm">
              <div className="space-y-4 border-l-2 border-a-border pl-4 dark:border-a-dark-border">
                <div>
                  <p className="text-sm font-medium text-a-text dark:text-a-dark-text">Joined the gym</p>
                  <p className="text-xs text-a-muted dark:text-a-dark-muted">{formatDateTime(member.joinDate)}</p>
                </div>
                {memberPayments.slice(0, 3).map((p) => (
                  <div key={p.id}>
                    <p className="text-sm font-medium text-a-text dark:text-a-dark-text">Payment of {formatCurrency(p.amount)}</p>
                    <p className="text-xs text-a-muted dark:text-a-dark-muted">{formatDateTime(p.date)}</p>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>

      <MemberFormModal
        open={editOpen}
        onClose={() => { setEditOpen(false); setFormErrors(undefined); }}
        onSave={handleSave}
        initial={member}
        plans={plans}
        saving={saving}
        errors={formErrors}
      />
    </div>
  );
}
