import { lazy, Suspense } from "react";
import { BrowserRouter, Outlet, Route, Routes } from "react-router-dom";
import { Layout } from "./components/layout/Layout";
import { PageLoader } from "./components/ui/PageLoader";
import Home from "./pages/Home";
import { ThemeProvider } from "./admin/context/ThemeContext";
import { ToastProvider } from "./admin/context/ToastContext";
import { AuthProvider } from "./admin/context/AuthContext";
import { AdminLayout } from "./admin/components/layout/AdminLayout";
import { ProtectedRoute } from "./admin/components/layout/ProtectedRoute";
import { PlatformLayout } from "./platform/components/layout/PlatformLayout";
import { PlatformProtectedRoute } from "./platform/components/layout/PlatformProtectedRoute";

const About = lazy(() => import("./pages/About"));
const Facilities = lazy(() => import("./pages/Facilities"));
const Equipment = lazy(() => import("./pages/Equipment"));
const Programs = lazy(() => import("./pages/Programs"));
const Trainers = lazy(() => import("./pages/Trainers"));
const Membership = lazy(() => import("./pages/Membership"));
const Gallery = lazy(() => import("./pages/Gallery"));
const Contact = lazy(() => import("./pages/Contact"));
const NotFound = lazy(() => import("./pages/NotFound"));

const AdminDashboard = lazy(() => import("./admin/pages/Dashboard"));
const AdminMembers = lazy(() => import("./admin/pages/Members"));
const AdminMemberDetails = lazy(() => import("./admin/pages/MemberDetails"));
const AdminMemberships = lazy(() => import("./admin/pages/Memberships"));
const AdminMembershipPlans = lazy(() => import("./admin/pages/MembershipPlans"));
const AdminAttendance = lazy(() => import("./admin/pages/Attendance"));
const AdminCheckInScanner = lazy(() => import("./admin/pages/CheckInScanner"));
const AdminTrainers = lazy(() => import("./admin/pages/Trainers"));
const AdminTrainerDetails = lazy(() => import("./admin/pages/TrainerDetails"));
const AdminPrograms = lazy(() => import("./admin/pages/TrainingPrograms"));
const AdminPersonalTraining = lazy(() => import("./admin/pages/PersonalTraining"));
const AdminClasses = lazy(() => import("./admin/pages/Classes"));
const AdminSchedule = lazy(() => import("./admin/pages/ClassSchedule"));
const AdminEquipment = lazy(() => import("./admin/pages/Equipment"));
const AdminFacilities = lazy(() => import("./admin/pages/Facilities"));
const AdminMaintenance = lazy(() => import("./admin/pages/Maintenance"));
const AdminPayments = lazy(() => import("./admin/pages/Payments"));
const AdminInvoices = lazy(() => import("./admin/pages/Invoices"));
const AdminExpenses = lazy(() => import("./admin/pages/Expenses"));
const AdminRevenue = lazy(() => import("./admin/pages/Revenue"));
const AdminBilling = lazy(() => import("./admin/pages/Billing"));
const AdminNotifications = lazy(() => import("./admin/pages/Notifications"));
const AdminAnnouncements = lazy(() => import("./admin/pages/Announcements"));
const AdminReports = lazy(() => import("./admin/pages/Reports"));
const AdminAnalytics = lazy(() => import("./admin/pages/Analytics"));
const AdminAIAssistant = lazy(() => import("./admin/pages/AIAssistant"));
const AdminAIInsights = lazy(() => import("./admin/pages/AIInsights"));
const AdminStaff = lazy(() => import("./admin/pages/Staff"));
const AdminRoles = lazy(() => import("./admin/pages/RolesPermissions"));
const AdminSettings = lazy(() => import("./admin/pages/Settings"));
const AdminNotFound = lazy(() => import("./admin/pages/AdminNotFound"));
const AdminLogin = lazy(() => import("./admin/pages/Login"));

const PlatformDashboard = lazy(() => import("./platform/pages/Dashboard"));
const PlatformGyms = lazy(() => import("./platform/pages/Gyms"));
const PlatformGymDetails = lazy(() => import("./platform/pages/GymDetails"));
const PlatformPlans = lazy(() => import("./platform/pages/Plans"));
const PlatformSubscriptions = lazy(() => import("./platform/pages/Subscriptions"));
const PlatformBilling = lazy(() => import("./platform/pages/Billing"));
const PlatformUsers = lazy(() => import("./platform/pages/PlatformUsers"));
const PlatformAuditLogs = lazy(() => import("./platform/pages/AuditLogs"));
const PlatformCommunicationStatus = lazy(() => import("./platform/pages/CommunicationStatus"));
const PlatformNotFound = lazy(() => import("./platform/pages/PlatformNotFound"));

function App() {
  return (
    <BrowserRouter basename={import.meta.env.BASE_URL}>
      <Suspense fallback={<PageLoader />}>
        <Routes>
          <Route element={<Layout />}>
            <Route path="/" element={<Home />} />
            <Route path="/about" element={<About />} />
            <Route path="/facilities" element={<Facilities />} />
            <Route path="/equipment" element={<Equipment />} />
            <Route path="/programs" element={<Programs />} />
            <Route path="/trainers" element={<Trainers />} />
            <Route path="/membership" element={<Membership />} />
            <Route path="/gallery" element={<Gallery />} />
            <Route path="/contact" element={<Contact />} />
          </Route>

          <Route
            element={
              <ThemeProvider>
                <ToastProvider>
                  <AuthProvider>
                    <Outlet />
                  </AuthProvider>
                </ToastProvider>
              </ThemeProvider>
            }
          >
            <Route path="/admin/login" element={<AdminLogin />} />

            <Route element={<ProtectedRoute />}>
              <Route element={<AdminLayout />}>
                <Route path="/admin" element={<AdminDashboard />} />
                <Route path="/admin/dashboard" element={<AdminDashboard />} />
                <Route path="/admin/members" element={<AdminMembers />} />
                <Route path="/admin/members/:id" element={<AdminMemberDetails />} />
                <Route path="/admin/memberships" element={<AdminMemberships />} />
                <Route path="/admin/membership-plans" element={<AdminMembershipPlans />} />
                <Route path="/admin/attendance" element={<AdminAttendance />} />
                <Route path="/admin/attendance/scanner" element={<AdminCheckInScanner />} />
                <Route path="/admin/trainers" element={<AdminTrainers />} />
                <Route path="/admin/trainers/:id" element={<AdminTrainerDetails />} />
                <Route path="/admin/programs" element={<AdminPrograms />} />
                <Route path="/admin/personal-training" element={<AdminPersonalTraining />} />
                <Route path="/admin/classes" element={<AdminClasses />} />
                <Route path="/admin/schedule" element={<AdminSchedule />} />
                <Route path="/admin/equipment" element={<AdminEquipment />} />
                <Route path="/admin/facilities" element={<AdminFacilities />} />
                <Route path="/admin/maintenance" element={<AdminMaintenance />} />
                <Route path="/admin/payments" element={<AdminPayments />} />
                <Route path="/admin/invoices" element={<AdminInvoices />} />
                <Route path="/admin/expenses" element={<AdminExpenses />} />
                <Route path="/admin/revenue" element={<AdminRevenue />} />
                <Route path="/admin/billing" element={<AdminBilling />} />
                <Route path="/admin/notifications" element={<AdminNotifications />} />
                <Route path="/admin/announcements" element={<AdminAnnouncements />} />
                <Route path="/admin/reports" element={<AdminReports />} />
                <Route path="/admin/analytics" element={<AdminAnalytics />} />
                <Route path="/admin/ai/assistant" element={<AdminAIAssistant />} />
                <Route path="/admin/ai/insights" element={<AdminAIInsights />} />
                <Route path="/admin/staff" element={<AdminStaff />} />
                <Route path="/admin/roles" element={<AdminRoles />} />
                <Route path="/admin/settings" element={<AdminSettings />} />
                <Route path="/admin/*" element={<AdminNotFound />} />
              </Route>
            </Route>

            <Route element={<PlatformProtectedRoute />}>
              <Route element={<PlatformLayout />}>
                <Route path="/platform" element={<PlatformDashboard />} />
                <Route path="/platform/dashboard" element={<PlatformDashboard />} />
                <Route path="/platform/gyms" element={<PlatformGyms />} />
                <Route path="/platform/gyms/:id" element={<PlatformGymDetails />} />
                <Route path="/platform/plans" element={<PlatformPlans />} />
                <Route path="/platform/subscriptions" element={<PlatformSubscriptions />} />
                <Route path="/platform/billing" element={<PlatformBilling />} />
                <Route path="/platform/users" element={<PlatformUsers />} />
                <Route path="/platform/audit-logs" element={<PlatformAuditLogs />} />
                <Route path="/platform/communication" element={<PlatformCommunicationStatus />} />
                <Route path="/platform/*" element={<PlatformNotFound />} />
              </Route>
            </Route>
          </Route>

          <Route path="*" element={<NotFound />} />
        </Routes>
      </Suspense>
    </BrowserRouter>
  );
}

export default App;
