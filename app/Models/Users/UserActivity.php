<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;

    public $timestamps = true;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'user_id',
        'body',
        'by_table',
        'is_active',
        'deleted'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'deleted' => 'boolean'
    ];

    public function scopeFilterData($filterBy, $fDate, $tDate, $isActive, $IsDeleted) 
    {
        if (!empty($filterBy) && $filterBy == "ByPrice") {
            self::query()
            ->select('')
            ->where([
                ['id', $id],
                //['is_active', "!=", 1]
            ])->update(['is_active' => 1]);
        }
    }
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
    public static function scopeDeleteIt($id, $status)
    {
        //deactivate if is equal 1
        if (!empty($id) && $status == '1') {
            self::query()->where([
                ['id', $id],
                //['is_active', "!=", 1]
            ])->update(['deleted' => 1]);
        } else {
            //else activate it if id equal 0
            self::query()->where('id', $id) ->update(['deleted' => 0]);
        }
        return self::where([
                ['id', $id]
            ])->first();
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
            self::query()->whereIn('id', $DataID)->update(['deleted' => 1]);
        } else {
            //else activate it if id equal 0
            self::query()->whereIn('id', $DataID) ->update(['deleted' => 0]);
        }
        return;
    }
}
