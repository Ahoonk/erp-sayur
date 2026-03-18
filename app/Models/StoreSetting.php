<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'logo',
        'footer_text',
        'bank_name',
        'bank_account',
        'bank_account_name',
        'qris_image',
        'signature_name',
        'service_terms',
    ];
}
