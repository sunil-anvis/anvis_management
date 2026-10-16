<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\Branch;
use App\Models\Status;
use App\Mail\DomainExpiryReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class NotifyDomainExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-domain-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify managers and employees about domains expiring in 30, 15, and 7 days, and update domain statuses.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        // Fetch statuses
        $activeStatus   = Status::where('name', 'Active')->first();
        $expiringStatus = Status::where('name', 'Expiring')->first();
        $expiredStatus  = Status::where('name', 'Expire')->first();

        // 1. Mark Expired: expiry_date < today
        Domain::where('expiry_date', '<', $today->toDateString())
            ->update(['status_id' => $expiredStatus->id]);

        // 2. Mark Expiring: today <= expiry_date <= today + 30 days
        Domain::whereBetween('expiry_date', [
            $today->toDateString(),
            $today->copy()->addDays(30)->toDateString(),
        ])->update(['status_id' => $expiringStatus->id]);

        // 3. Mark Active: expiry_date > today + 30 days
        Domain::where('expiry_date', '>', $today->copy()->addDays(30)->toDateString())
            ->update(['status_id' => $activeStatus->id]);

        $this->info('Domain statuses updated.');

        // 4. Send email notifications for domains expiring in 30, 15, and 7 days
        $intervals = [30, 15, 7];

        foreach ($intervals as $days) {
            $expiryDate = $today->copy()->addDays($days)->toDateString();
            $domains = Domain::where('expiry_date', $expiryDate)->get();

            if ($domains->isEmpty()) {
                $this->info("No domains expiring in exactly {$days} days.");
                continue;
            }

            foreach ($domains as $domain) {
                $branch = $domain->branch;
                if (!$branch) continue;

                $recipients = collect();
                $branch->managers->each(fn($m) => $recipients->push($m->email));
                $branch->employees->each(fn($e) => $recipients->push($e->email));

                $recipientList = $recipients->unique()->filter()->toArray();

                if (!empty($recipientList)) {
                    Mail::to($recipientList)->send(new DomainExpiryReminder($domain, $days));
                    $this->info("Sent {$days}-day expiry reminder for {$domain->name}");
                }
            }
        }

        $this->info('Expiry notifications and status updates completed.');
    }
}
