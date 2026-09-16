<?php

namespace App\Http\Resources;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionPlan */
class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'monthlyPrice' => $this->monthly_price,
            'yearlyPrice' => $this->yearly_price,
            'trialDays' => $this->trial_days,
            'features' => $this->features ?? [],
            'limits' => $this->limits,
            'status' => $this->status,
            'sortOrder' => $this->sort_order,
        ];
    }
}
