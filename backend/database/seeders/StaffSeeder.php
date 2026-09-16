<?php

namespace Database\Seeders;

use App\Models\RolePermission;
use App\Models\User;
use App\Support\PermissionModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StaffSeeder extends Seeder
{
    private const MODULES = PermissionModule::ALL;

    public function run(): void
    {
        $staff = [
            ['name' => 'Ahmed Ehab', 'photo' => 68, 'role' => 'Super Admin', 'email' => 'ahmed.ehab@premiumgym.com', 'phone' => '+20 100 000 0001', 'status' => 'Active', 'hoursAgo' => 1],
            ['name' => 'Yara Mansour', 'photo' => 25, 'role' => 'Admin', 'email' => 'yara.mansour@premiumgym.com', 'phone' => '+20 100 000 0002', 'status' => 'Active', 'hoursAgo' => 3],
            ['name' => 'Mostafa Reda', 'photo' => 15, 'role' => 'Manager', 'email' => 'mostafa.reda@premiumgym.com', 'phone' => '+20 100 000 0003', 'status' => 'Active', 'hoursAgo' => 5],
            ['name' => 'Salma Adly', 'photo' => 29, 'role' => 'Receptionist', 'email' => 'salma.adly@premiumgym.com', 'phone' => '+20 100 000 0004', 'status' => 'Active', 'hoursAgo' => 2],
            ['name' => 'Karim Adel', 'photo' => 12, 'role' => 'Trainer', 'email' => 'karim.adel@premiumgym.com', 'phone' => '+20 100 111 2233', 'status' => 'Active', 'hoursAgo' => 6],
            ['name' => 'Nourhan Sami', 'photo' => 32, 'role' => 'Trainer', 'email' => 'nourhan.sami@premiumgym.com', 'phone' => '+20 101 222 3344', 'status' => 'Active', 'hoursAgo' => 8],
            ['name' => 'Hany Fekry', 'photo' => 53, 'role' => 'Accountant', 'email' => 'hany.fekry@premiumgym.com', 'phone' => '+20 100 000 0007', 'status' => 'Inactive', 'hoursAgo' => 96],
            ['name' => 'Rania Sobhy', 'photo' => 44, 'role' => 'Receptionist', 'email' => 'rania.sobhy@premiumgym.com', 'phone' => '+20 100 000 0008', 'status' => 'Active', 'hoursAgo' => 12],
        ];

        foreach ($staff as $s) {
            User::factory()->create([
                'name' => $s['name'],
                'photo' => "https://i.pravatar.cc/200?img={$s['photo']}",
                'email' => $s['email'],
                'phone' => $s['phone'],
                'role' => $s['role'],
                'status' => $s['status'],
                'password' => 'password',
                'last_login_at' => Carbon::now()->subHours($s['hoursAgo']),
            ]);
        }

        $this->seedRolePermissions();
    }

    private function seedRolePermissions(): void
    {
        $roles = ['Super Admin', 'Admin', 'Manager', 'Receptionist', 'Trainer', 'Accountant'];

        foreach ($roles as $role) {
            foreach (self::MODULES as $module) {
                // updateOrCreate, not factory()->create(): the 'AI' module
                // is already backfilled for every role by the
                // add_ai_module_role_permissions migration, which runs
                // before this seeder on a fresh `migrate --seed` — a
                // plain create() collides with that row's unique
                // (role, module) constraint (Phase 30 audit finding).
                RolePermission::query()->updateOrCreate(
                    ['role' => $role, 'module' => $module],
                    $this->permissionsFor($role, $module),
                );
            }
        }
    }

    private function permissionsFor(string $role, string $module): array
    {
        return match ($role) {
            'Super Admin' => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true],
            'Admin' => ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => $module !== 'Settings'],
            'Manager' => [
                'can_view' => true,
                'can_create' => ! in_array($module, ['Settings', 'Staff'], true),
                'can_edit' => ! in_array($module, ['Settings', 'Staff'], true),
                'can_delete' => false,
            ],
            'Receptionist' => [
                'can_view' => in_array($module, ['Members', 'Memberships', 'Attendance', 'Payments', 'Invoices'], true),
                'can_create' => in_array($module, ['Members', 'Attendance'], true),
                'can_edit' => in_array($module, ['Members', 'Attendance'], true),
                'can_delete' => false,
            ],
            'Trainer' => [
                'can_view' => in_array($module, ['Members', 'Attendance', 'Classes', 'Trainers'], true),
                'can_create' => false,
                'can_edit' => false,
                'can_delete' => false,
            ],
            'Accountant' => [
                'can_view' => in_array($module, ['Payments', 'Invoices', 'Expenses', 'Reports', 'AI'], true),
                'can_create' => in_array($module, ['Invoices', 'Expenses', 'AI'], true),
                'can_edit' => in_array($module, ['Invoices', 'Expenses'], true),
                'can_delete' => false,
            ],
            default => ['can_view' => false, 'can_create' => false, 'can_edit' => false, 'can_delete' => false],
        };
    }
}
