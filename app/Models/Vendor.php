<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'vendor_name',
        'vendor_address',
        'vendor_gst_no',
        'vendor_contact',
        'vendor_email'
    ];
}
