<?php

use App\Http\Controllers\Api\AI\AssistantController;
use App\Http\Controllers\Api\AI\InsightController;
use App\Http\Controllers\Api\AI\MemberInsightController;
use App\Http\Controllers\Api\AI\ReportAssistantController;
use App\Http\Controllers\Api\AI\UsageController as AIUsageController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClassScheduleController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FacilityController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\GymClassController;
use App\Http\Controllers\Api\GymSettingController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MaintenanceRecordController;
use App\Http\Controllers\Api\Member\AttendanceController as MemberAttendanceController;
use App\Http\Controllers\Api\Member\AuthController as MemberAuthController;
use App\Http\Controllers\Api\Member\ClassController as MemberClassController;
use App\Http\Controllers\Api\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Api\Member\InvoiceController as MemberInvoiceController;
use App\Http\Controllers\Api\Member\MembershipController as MemberMembershipController;
use App\Http\Controllers\Api\Member\NotificationController as MemberNotificationController;
use App\Http\Controllers\Api\Member\PaymentController as MemberPaymentController;
use App\Http\Controllers\Api\Member\ProfileController as MemberProfileController;
use App\Http\Controllers\Api\Member\ProgramController as MemberProgramController;
use App\Http\Controllers\Api\Member\QrController as MemberQrController;
use App\Http\Controllers\Api\Member\TrainerController as MemberTrainerController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\MembershipPlanController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\NotificationDeliveryController;
use App\Http\Controllers\Api\NotificationPreferenceController;
use App\Http\Controllers\Api\Payment\CheckoutController;
use App\Http\Controllers\Api\Payment\PaymentWebhookController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PersonalTrainingSessionController;
use App\Http\Controllers\Api\PlatformAuditLogController;
use App\Http\Controllers\Api\PlatformBillingController;
use App\Http\Controllers\Api\PlatformCommunicationController;
use App\Http\Controllers\Api\PlatformDashboardController;
use App\Http\Controllers\Api\PlatformGymController;
use App\Http\Controllers\Api\PlatformPaymentController;
use App\Http\Controllers\Api\PlatformSubscriptionController;
use App\Http\Controllers\Api\PlatformSubscriptionPlanController;
use App\Http\Controllers\Api\PlatformUserController;
use App\Http\Controllers\Api\QrAttendanceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TenantSubscriptionController;
use App\Http\Controllers\Api\Trainer\DashboardController as TrainerDashboardController;
use App\Http\Controllers\Api\Trainer\ProfileController as TrainerProfileController;
use App\Http\Controllers\Api\TrainerController;
use App\Http\Controllers\Api\TrainingProgramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1
|--------------------------------------------------------------------------
| Phase 1 laid out every route with empty controller stubs. Phase 2 wires
| up authentication (Sanctum) and role/permission authorization; module
| business logic (Members, Payments, etc.) still lands in later phases.
| See the Backend Blueprint (§5-6) for the full endpoint contract.
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::get('/ping', fn () => response()->json(['data' => ['status' => 'ok']]));

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->middleware(['auth:sanctum', 'tenant'])->name('me');
    });

    // Public, unauthenticated read endpoints for the marketing site.
    Route::prefix('public')->name('public.')->group(function () {
        Route::get('/facilities', [FacilityController::class, 'index'])->name('facilities');
        Route::get('/trainers', [TrainerController::class, 'index'])->name('trainers');
    });

    // The payment gateway's server-to-server callback (Phase 23) — no
    // auth:sanctum token exists for this caller, so 'payment.webhook'
    // (signature verification) is the only gate. See
    // App\Http\Middleware\VerifyPaymentWebhookSignature and
    // App\Http\Controllers\Api\Payment\PaymentWebhookController.
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::post('stripe', [PaymentWebhookController::class, 'stripe'])
            ->middleware(['throttle:60,1', 'payment.webhook'])
            ->name('stripe');
    });

    // Everything below requires an authenticated staff/admin user. `tenant`
    // must run right after auth so every query in this group is scoped to
    // the authenticated user's own gym (see App\Models\Concerns\TenantScope).
    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

        Route::apiResource('members', MemberController::class)
            ->middleware(['permission:Members', 'subscription.limit:members']);
        Route::patch('members/{member}/status', [MemberController::class, 'updateStatus'])
            ->name('members.status')
            ->middleware('permission:Members,edit');
        Route::get('members/{member}/attendance', [AttendanceController::class, 'forMember'])
            ->name('members.attendance')
            ->middleware('permission:Attendance');
        Route::get('members/{member}/payments', [PaymentController::class, 'forMember'])
            ->name('members.payments')
            ->middleware('permission:Payments');
        Route::post('members/{member}/notes', [MemberController::class, 'storeNote'])
            ->name('members.notes.store')
            ->middleware('permission:Members,edit');
        Route::post('members/{member}/set-password', [MemberController::class, 'setPassword'])
            ->name('members.set-password')
            ->middleware('permission:Members,edit');

        Route::apiResource('membership-plans', MembershipPlanController::class)->middleware('permission:Memberships');

        Route::apiResource('memberships', MembershipController::class)
            ->only(['index', 'show', 'store'])
            ->middleware('permission:Memberships');
        Route::post('memberships/{membership}/renew', [MembershipController::class, 'renew'])
            ->name('memberships.renew')
            ->middleware('permission:Memberships,edit');
        Route::post('memberships/{membership}/change-plan', [MembershipController::class, 'changePlan'])
            ->name('memberships.change-plan')
            ->middleware('permission:Memberships,edit');
        Route::post('memberships/{membership}/suspend', [MembershipController::class, 'suspend'])
            ->name('memberships.suspend')
            ->middleware('permission:Memberships,edit');
        Route::post('memberships/{membership}/cancel', [MembershipController::class, 'cancel'])
            ->name('memberships.cancel')
            ->middleware('permission:Memberships,edit');

        Route::get('attendance/today', [AttendanceController::class, 'today'])
            ->name('attendance.today')
            ->middleware('permission:Attendance');
        Route::get('attendance/stats', [AttendanceController::class, 'stats'])
            ->name('attendance.stats')
            ->middleware('permission:Attendance');
        Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])
            ->name('attendance.check-in')
            ->middleware('permission:Attendance');
        Route::post('attendance/{attendance}/check-out', [AttendanceController::class, 'checkOut'])
            ->name('attendance.check-out')
            ->middleware('permission:Attendance,edit');
        Route::apiResource('attendance', AttendanceController::class)
            ->only(['index'])
            ->middleware('permission:Attendance');

        // Front-desk QR scanning (Phase 28) — the token IS the identity;
        // resolving it is what identifies/validates the member, so
        // neither route takes a member id in the URL.
        Route::post('qr/check-in', [QrAttendanceController::class, 'checkIn'])
            ->name('qr.check-in')
            ->middleware(['permission:Attendance', 'throttle:30,1']);
        Route::post('qr/check-out', [QrAttendanceController::class, 'checkOut'])
            ->name('qr.check-out')
            ->middleware(['permission:Attendance,edit', 'throttle:30,1']);

        Route::get('trainers/stats', [TrainerController::class, 'stats'])
            ->name('trainers.stats')
            ->middleware('permission:Trainers');
        Route::apiResource('trainers', TrainerController::class)
            ->middleware(['permission:Trainers', 'subscription.limit:trainers']);
        Route::post('trainers/{trainer}/photo', [TrainerController::class, 'uploadPhoto'])
            ->name('trainers.photo')
            ->middleware('permission:Trainers,edit');

        Route::apiResource('training-programs', TrainingProgramController::class)->middleware('permission:Trainers');
        Route::post('training-programs/{training_program}/image', [TrainingProgramController::class, 'uploadImage'])
            ->name('training-programs.image')
            ->middleware('permission:Trainers,edit');
        Route::post('training-programs/{training_program}/enroll', [TrainingProgramController::class, 'enroll'])
            ->name('training-programs.enroll')
            ->middleware('permission:Trainers,edit');
        Route::delete('training-programs/{training_program}/enroll/{member}', [TrainingProgramController::class, 'unenroll'])
            ->name('training-programs.unenroll')
            ->middleware('permission:Trainers,edit');

        Route::apiResource('personal-training', PersonalTrainingSessionController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::prefix('schedule')->name('schedule.')->middleware('permission:Classes')->group(function () {
            Route::get('daily', [ClassScheduleController::class, 'daily'])->name('daily');
            Route::get('weekly', [ClassScheduleController::class, 'weekly'])->name('weekly');
            Route::get('monthly', [ClassScheduleController::class, 'monthly'])->name('monthly');
        });
        Route::apiResource('classes', GymClassController::class)
            ->middleware(['permission:Classes', 'subscription.limit:classes']);
        Route::post('classes/{class}/book', [GymClassController::class, 'book'])
            ->name('classes.book')
            ->middleware('permission:Classes,edit');
        Route::delete('classes/{class}/book/{member}', [GymClassController::class, 'cancelBooking'])
            ->name('classes.cancel-booking')
            ->middleware('permission:Classes,edit');

        Route::apiResource('equipment', EquipmentController::class)->middleware('permission:Equipment');
        Route::post('equipment/{equipment}/image', [EquipmentController::class, 'uploadImage'])
            ->name('equipment.image')
            ->middleware('permission:Equipment,edit');
        Route::get('equipment/{equipment}/maintenance', [MaintenanceRecordController::class, 'forEquipment'])
            ->name('equipment.maintenance')
            ->middleware('permission:Maintenance');

        Route::get('maintenance/upcoming', [MaintenanceRecordController::class, 'upcoming'])
            ->name('maintenance.upcoming')
            ->middleware('permission:Maintenance');
        Route::get('maintenance/overdue', [MaintenanceRecordController::class, 'overdue'])
            ->name('maintenance.overdue')
            ->middleware('permission:Maintenance');
        Route::get('maintenance/stats', [MaintenanceRecordController::class, 'stats'])
            ->name('maintenance.stats')
            ->middleware('permission:Maintenance');
        Route::apiResource('maintenance', MaintenanceRecordController::class)
            ->only(['index', 'store', 'update'])
            ->middleware('permission:Maintenance');
        Route::apiResource('facilities', FacilityController::class)->except(['index']);
        Route::post('facilities/{facility}/image', [FacilityController::class, 'uploadImage'])->name('facilities.image');

        Route::prefix('finance')->name('finance.')->middleware('permission:Reports')->group(function () {
            Route::get('overview', [FinanceController::class, 'overview'])->name('overview');
            Route::get('revenue-over-time', [FinanceController::class, 'revenueOverTime'])->name('revenue-over-time');
        });

        Route::prefix('dashboard')->name('dashboard.')->middleware('permission:Reports')->group(function () {
            Route::get('summary', [DashboardController::class, 'summary'])->name('summary');
            Route::get('activity', [DashboardController::class, 'activity'])->name('activity');
        });

        Route::prefix('analytics')->name('analytics.')->middleware('permission:Reports')->group(function () {
            Route::get('member-growth', [AnalyticsController::class, 'memberGrowth'])->name('member-growth');
            Route::get('attendance-trends', [AnalyticsController::class, 'attendanceTrends'])->name('attendance-trends');
            Route::get('revenue-trends', [AnalyticsController::class, 'revenueTrends'])->name('revenue-trends');
            Route::get('expense-trends', [AnalyticsController::class, 'expenseTrends'])->name('expense-trends');
            Route::get('net-revenue', [AnalyticsController::class, 'netRevenue'])->name('net-revenue');
            Route::get('membership-distribution', [AnalyticsController::class, 'membershipDistribution'])->name('membership-distribution');
            Route::get('revenue-by-method', [AnalyticsController::class, 'revenueByMethod'])->name('revenue-by-method');
            Route::get('class-performance', [AnalyticsController::class, 'classPerformance'])->name('class-performance');
            Route::get('trainer-performance', [AnalyticsController::class, 'trainerPerformance'])->name('trainer-performance');
            Route::get('equipment-stats', [AnalyticsController::class, 'equipmentStats'])->name('equipment-stats');
        });

        Route::prefix('reports')->name('reports.')->middleware('permission:Reports')->group(function () {
            Route::get('members', [ReportController::class, 'members'])->name('members');
            Route::get('memberships', [ReportController::class, 'memberships'])->name('memberships');
            Route::get('attendance', [ReportController::class, 'attendance'])->name('attendance');
            Route::get('revenue', [ReportController::class, 'revenue'])->name('revenue');
            Route::get('expenses', [ReportController::class, 'expenses'])->name('expenses');
            Route::get('trainers', [ReportController::class, 'trainers'])->name('trainers');
            Route::get('classes', [ReportController::class, 'classes'])->name('classes');
            Route::get('equipment', [ReportController::class, 'equipment'])->name('equipment');
        });

        Route::apiResource('invoices', InvoiceController::class)->middleware('permission:Invoices');

        Route::get('payments/stats', [PaymentController::class, 'stats'])
            ->name('payments.stats')
            ->middleware('permission:Payments');
        Route::apiResource('payments', PaymentController::class)->middleware('permission:Payments');
        Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])
            ->name('payments.refund')
            ->middleware('permission:Payments,edit');

        Route::get('expenses/stats', [ExpenseController::class, 'stats'])
            ->name('expenses.stats')
            ->middleware('permission:Expenses');
        Route::apiResource('expenses', ExpenseController::class)->middleware('permission:Expenses');
        Route::post('expenses/{expense}/receipt', [ExpenseController::class, 'uploadReceipt'])
            ->name('expenses.receipt')
            ->middleware('permission:Expenses,edit');

        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
        Route::apiResource('notifications', NotificationController::class)->only(['index', 'update', 'destroy']);

        // Push-notification device registration (Phase 24) — any
        // authenticated user manages their own device(s) only, see
        // DeviceTokenController.
        Route::apiResource('device-tokens', DeviceTokenController::class)->only(['store', 'destroy']);

        // Per-type/per-channel communication preferences + external
        // delivery history (Phase 24 §5/§8) — reuses the existing
        // `Settings` permission module rather than a new one.
        Route::prefix('communication')->name('communication.')->middleware('permission:Settings')->group(function () {
            Route::get('preferences', [NotificationPreferenceController::class, 'index'])->name('preferences.index');
            Route::put('preferences', [NotificationPreferenceController::class, 'update'])->name('preferences.update');
            Route::get('deliveries', [NotificationDeliveryController::class, 'index'])->name('deliveries.index');
        });

        // Phase 30: this module had no permission gate at all — any
        // authenticated staff member (regardless of role) could create,
        // edit, delete, publish, or upload images for announcements.
        // Gated the same way as every other resource now.
        Route::apiResource('announcements', AnnouncementController::class)->middleware('permission:Announcements');
        Route::post('announcements/{announcement}/image', [AnnouncementController::class, 'uploadImage'])
            ->name('announcements.image')
            ->middleware('permission:Announcements,edit');
        Route::post('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])
            ->name('announcements.publish')
            ->middleware('permission:Announcements,edit');
        Route::post('announcements/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])
            ->name('announcements.unpublish')
            ->middleware('permission:Announcements,edit');

        Route::apiResource('staff', StaffController::class)
            ->middleware(['permission:Staff', 'subscription.limit:staff']);
        Route::patch('staff/{staff}/status', [StaffController::class, 'updateStatus'])
            ->name('staff.status')
            ->middleware('permission:Staff,edit');
        Route::patch('staff/{staff}/role', [StaffController::class, 'updateRole'])
            ->name('staff.role')
            ->middleware('permission:Staff,edit');
        Route::post('staff/{staff}/photo', [StaffController::class, 'uploadPhoto'])
            ->name('staff.photo')
            ->middleware('permission:Staff,edit');

        Route::apiResource('roles', RolePermissionController::class)->only(['index', 'update'])->middleware('permission:Settings');

        Route::prefix('settings')->name('settings.')->middleware('permission:Settings')->group(function () {
            Route::get('/', [GymSettingController::class, 'show'])->name('show');
            Route::put('/', [GymSettingController::class, 'update'])->name('update');
            Route::post('logo', [GymSettingController::class, 'uploadLogo'])->name('logo');
        });

        // Every action here operates on the caller's own tenant (never an
        // {id} parameter) — read endpoints are open to any authenticated
        // staff member; changing the subscription itself is restricted to
        // the tenant's own admins, same as the "Gym Admin manages their
        // gym" split described in Phase 18 §5 / Phase 19 §9. SaaS-plan
        // pricing/catalog management is a separate, platform-level concern
        // (see the 'platform' group below), not exposed here at all.
        Route::prefix('subscription')->name('subscription.')->group(function () {
            Route::get('/', [TenantSubscriptionController::class, 'show'])->name('show');
            Route::get('plans', [TenantSubscriptionController::class, 'plans'])->name('plans');
            Route::get('usage', [TenantSubscriptionController::class, 'usage'])->name('usage');
            Route::get('invoices', [TenantSubscriptionController::class, 'invoices'])->name('invoices');

            Route::middleware('role:Super Admin,Admin')->group(function () {
                Route::post('start', [TenantSubscriptionController::class, 'start'])->name('start');
                Route::post('change-plan', [TenantSubscriptionController::class, 'changePlan'])->name('change-plan');
                Route::post('cancel', [TenantSubscriptionController::class, 'cancel'])->name('cancel');
                Route::post('reactivate', [TenantSubscriptionController::class, 'reactivate'])->name('reactivate');
                Route::post('renew', [TenantSubscriptionController::class, 'renew'])->name('renew');

                // Real payment gateway checkout (Phase 23) — activation only
                // ever happens once CheckoutController::verify() (or the
                // Stripe webhook) confirms payment server-side; creating a
                // session here does not touch TenantSubscription at all.
                Route::post('checkout', [CheckoutController::class, 'store'])
                    ->name('checkout.store')
                    ->middleware('throttle:10,1');
                Route::get('checkout/verify', [CheckoutController::class, 'verify'])->name('checkout.verify');
            });
        });

        // AI Layer (Phase 22) — every request here is already scoped to
        // the caller's own tenant by the 'tenant' middleware above, and
        // gated further by the 'AI' permission module (see
        // database/seeders/StaffSeeder.php) and, for anything that
        // actually calls the AI provider, the tenant's plan quota
        // (`ai.limit` — see App\Http\Middleware\EnforceAIUsageLimit).
        // The usage endpoint itself doesn't consume a request, so it's
        // outside `ai.limit` — a tenant at their limit can still see why.
        Route::prefix('ai')->name('ai.')->middleware('throttle:20,1')->group(function () {
            Route::get('usage', [AIUsageController::class, 'show'])
                ->name('usage')
                ->middleware('permission:AI,view');

            Route::middleware('ai.limit')->group(function () {
                Route::post('assistant', [AssistantController::class, 'ask'])
                    ->name('assistant')
                    ->middleware('permission:AI,create');
                Route::get('insights/{domain}', [InsightController::class, 'show'])
                    ->name('insights')
                    ->middleware('permission:AI,view');
                Route::get('members/{member}/insights', [MemberInsightController::class, 'show'])
                    ->name('members.insights')
                    ->middleware('permission:AI,view');
                Route::post('reports/summarize', [ReportAssistantController::class, 'summarize'])
                    ->name('reports.summarize')
                    ->middleware('permission:AI,create');
            });
        });

        // The Flutter Trainer App (Phase 26) — a trainer is a normal
        // staff User (role=Trainer), authenticated via the exact same
        // /auth/login above; this is purely an additional, role-gated URL
        // surface, not a new actor type or auth system. Every endpoint
        // resolves "the caller's own Trainer roster row" via
        // trainers.user_id, so a trainer can only ever see/edit their own
        // data — see Api\Trainer\ProfileController/DashboardController.
        // "My classes"/"assigned members"/"my programs" don't need new
        // list endpoints at all: the existing /classes, /schedule/*,
        // /members, and /training-programs endpoints already accept a
        // ?trainer_id= filter, and the Trainer role already has view
        // permission on those modules (see StaffSeeder).
        Route::prefix('trainer')->name('trainer.')->middleware('role:Trainer')->group(function () {
            Route::get('dashboard', [TrainerDashboardController::class, 'show'])->name('dashboard');
            Route::get('profile', [TrainerProfileController::class, 'show'])->name('profile.show');
            Route::put('profile', [TrainerProfileController::class, 'update'])->name('profile.update');
            Route::post('profile/photo', [TrainerProfileController::class, 'uploadPhoto'])->name('profile.photo');
        });
    });

    // The Flutter Member Mobile App (Phase 25) — a completely separate
    // authenticated actor (App\Models\Member, not App\Models\User), never
    // able to reach the staff-only routes above or vice versa. `tenant`
    // still applies (Member has a tenant_id, see ResolveTenant), so every
    // query below is automatically scoped to the member's own gym; every
    // controller additionally scopes to $request->user()->id so a member
    // can only ever see their own data, never another member's.
    Route::prefix('member')->name('member.')->group(function () {
        Route::post('auth/login', [MemberAuthController::class, 'login'])
            ->middleware('throttle:6,1')
            ->name('auth.login');

        Route::middleware(['auth:sanctum', 'member.guard', 'tenant'])->group(function () {
            Route::post('auth/logout', [MemberAuthController::class, 'logout'])->name('auth.logout');
            Route::get('auth/me', [MemberAuthController::class, 'me'])->name('auth.me');

            Route::get('dashboard', [MemberDashboardController::class, 'show'])->name('dashboard');

            Route::get('membership', [MemberMembershipController::class, 'current'])->name('membership.show');
            Route::get('membership/history', [MemberMembershipController::class, 'history'])->name('membership.history');

            Route::get('attendance', [MemberAttendanceController::class, 'index'])->name('attendance.index');
            Route::get('attendance/summary', [MemberAttendanceController::class, 'summary'])->name('attendance.summary');

            Route::get('qr', [MemberQrController::class, 'show'])->name('qr.show');
            Route::post('qr/regenerate', [MemberQrController::class, 'regenerate'])
                ->name('qr.regenerate')
                ->middleware('throttle:5,1');

            Route::get('classes', [MemberClassController::class, 'index'])->name('classes.index');
            Route::get('classes/my-bookings', [MemberClassController::class, 'myBookings'])->name('classes.my-bookings');
            Route::get('classes/{class}', [MemberClassController::class, 'show'])->name('classes.show');
            Route::post('classes/{class}/book', [MemberClassController::class, 'book'])->name('classes.book');
            Route::delete('classes/{class}/book', [MemberClassController::class, 'cancel'])->name('classes.cancel');

            Route::get('trainers', [MemberTrainerController::class, 'index'])->name('trainers.index');
            Route::get('trainers/{trainer}', [MemberTrainerController::class, 'show'])->name('trainers.show');

            Route::get('programs', [MemberProgramController::class, 'index'])->name('programs.index');
            Route::get('programs/mine', [MemberProgramController::class, 'mine'])->name('programs.mine');
            Route::get('programs/{program}', [MemberProgramController::class, 'show'])->name('programs.show');

            Route::get('payments', [MemberPaymentController::class, 'index'])->name('payments.index');
            Route::get('invoices', [MemberInvoiceController::class, 'index'])->name('invoices.index');
            Route::get('invoices/{invoice}', [MemberInvoiceController::class, 'show'])->name('invoices.show');

            Route::get('notifications', [MemberNotificationController::class, 'index'])->name('notifications.index');
            Route::patch('notifications/{notification}/read', [MemberNotificationController::class, 'markRead'])->name('notifications.read');
            Route::post('notifications/mark-all-read', [MemberNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

            Route::get('profile', [MemberProfileController::class, 'show'])->name('profile.show');
            Route::put('profile', [MemberProfileController::class, 'update'])->name('profile.update');
            Route::post('profile/photo', [MemberProfileController::class, 'uploadPhoto'])->name('profile.photo');

            // Reuses the same device-token endpoint the staff dashboard uses (Phase 24) — see DeviceTokenController.
            Route::post('device-tokens', [DeviceTokenController::class, 'store'])->name('device-tokens.store');
            Route::delete('device-tokens/{device_token}', [DeviceTokenController::class, 'destroy'])->name('device-tokens.destroy');
        });
    });

    // Platform-level routes: the Super Admin platform dashboard (Phase 21)
    // and the SaaS's own pricing catalog — never any single gym's tenant-
    // scoped data. Gated by `platform.admin`, not the tenant/role/
    // permission machinery above — see App\Http\Middleware\EnsurePlatformAdmin.
    Route::prefix('platform')->name('platform.')->middleware(['auth:sanctum', 'platform.admin'])->group(function () {
        Route::apiResource('subscription-plans', PlatformSubscriptionPlanController::class);

        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('summary', [PlatformDashboardController::class, 'summary'])->name('summary');
            Route::get('charts', [PlatformDashboardController::class, 'charts'])->name('charts');
            Route::get('activity', [PlatformDashboardController::class, 'activity'])->name('activity');
        });

        Route::get('gyms', [PlatformGymController::class, 'index'])->name('gyms.index');
        Route::post('gyms', [PlatformGymController::class, 'store'])->name('gyms.store');
        Route::get('gyms/{gym}', [PlatformGymController::class, 'show'])->name('gyms.show');
        Route::put('gyms/{gym}', [PlatformGymController::class, 'update'])->name('gyms.update');
        Route::post('gyms/{gym}/activate', [PlatformGymController::class, 'activate'])->name('gyms.activate');
        Route::post('gyms/{gym}/suspend', [PlatformGymController::class, 'suspend'])->name('gyms.suspend');
        Route::post('gyms/{gym}/reactivate', [PlatformGymController::class, 'reactivate'])->name('gyms.reactivate');

        Route::get('subscriptions', [PlatformSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('billing', [PlatformBillingController::class, 'index'])->name('billing.index');

        // Payment transaction ledger, revenue stats, and refunds (Phase 23)
        // — distinct from 'billing' above (SubscriptionInvoice rows).
        Route::get('billing/transactions', [PlatformPaymentController::class, 'index'])->name('billing.transactions.index');
        Route::get('billing/stats', [PlatformPaymentController::class, 'stats'])->name('billing.stats');
        Route::post('billing/transactions/{payment_transaction}/refund', [PlatformPaymentController::class, 'refund'])
            ->name('billing.transactions.refund');

        Route::get('users', [PlatformUserController::class, 'index'])->name('users.index');
        Route::post('users', [PlatformUserController::class, 'store'])->name('users.store');
        Route::patch('users/{platform_user}/status', [PlatformUserController::class, 'updateStatus'])->name('users.status');

        Route::get('audit-logs', [PlatformAuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('communication/status', [PlatformCommunicationController::class, 'status'])->name('communication.status');
    });
});
