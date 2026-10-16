<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\DomainCreatedMail;

class Domain extends Model
{
    protected $fillable = [
        'name',
        'company_name',
        'booking_date',
        'expiry_date',
        'total_amount',
        'sales_person_id',
        'system_status',
        'manual_status',
        'last_contacted_at',
        'next_followup_at',
        'renewal_date',
        'renewed_by',
        'remark',
        'branch_id',
    ];

    protected $appends = ['payment_summary', 'days_to_expiry', 'priority'];

    protected static function booted()
    {
        static::saving(function ($domain) {
            if ($domain->expiry_date) {
                $expiry = Carbon::parse($domain->expiry_date)->startOfDay();
                $now = Carbon::now()->startOfDay();
                $diff = $now->diffInDays($expiry, false);

                if ($diff < 0) {
                    $domain->system_status = 'Expired';
                } elseif ($diff <= 7) {
                    $domain->system_status = 'Renewal in Progress';
                } elseif ($diff <= 15) {
                    $domain->system_status = 'Pending Renewal';
                } elseif ($diff <= 30) {
                    $domain->system_status = 'Expiring soon';
                } else {
                    $domain->system_status = 'Active';
                }
            }
        });

        static::created(function ($domain) {
            $userId = request()->attributes->get('user')?->id ?? auth()->id();

            ActivityLog::create([
                'domain_id' => $domain->id,
                'user_id'   => $userId,
                'action'    => 'created',
                'changes'   => $domain->toArray(),
            ]);

            try {
                $adminEmails = User::getAdminEmails();
                $managerEmails = User::getManagerEmailsByBranch($domain->branch_id ?? null);
                $employeeEmails = User::getEmployeeEmailById($domain->sales_person_id ?? null);

                $emails = array_unique(array_filter(array_merge($adminEmails, $managerEmails, $employeeEmails)));

                if (!empty($emails)) {
                    Mail::to($emails)->send(new DomainCreatedMail($domain));
                    Log::info('DomainCreatedMail successfully sent to: ' . implode(', ', $emails));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send DomainCreatedMail: ' . $e->getMessage());
            }
        });

        static::updated(function ($domain) {
            $userId = request()->attributes->get('user')?->id ?? auth()->id();
            $changes = $domain->getChanges();
            unset($changes['updated_at']);

            if (!empty($changes)) {
                ActivityLog::create([
                    'domain_id' => $domain->id,
                    'user_id'   => $userId,
                    'action'    => 'updated',
                    'changes'   => [
                        'before' => array_intersect_key($domain->getOriginal(), $changes),
                        'after'  => $changes,
                    ],
                ]);
            }
        });

        static::deleted(function ($domain) {
            $userId = request()->attributes->get('user')?->id ?? auth()->id();

            ActivityLog::create([
                'domain_id' => null,
                'user_id'   => $userId,
                'action'    => 'deleted',
                'changes'   => ['domain_name' => $domain->name, 'domain_id' => $domain->id],
            ]);
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function salesPerson()
    {
        return $this->belongsTo(User::class, 'sales_person_id');
    }

    public function renewedBy()
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function getPaymentSummaryAttribute()
    {
        $totalPaid = $this->payments()->sum('amount');
        return [
            'total_amount' => (float)$this->total_amount,
            'total_paid' => (float)$totalPaid,
            'balance_pending' => (float)($this->total_amount - $totalPaid),
        ];
    }

    public function getDaysToExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }
        $expiry = Carbon::parse($this->expiry_date)->startOfDay();
        $now = Carbon::now()->startOfDay();
        return (int)$now->diffInDays($expiry, false);
    }

    public function getPriorityAttribute()
    {
        $days = $this->days_to_expiry;
        if ($days === null) {
            return 'Low';
        }

        if ($days < 0) {
            return 'Critical'; // Expired
        } elseif ($days <= 7) {
            return 'High';
        } elseif ($days <= 30) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }
}
