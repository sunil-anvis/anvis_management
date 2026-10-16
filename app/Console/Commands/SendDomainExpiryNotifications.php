<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDomainExpiryNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-domain-expiry-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send domain expiry notification emails for 30, 15, 7, and 1 days before expiry';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysToNotify = [30, 15, 7, 1];
        $adminEmails = \App\Models\User::getAdminEmails();

        foreach ($daysToNotify as $days) {
            $date = \Carbon\Carbon::now()->addDays($days)->format('Y-m-d');
            
            $domains = \App\Models\Domain::whereDate('expiry_date', $date)->get();
            
            foreach ($domains as $domain) {
                try {
                    $managerEmails = \App\Models\User::getManagerEmailsByBranch($domain->branch_id ?? null);
                    $employeeEmails = \App\Models\User::getEmployeeEmailById($domain->sales_person_id ?? null);

                    $emails = array_unique(array_filter(array_merge($adminEmails, $managerEmails, $employeeEmails)));

                    if (!empty($emails)) {
                        \Illuminate\Support\Facades\Mail::to($emails)->send(new \App\Mail\DomainExpiryMail($domain, $days));
                        $this->info("Notification sent for domain: {$domain->name} ({$days} days left)");
                        \Illuminate\Support\Facades\Log::info("DomainExpiryMail successfully sent to: " . implode(', ', $emails) . " for domain {$domain->name}");
                    } else {
                        $this->warn("No emails found to notify for domain: {$domain->name}");
                        \Illuminate\Support\Facades\Log::warning("No emails found to notify for domain: {$domain->name}");
                    }
                } catch (\Exception $e) {
                    $this->error("Failed to send expiry mail for domain {$domain->name}");
                    \Illuminate\Support\Facades\Log::error("Failed to send expiry mail for domain {$domain->name}: " . $e->getMessage());
                }
            }
        }
    }
}
