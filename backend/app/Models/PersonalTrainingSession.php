<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PersonalTrainingSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['member_id', 'trainer_id', 'goal', 'sessions_per_week'])]
class PersonalTrainingSession extends Model
{
    /** @use HasFactory<PersonalTrainingSessionFactory> */
    use BelongsToTenant, HasFactory;

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }
}
