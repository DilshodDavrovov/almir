<?php

namespace App\Models;

use App\Models\Drugs\DrugType;
use App\Models\Users\UserAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasMany;
//For importing traits form package
use MIMAXUZ\LRoles\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'company_name',
        'company_inn',
        'phone_number',
        'um_created_at',
        'um_expired_at',
        'one_time_mac',
        'otm_created_at',
        'passport_info',
        'address',
        'user_mac',
        'email',
        'password',
        'browserName',
        'platform',
        'browserLanguage',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        'is_blocked' => 'boolean',
        'confirmed' => 'boolean'
    ];

    
    /**
     * Active STATUS
     * @var int ID
     * @var bool Status
     */

    public static function scopeActiveDeactivate($id, $status)
    {
        //deactivate if is equal 1
        if (!empty($id) && $status == '1') {
            self::query()->where([
                ['id', $id],
                //['is_active', "!=", 1]
            ])->update(['is_active' => 1]);
        } else {
            //else activate it if id equal 0
            self::query()->where('id', $id) ->update(['is_active' => 0]);
        }
        return self::where([
                ['id', $id]
            ])->first();
    }

    /**
     * DELETED STATUS
     * @var int ID
     * @var bool Status
     */
    public static function scopeDeleteIt($id, $isActive, $isDeleted)
    {
        //delete or active status for data
        if ($id && $isActive * $isDeleted != 1) {
            self::query()->where(['id' => $id])->update(['is_deleted' => $isDeleted, 'is_active' => $isActive]);
        } else {
            return null;
        }
        return self::where(['id' => $id])->first();
    }


    /**
     * Active STATUS
     * @var array ID
     * @var bool Status
     */

    public static function scopeActiveDeactivateList($id, $status)
    {
        //deactivate if is equal 1
        if (!empty($id) && $status == '1') {
            self::query()->whereIn('id', $id)->update(['is_active' => 1]);
        } else {
            //else activate it if id equal 0
            self::query()->whereIn('id', $id) ->update(['is_active' => 0]);
        }
        return;
    }

    /**
     * Bulk REMOVE
     * @var array DataID
     * @var bool Status
     */
    public static function scopeDeleteList($DataID, $status)
    {
        //deactivate if is equal 1
        if (!empty($DataID) && $status == '1') {
            self::query()->whereIn('id', $DataID)->update(['is_deleted' => 1, 'is_active' => 0]);
        } else {
            //else activate it if id equal 0
            self::query()->whereIn('id', $DataID) ->update(['is_deleted' => 0]);
        }
        return;
    }


    /**
     * Get all of the access for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function access(): HasMany
    {
        return $this->hasMany(UserAccess::class, 'member_id', 'id');
    }
    /**
     * Get all of the access for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function accessOld(): HasManyThrough
    {
        return $this->hasManyThrough(
            UserAccess::class, 
            DrugType::class,
            'id',
            'member_id',
            'type_id',
            'id'
        );
    }
}
