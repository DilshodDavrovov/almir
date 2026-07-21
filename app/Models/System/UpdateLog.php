<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    use HasFactory;

    public $table = "update_log";

    public $timestamps = true;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'user_id',
        'updated_date',
        'is_active',
        'deleted'
    ];
    protected $casts = [
        'updated_date' => 'datetime:d-m-Y',
        'created_at' => 'datetime:Y-m-d | H:m',
        'updated_at' => 'datetime:Y-m-d | H:m',
        'is_active' => 'boolean',
        'deleted' => 'boolean'
    ];
}
