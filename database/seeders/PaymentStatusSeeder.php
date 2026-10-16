<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentStatus;

class PaymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'Not Received',
            'Partially Received',
            'Received',
        ];

        foreach ($statuses as $status) {
            PaymentStatus::updateOrCreate(['name' => $status]);
        }
    }
}
