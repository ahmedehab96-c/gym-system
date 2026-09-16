<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['gym_class_id', 'for_date'])]
class ClassReminderLog extends Model
{
    protected function casts(): array
    {
        return ['for_date' => 'date'];
    }
}
