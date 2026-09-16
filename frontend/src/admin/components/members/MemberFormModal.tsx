import { useEffect, useState, type FormEvent } from "react";
import { Modal } from "../ui/Modal";
import { Input } from "../ui/Input";
import { Select } from "../ui/Select";
import { Button } from "../ui/Button";
import type { Member, MembershipPlan } from "../../types";

interface MemberFormModalProps {
  open: boolean;
  onClose: () => void;
  onSave: (member: Member) => void;
  initial: Member | null;
  plans: MembershipPlan[];
  saving?: boolean;
  /** Laravel 422 validation errors, keyed by backend field name (e.g. "email", "plan_id"). */
  errors?: Record<string, string[]>;
}

function emptyMember(plans: MembershipPlan[]): Member {
  return {
    id: "",
    memberId: "",
    name: "",
    avatar: "https://i.pravatar.cc/150?img=1",
    gender: "Male",
    phone: "",
    email: "",
    address: "",
    dob: "",
    joinDate: new Date().toISOString().slice(0, 10),
    planId: plans[0]?.id ?? "",
    planName: plans[0]?.name ?? "",
    startDate: new Date().toISOString().slice(0, 10),
    expiryDate: new Date().toISOString().slice(0, 10),
    status: "Active",
    attendanceRate: 0,
    balanceDue: 0,
    notes: [],
    emergencyContact: "",
  };
}

export function MemberFormModal({ open, onClose, onSave, initial, plans, saving, errors }: MemberFormModalProps) {
  const [form, setForm] = useState<Member>(initial ?? emptyMember(plans));

  useEffect(() => {
    setForm(initial ?? emptyMember(plans));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [initial, open]);

  function update<K extends keyof Member>(key: K, value: Member[K]) {
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
      title={initial ? "Edit Member" : "Add Member"}
      description={initial ? "Update member information" : "Create a new member profile"}
      size="lg"
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>Cancel</Button>
          <Button type="submit" form="member-form" disabled={saving}>
            {saving ? "Saving..." : initial ? "Save Changes" : "Add Member"}
          </Button>
        </>
      }
    >
      <form id="member-form" onSubmit={handleSubmit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <Input label="Full Name" value={form.name} onChange={(e) => update("name", e.target.value)} error={fieldError("name")} required />
        <Input label="Phone" value={form.phone} onChange={(e) => update("phone", e.target.value)} error={fieldError("phone")} required />
        <Input label="Email" type="email" value={form.email} onChange={(e) => update("email", e.target.value)} error={fieldError("email")} required />
        <Input label="Address" value={form.address} onChange={(e) => update("address", e.target.value)} error={fieldError("address")} />
        <Select
          label="Gender"
          value={form.gender}
          onChange={(e) => update("gender", e.target.value as Member["gender"])}
          options={[{ label: "Male", value: "Male" }, { label: "Female", value: "Female" }]}
          error={fieldError("gender")}
        />
        <Input label="Date of Birth" type="date" value={form.dob} onChange={(e) => update("dob", e.target.value)} error={fieldError("dob")} />
        <Select
          label="Membership Plan"
          value={form.planId}
          onChange={(e) => {
            const p = plans.find((pl) => pl.id === e.target.value);
            update("planId", e.target.value);
            update("planName", p?.name ?? "");
          }}
          options={plans.map((p) => ({ label: p.name, value: p.id }))}
          error={fieldError("plan_id")}
        />
        <Select
          label="Status"
          value={form.status}
          onChange={(e) => update("status", e.target.value as Member["status"])}
          options={["Active", "Inactive", "Suspended", "Expired"].map((s) => ({ label: s, value: s }))}
          error={fieldError("status")}
        />
        <Input label="Start Date" type="date" value={form.startDate} onChange={(e) => update("startDate", e.target.value)} error={fieldError("start_date")} />
        <Input label="Expiry Date" type="date" value={form.expiryDate} onChange={(e) => update("expiryDate", e.target.value)} error={fieldError("expiry_date")} />
        <Input label="Emergency Contact" value={form.emergencyContact} onChange={(e) => update("emergencyContact", e.target.value)} error={fieldError("emergency_contact")} className="sm:col-span-2" />
      </form>
    </Modal>
  );
}
