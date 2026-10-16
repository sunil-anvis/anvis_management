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
        if ($this->invoice_file_path) {
            return asset('storage/' . $this->invoice_file_path);
        }
        return null;
    }
}
