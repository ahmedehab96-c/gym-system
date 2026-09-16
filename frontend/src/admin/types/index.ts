// Core entity types for the Gym Admin Dashboard. These mirror the shape of
// the Laravel API responses, mapped from each service's `to*` functions.

export type MemberStatus = "Active" | "Inactive" | "Suspended" | "Expired";
export type Gender = "Male" | "Female";

export interface Member {
  id: string;
  memberId: string;
  name: string;
  avatar: string;
  gender: Gender;
  phone: string;
  email: string;
  address: string;
  dob: string;
  joinDate: string;
  planId: string;
  planName: string;
  startDate: string;
  expiryDate: string;
  status: MemberStatus;
  attendanceRate: number;
  trainerId?: string;
  trainerName?: string;
  balanceDue: number;
  notes: Note[];
  emergencyContact: string;
}

export interface Note {
  id: string;
  author: string;
  date: string;
  text: string;
}

export interface ActivityEvent {
  id: string;
  memberId: string;
  type: "join" | "renewal" | "payment" | "attendance" | "note" | "suspend" | "upgrade";
  title: string;
  description: string;
  date: string;
}

export type PlanStatus = "Active" | "Inactive";

export interface MembershipPlan {
  id: string;
  name: string;
  tagline: string;
  price: number;
  duration: string;
  durationDays: number;
  features: string[];
  memberCount: number;
  status: PlanStatus;
  color: string;
  popular?: boolean;
}

export type MembershipStatus = "Active" | "Expiring Soon" | "Expired" | "Suspended";

export interface Membership {
  id: string;
  memberId: string;
  memberName: string;
  memberAvatar: string;
  planId: string;
  planName: string;
  startDate: string;
  expiryDate: string;
  price: number;
  status: MembershipStatus;
}

export interface AttendanceRecord {
  id: string;
  memberId: string;
  memberName: string;
  memberAvatar: string;
  date: string;
  checkIn: string;
  checkOut: string | null;
  duration: string | null;
  method: "QR Code" | "Manual" | "Card";
}

export type TrainerStatus = "Active" | "On Leave" | "Inactive";

export interface Trainer {
  id: string;
  name: string;
  photo: string;
  specialty: string;
  specialties: string[];
  experience: string;
  phone: string;
  email: string;
  bio: string;
  assignedMembers: number;
  classesCount: number;
  status: TrainerStatus;
  rating: number;
  sessionsCompleted: number;
  schedule: { day: string; time: string; activity: string }[];
}

export type ProgramDifficulty = "Beginner" | "Intermediate" | "Advanced" | "All Levels";
export type ProgramStatus = "Active" | "Draft" | "Archived";

export interface TrainingProgram {
  id: string;
  name: string;
  description: string;
  image: string;
  duration: string;
  difficulty: ProgramDifficulty;
  trainerId: string;
  trainerName: string;
  membersEnrolled: number;
  status: ProgramStatus;
}

export type ClassStatus = "Scheduled" | "Full" | "Cancelled" | "Completed";

export interface GymClass {
  id: string;
  name: string;
  category: string;
  trainerId: string;
  trainerName: string;
  date: string;
  day: string;
  startTime: string;
  endTime: string;
  capacity: number;
  booked: number;
  status: ClassStatus;
  color: string;
}

export type EquipmentCondition = "Excellent" | "Good" | "Needs Maintenance" | "Out of Service";
export type EquipmentCategory = "Cardio" | "Strength" | "Free Weights" | "Functional";

export interface EquipmentItem {
  id: string;
  name: string;
  image: string;
  category: EquipmentCategory;
  brand: string;
  model: string;
  purchaseDate: string;
  condition: EquipmentCondition;
  location: string;
  lastMaintenance: string;
  nextMaintenance: string;
  status: "In Use" | "Under Maintenance" | "Retired";
}

export type MaintenanceStatus = "Upcoming" | "Overdue" | "Completed" | "In Progress";

export interface MaintenanceRecord {
  id: string;
  equipmentId: string;
  equipmentName: string;
  type: string;
  technician: string;
  date: string;
  cost: number;
  status: MaintenanceStatus;
  notes: string;
}

export type PaymentMethod = "Cash" | "Card" | "Bank Transfer" | "Online";
export type PaymentStatus = "Paid" | "Pending" | "Failed" | "Refunded";

export interface Payment {
  id: string;
  invoiceId: string;
  memberId: string;
  memberName: string;
  memberAvatar: string;
  amount: number;
  method: PaymentMethod;
  date: string;
  status: PaymentStatus;
}

export type InvoiceStatus = "Paid" | "Unpaid" | "Overdue" | "Draft";

export interface InvoiceItem {
  description: string;
  amount: number;
}

export interface InvoiceBusiness {
  name: string;
  phone: string | null;
  email: string | null;
  address: string | null;
  currency: string;
}

export interface Invoice {
  id: string;
  invoiceNumber: string;
  memberId: string;
  memberName: string;
  memberAvatar: string;
  issueDate: string;
  dueDate: string;
  items: InvoiceItem[];
  subtotal: number;
  discount: number;
  total: number;
  amountPaid: number | null;
  balanceDue: number | null;
  status: InvoiceStatus;
  business: InvoiceBusiness;
}

export type ExpenseCategory =
  | "Rent"
  | "Equipment"
  | "Maintenance"
  | "Salaries"
  | "Utilities"
  | "Marketing"
  | "Other";

export interface Expense {
  id: string;
  title: string;
  category: ExpenseCategory;
  amount: number;
  date: string;
  vendor: string;
  notes: string;
  receipt: string;
}

export type NotificationType =
  | "Membership Expiring"
  | "Membership Expired"
  | "Payment Received"
  | "Payment Pending"
  | "Payment Failed"
  | "New Member"
  | "Class Reminder"
  | "Class Cancellation"
  | "Maintenance Due"
  | "Maintenance Overdue"
  | "System Notification"
  | "Invoice Due Reminder";

export interface AppNotification {
  id: string;
  type: NotificationType;
  title: string;
  message: string;
  date: string;
  read: boolean;
}

export type AnnouncementAudience = "All Members" | "Trainers" | "Staff" | "Specific Plan";
export type AnnouncementStatus = "Published" | "Scheduled" | "Draft";

export interface Announcement {
  id: string;
  title: string;
  description: string;
  image: string;
  audience: AnnouncementAudience;
  planId: string;
  planName: string;
  publishDate: string;
  status: AnnouncementStatus;
}

export type StaffRole =
  | "Super Admin"
  | "Admin"
  | "Manager"
  | "Receptionist"
  | "Trainer"
  | "Accountant";
export type StaffStatus = "Active" | "Inactive";

export interface StaffMember {
  id: string;
  name: string;
  photo: string;
  role: StaffRole;
  email: string;
  phone: string;
  status: StaffStatus;
  lastLogin: string;
}

export interface PermissionModule {
  module: string;
  view: boolean;
  create: boolean;
  edit: boolean;
  delete: boolean;
}

export interface RolePermissions {
  role: StaffRole;
  permissions: PermissionModule[];
}
