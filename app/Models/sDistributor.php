<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sDistributor extends Model
{
    use HasFactory;
    public $timestamps = false;
     protected $table = 'distributor';
    // protected $fillable = [
    //     'name',
    //     'counter'
    // ];
}
