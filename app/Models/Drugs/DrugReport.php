<?php

namespace App\Models\Drugs;

use App\Models\Distributor;
use App\Models\User;
use App\Models\Drug;
use App\Models\Drugs\Company;
use App\Models\Manufacturer;
use App\Models\System\District;
use App\Models\System\Region;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DrugReport extends Model
{
    use HasFactory;
    
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'data_type',
        'counterparty_id',
        'region_id',
        'district_id',
        'drug_id',
        'period_code',
        'user_id',
        'serial_number',
        'shelf_life',
        'mode_70_date',
        'm70d_id',
        'mode_40_date',
        'm40d_id',
        'sc_id',
        'mf_id',
        'c_price_ccy',
        'c_price_ccy_rate',
        'c_price_usd',
        'c_price_uzs',
        'c_price_eur',
        'c_price_rub',
        'price_ccy',
        'price_ccy_rate',
        'price_usd',
        'price_uzs',
        'price_eur',
        'price_rub',
        'quantity',
        'sum_price_usd',
        'sum_price_uzs',
        'sum_price_eur',
        'sum_price_rub',
        'is_local',
        'is_updated',
        'is_active',
        'is_deleted'
    ];
    protected $casts = [
        'is_local' => 'boolean',
        'is_updated' => 'boolean',
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
        // 'mode_40_date' => 'datetime:Y-m-d',
        // 'mode_70_date' => 'datetime:Y-m-d',
        'created_at' => 'datetime:Y-m-d | H:m'
    ];

    // protected $hidden = [
    //     'user_id',
    //     'drug_id'
    // ];


    /**
     * Get the post that owns the comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /***
     * DRUG NAMES
     */
    public function drug_name()
    {
        return $this->belongsTo(Drug::class, 'drug_id');
        //return $this->belongsTo(Drug::class);
    }


    /***
     * Distributor
     */
    public function distributor()
    {
        return $this->belongsTo(Distributor::class, 'm40d_id');
        //return $this->belongsTo(Drug::class);
    }

    
    public function _manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'mf_id');
        //return $this->belongsTo(Drug::class);
    }

    /**
     * The Drug Counter that belong to the DrugReport
     *
     */
    public function drug_code()
    {
        return $this->hasMany(DrugManufacturer::class, 'drug_id', 'drug_id');
    }

    public function drugManufacturer()
    {
        return $this->hasOne(DrugManufacturer::class, 'drug_id', 'drug_id')
            ->whereColumn('manufacturer_id', 'mf_id');
    }




    public function sender_company()
    {
        return $this->belongsTo(Company::class, 'sc_id');
        //return $this->belongsTo(Drug::class);
    }

    
    public function distributor70()
    {
        return $this->belongsTo(Distributor::class, 'm70d_id');
        //return $this->belongsTo(Drug::class);
    }

    public function distributor40()
    {
        return $this->belongsTo(Distributor::class, 'm40d_id');
        //return $this->belongsTo(Drug::class);
    }
    
    /**
     * Get the counterparty associated with the DrugReport
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function counterparty()
    {
        return $this->belongsTo(Contrahens::class, 'counterparty_id');
    }

    /**
     * Get the region associated with the DrugReport
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * Get the district associated with the DrugReport
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    /**
     * Active STATUS
     * @var int ID
     * @var bool Status
     */

    public static function scopeActiveDeactivate($id, $status)
    {
        //deactivate if is equal 1
        if ($id && $status == '1') {
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

    public static function scopeActiveDeactivateList($id, $isActive, $isDeleted)
    {
        //delete or active status for data
        if ($id && $isActive * $isDeleted != 1) {
            self::query()->whereIn('id', $id)->update(['is_deleted' => $isDeleted, 'is_active' => $isActive]);
            return true;
        }
        return false;
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
}
