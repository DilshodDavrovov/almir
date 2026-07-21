<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSettings extends Model
{
    use HasFactory;
    public $timestamps = true;
     
    protected $fillable = [
        'app_name',
        'support_email',
        'description',
        'contact_phone',
        'contact_fax',
        'contact_address',
        'app_version',
        'referent_cost_file',
        'reg_cost_glc_file',
        'customer_cost_file'
    ];
}
