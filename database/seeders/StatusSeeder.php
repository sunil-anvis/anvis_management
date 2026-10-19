<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::create(['name' => 'Active']);
        Status::create(['name' => 'Expiring soon']);
        Status::create(['name' => 'Expired']);
        Status::create(['name' => 'Pending Renewal']);
        Status::create(['name' => 'Renewal in Progress']);
        Status::create(['name' => 'Renewed']);
        Status::create(['name' => 'On Hold']);
        Status::create(['name' => 'Cancelled']);
    }
}
