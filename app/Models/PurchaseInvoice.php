<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'amount',
        'payment_date',
        'status',
        'invoice_file_path',
        'company_name',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getInvoiceFileUrlAttribute()
    {
        $statusLower = strtolower(trim($this->status ?? ''));
        $isCompleted = in_array($statusLower, ['completed', 'processed', 'approved']);

        if ($isCompleted && $this->invoice_file_path) {
            $hasFile = file_exists(storage_path('app/public/' . $this->invoice_file_path))
                    || file_exists(storage_path('app/public_storage/' . $this->invoice_file_path));

            if ($hasFile) {
                return asset('public_storage/' . $this->invoice_file_path);
            }
        }

        return null;
    }
}
