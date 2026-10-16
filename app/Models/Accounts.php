<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accounts extends Model
{
    protected $fillable = [
        'ro_form_id',
        'bill_name',
        'anvis_invoice',
        'anvis_invoice_date',
        'anvis_status',
        'vendor_invoice',
        'vendor_invoice_date',
        'vendor_status',
        'external_amount',
        'executive',
        'comments'
    ];

    public function roForm(){

        return $this->belongsTo(RoForm::class);
    }
}
