<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TrainingProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'image', 'duration', 'difficulty', 'trainer_id', 'status'])]
class TrainingProgram extends Model
{
    /** @use HasFactory<TrainingProgramFactory> */
    use BelongsToTenant, HasFactory;

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ProgramEnrollment::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'program_enrollments')->withPivot('enrolled_at');
    }
}
