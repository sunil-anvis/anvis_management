<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Traits\FormatsDates;

class UserToken extends Model
{
    use HasFactory, FormatsDates;

    protected $fillable = [
        'user_id',
        'token',
        'ip_address',
        'is_revoked',
        'expiry_time'
    ];

    protected $casts = [
        'is_revoked' => 'boolean',
        'expiry_time' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
