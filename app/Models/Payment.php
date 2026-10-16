<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\PaymentAddedMail;

class Payment extends Model
{
    protected $fillable = [
        'domain_id',
        'amount',
        'payment_date',
        'payment_status_id',
    ];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function status()
    {
        return $this->belongsTo(PaymentStatus::class, 'payment_status_id');
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            try {
                $payment->load('domain');
                $adminEmails = User::getAdminEmails();
                $managerEmails = User::getManagerEmailsByBranch($payment->domain->branch_id ?? null);
                $employeeEmails = User::getEmployeeEmailById($payment->domain->sales_person_id ?? null);

                $emails = array_unique(array_filter(array_merge($adminEmails, $managerEmails, $employeeEmails)));

                if (!empty($emails)) {
                    Mail::to($emails)->send(new PaymentAddedMail($payment));
                    Log::info('PaymentAddedMail successfully sent to: ' . implode(', ', $emails));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send PaymentAddedMail: ' . $e->getMessage());
            }
        });
    }
}
