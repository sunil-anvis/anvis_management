<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\ActivityLog;
use Illuminate\Console\Command;

class SendDomainReminders extends Command
{
    protected $signature   = 'app:send-domain-reminders';
    protected $description = 'Log/send reminders for domains expiring in 30, 15, 7, or 1 day(s)';

    public function handle(): void
    {
        $thresholds = [30, 15, 7, 1];

        foreach ($thresholds as $days) {
            $domains = Domain::whereDate(
                'expiry_date',
                now()->addDays($days)->toDateString()
            )->get();

            foreach ($domains as $domain) {
                // Log the reminder
                ActivityLog::create([
                    'domain_id' => $domain->id,
                    'user_id'   => null, // system-generated
                    'action'    => 'reminder_sent',
                    'changes'   => [
                        'days_to_expiry' => $days,
                        'expiry_date'    => $domain->expiry_date,
                        'system_status'  => $domain->system_status,
                    ],
                ]);

                // TODO: Replace with actual email/notification
                $this->info("Reminder: Domain [{$domain->name}] expires in {$days} day(s) on {$domain->expiry_date}.");
            }
        }

        $this->info('Domain reminders processed successfully.');
    }
}
