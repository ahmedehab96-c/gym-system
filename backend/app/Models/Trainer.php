<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TrainerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * user_id links this roster entry to the staff User account that logs
 * in as this trainer (Phase 26 — the Flutter Trainer App). Nullable —
 * see the migration docblock for why neither side requires the other.
 */
#[Fillable(['user_id', 'name', 'photo', 'specialty', 'specialties', 'experience', 'phone', 'email', 'bio', 'status', 'rating', 'sessions_completed', 'schedule'])]
class Trainer extends Model
{
    /** @use HasFactory<TrainerFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'schedule' => 'array',
            'rating' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'trainer_id');
    }

    public function trainingPrograms(): HasMany
    {
        return $this->hasMany(TrainingProgram::class, 'trainer_id');
    }

    public function gymClasses(): HasMany
    {
        return $this->hasMany(GymClass::class, 'trainer_id');
    }

    public function personalTrainingSessions(): HasMany
    {
        return $this->hasMany(PersonalTrainingSession::class, 'trainer_id');
    }
}
