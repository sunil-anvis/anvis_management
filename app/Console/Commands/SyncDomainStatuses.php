<?php

namespace App\Console\Commands;

use App\Models\Domain;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncDomainStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:sync-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync system_status for all domains based on their expiry date.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $today = Carbon::today();

        $expired = Domain::where('expiry_date', '<', $today->toDateString())
            ->update(['system_status' => 'Expired']);

        $inProgress = Domain::whereBetween('expiry_date', [
            $today->toDateString(),
            $today->copy()->addDays(7)->toDateString(),
        ])->update(['system_status' => 'Renewal in Progress']);

        $pending = Domain::whereBetween('expiry_date', [
            $today->copy()->addDays(8)->toDateString(),
            $today->copy()->addDays(15)->toDateString(),
        ])->update(['system_status' => 'Pending Renewal']);

        $expiring = Domain::whereBetween('expiry_date', [
            $today->copy()->addDays(16)->toDateString(),
            $today->copy()->addDays(30)->toDateString(),
        ])->update(['system_status' => 'Expiring soon']);

        $active = Domain::where('expiry_date', '>', $today->copy()->addDays(30)->toDateString())
            ->update(['system_status' => 'Active']);

        $total = $expired + $inProgress + $pending + $expiring + $active;

        $this->info("Domain status sync complete:");
        $this->line("  • Expired             : {$expired}");
        $this->line("  • Renewal in Progress : {$inProgress}");
        $this->line("  • Pending Renewal     : {$pending}");
        $this->line("  • Expiring soon       : {$expiring}");
        $this->line("  • Active              : {$active}");
        $this->line("  • Total updated       : {$total}");
    }
}
