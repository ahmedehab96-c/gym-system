<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\GymClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'trainer_id', 'date', 'day', 'start_time', 'end_time', 'capacity', 'status', 'color'])]
class GymClass extends Model
{
    /** @use HasFactory<GymClassFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'gym_classes';

    protected $appends = ['booked'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class, 'class_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'class_bookings', 'class_id', 'member_id')
            ->withPivot('booked_at');
    }

    public function getBookedAttribute(): int
    {
        return $this->bookings()->count();
    }
}
