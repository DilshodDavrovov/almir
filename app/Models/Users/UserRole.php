<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory;
    
    protected $table = 'users_roles';
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'x_roles_id'
    ];

    public $timestamps = false;

    public static function scopeIsAdmin($id) {
        $isAdmin = self::query()->where(['user_id' => $id, 'x_roles_id' => 1])->first();
        if ($isAdmin) {
            return true;
        }
        return false;
    }

    public static function scopeIsEmp($id)
    {
        $isEmp = self::query()->where(['user_id' => $id, 'x_roles_id' => 2])->first();
        if ($isEmp) {
            return true;
        }
        return false;
    }
}
