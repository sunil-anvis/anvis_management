<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

$users = DB::table('users')->get();
echo "Total users in DB: " . $users->count() . PHP_EOL;

foreach ($users as $user) {
    try {
        Hash::check('test', $user->password);
    } catch (\Throwable $e) {
        echo "ERROR for user ID {$user->id} ({$user->username} / {$user->email}): " . $e->getMessage() . PHP_EOL;
        echo "Password value: [" . $user->password . "]" . PHP_EOL;
    }
}

echo "Check complete." . PHP_EOL;
