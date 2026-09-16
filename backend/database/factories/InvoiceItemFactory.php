<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'description' => $this->faker->randomElement(['Monthly Membership Fee', 'Personal Training Session', 'Locker Rental', 'Guest Pass']),
            'amount' => $this->faker->numberBetween(100, 950),
            'sort_order' => 0,
        ];
    }
}
