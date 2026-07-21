<?php

namespace App\Models\Drugs;

use App\Models\System\District;
use App\Models\System\Region;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contrahens extends Model
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
        'inn',
        'user_id',
        'region_id',
        'district_id',
        'is_active',
        'is_active',
        'deleted'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'deleted' => 'boolean'
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
            self::query()->where('id', $id)->update(['is_active' => 0]);
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
            self::query()->where(['id' => $id])->update(['deleted' => $isDeleted, 'is_active' => $isActive]);
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
            self::query()->whereIn('id', $id)->update(['is_active' => 0]);
        }
        return self::query()->whereIn('id', $id)->get();
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
            self::query()->whereIn('id', $DataID)->update(['deleted' => 1, 'is_active' => 0]);
        } else {
            //else activate it if id equal 0
            self::query()->whereIn('id', $DataID)->update(['deleted' => 0]);
        }
        return;
    }

    /** 
     * Get Region of data
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');  
    }
    /**
     * Get District of data
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
