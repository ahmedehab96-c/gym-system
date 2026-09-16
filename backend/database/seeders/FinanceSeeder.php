<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $members = Member::all();

        foreach ($members->random(20) as $member) {
            $invoice = Invoice::factory()->create(['member_id' => $member->id]);

            $itemCount = fake()->numberBetween(1, 3);
            $total = 0;

            for ($i = 0; $i < $itemCount; $i++) {
                $item = InvoiceItem::factory()->create([
                    'invoice_id' => $invoice->id,
                    'sort_order' => $i,
                ]);
                $total += $item->amount;
            }

            $invoice->update(['total' => $total]);
        }

        foreach ($members->random(30) as $member) {
            Payment::factory()->create(['member_id' => $member->id]);
        }

        Expense::factory()->count(24)->create();
    }
}
