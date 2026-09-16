<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expense */
class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'amount' => $this->amount,
            'date' => $this->date,
            'vendor' => $this->vendor,
            'notes' => $this->notes,
            'receipt' => $this->receipt,
        ];
    }
}
