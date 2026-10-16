<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            'mumbai',
            'ahemdabad',
            'delhi',
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(['name' => $branch]);
        }
    }
}
