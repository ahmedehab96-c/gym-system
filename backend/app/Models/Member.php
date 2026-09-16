<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

/**
 * A gym's client, and — since Phase 25 (the Flutter Member Mobile App) —
 * also a second Sanctum-authenticatable entity alongside User. Extending
 * Authenticatable (rather than plain Model) is what makes a Member a
 * valid `tokenable` for a personal access token; every existing
 * staff-facing use of Member (CRUD, relations, resources) is unaffected,
 * since Authenticatable is itself an Eloquent model. `password` is
 * deliberately NOT fillable — set only via App\Http\Controllers\Api\Member\AuthController,
 * never through the staff-facing member-update endpoint.
 */
#[Fillable([
    'member_id', 'name', 'avatar', 'gender', 'phone', 'email', 'address', 'dob',
    'join_date', 'plan_id', 'trainer_id', 'start_date', 'expiry_date', 'status',
    'attendance_rate', 'balance_due', 'emergency_contact',
])]
#[Hidden(['password', 'remember_token', 'qr_token', 'qr_token_hash'])]
class Member extends Authenticatable
{
    /** @use HasFactory<MemberFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory;

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'join_date' => 'date',
            'start_date' => 'date',
            'expiry_date' => 'date',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'qr_token' => 'encrypted',
            'qr_token_issued_at' => 'datetime',
            'qr_token_expires_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'plan_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(MemberNote::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function personalTrainingSession(): HasOne
    {
        return $this->hasOne(PersonalTrainingSession::class);
    }

    public function classBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class);
    }

    public function gymClasses(): BelongsToMany
    {
        return $this->belongsToMany(GymClass::class, 'class_bookings', 'member_id', 'class_id')
            ->withPivot('booked_at');
    }

    public function trainingPrograms(): BelongsToMany
    {
        return $this->belongsToMany(TrainingProgram::class, 'program_enrollments')
            ->withPivot('enrolled_at');
    }

    /**
     * Defensive stubs, always false: staff-only middleware (CheckPermission,
     * EnsurePlatformAdmin) calls $request->user()->hasPermission(...)/
     * ->isPlatformAdmin() polymorphically, with no instanceof check, since
     * User used to be the only Authenticatable in the app. Now that Member
     * is a second one (Phase 25), a member's token hitting a staff-only
     * route would otherwise crash on a missing method instead of cleanly
     * being denied — a member is never staff and never a platform admin,
     * so "always false" is also the semantically correct answer.
     */
    public function hasPermission(string $module, string $ability): bool
    {
        return false;
    }

    public function isPlatformAdmin(): bool
    {
        return false;
    }

    /**
     * Issues a fresh QR check-in token, invalidating whatever was there
     * before (Phase 28 §1 "regeneration"). The raw value is returned only
     * here — to the member's own self-service endpoint that calls this —
     * and is never reconstructable from `qr_token_hash` alone, which is
     * all a front-desk scan ever resolves against.
     */
    public function issueQrToken(): string
    {
        $raw = Str::random(48);

        $this->forceFill([
            'qr_token' => $raw,
            'qr_token_hash' => hash('sha256', $raw),
            'qr_token_issued_at' => now(),
            'qr_token_expires_at' => now()->addDays((int) config('gym.qr_token_ttl_days', 180)),
        ])->save();

        return $raw;
    }

    /**
     * Revokes the current QR token outright (Phase 28 §1 "revocation") —
     * a subsequent scan of the old code resolves to nothing, exactly like
     * an unissued one.
     */
    public function revokeQrToken(): void
    {
        $this->forceFill([
            'qr_token' => null,
            'qr_token_hash' => null,
            'qr_token_issued_at' => null,
            'qr_token_expires_at' => null,
        ])->save();
    }

    /**
     * The only lookup a front-desk scan ever performs — by hash, never by
     * decrypting `qr_token`, so resolving a scan never needs the raw
     * value at all. Inherits TenantScope like every other query on this
     * model, so a QR issued by one gym's member can never resolve inside
     * another tenant's scan (Phase 28 §7 "prevent cross-tenant QR usage").
     */
    public static function findByQrTokenHash(string $rawToken): ?self
    {
        return static::query()->where('qr_token_hash', hash('sha256', $rawToken))->first();
    }
}
