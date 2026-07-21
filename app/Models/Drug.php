<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Drugs\DrugManufacturer;
use App\Models\Drugs\DrugReport;
use App\Models\Drugs\DrugType;
use App\Models\Trademark;
use App\Models\Users\UserRole;

class Drug extends Model
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
        'dt_id',
        //'counter',
        'trademark_id',
        'di_id',
        'df_id',
        'dfg_id',
        'dtg_id',
        'ref_price',
        'ref_price_ccy',
        'is_rx',
        'is_otc',
        'is_active',
        'deleted'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'deleted' => 'boolean',
        'is_rx' => 'boolean',
        'is_otc' => 'boolean'
    ];

    protected $hidden = [
        'user_id',
        'trademark_id',
        //'dt_id',
        'di_id',
        'df_id',
        'dfg_id',
        'dtg_id',
        '_id'
    ];


    /**
     * SEARCH DATA By KEYWORD
     * @var STRING Keyword
     */
    public static function scopeSearchByWord(
        $keyword, 
        $dtIDList, 
        $distID,
        $scID,
        $mfID,
        $dfID,
        $innID,
        $dtgID,
        $dfgID,
        $trademarkID,
        $countryID,
        $isActive, 
        $isDeleted
    )
    {
        $data = self::query()->select('id', 'name');

        if (empty($dtIDList) && !\Auth::user()->hasRole('admin', 'employe')) {
            $idList = [];
            $userAccess = User::select('users.id')->with('access')->where('id', \Auth::user()->id)->first();
            foreach ($userAccess->access as $item) {
                $idList[] = $item->type_id;
            }
            if (empty($idList)) {
                $idList[] = 209999;
            }
            $data = $data->whereIn('dt_id', $idList);
        }
        
        if (!empty($dtIDList)) {
            $data = $data->whereIn('dt_id', explode(',', $dtIDList));
        }
        
        if (!empty($distID)) {
            $data = $data->whereIn('id', function ($query) use ($distID) {
                $query->select('drug_id')->from('drug_reports')->whereIn('m40d_id', explode(',', $distID));
            });
        }

        if (!empty($scID)) {
            $data = $data->whereIn('id', function ($query) use ($scID) {
                $query->select('drug_id')->from('drug_reports')->whereIn('sc_id', explode(',', $scID));
            });
        }

        if (!empty($mfID)) {
            $data = $data->whereIn('id', function ($query) use ($mfID) {
                $query->select('drug_id')->from('drug_reports')->whereIn('mf_id', explode(',', $mfID));
            });
        }

        if (!empty($dfID)) {
            $data = $data->whereIn('df_id', explode(',', $dfID));
        }

        if (!empty($innID)) {
            $data = $data->whereIn('di_id', explode(',', $innID));
        }

        if (!empty($dtgID)) {
            $data = $data->whereIn('dtg_id', explode(',', $dtgID));
        }

        if (!empty($dfgID)) {
            $data = $data->whereIn('dfg_id', explode(',', $dfgID));
        }

        if (!empty($trademarkID)) {
            $data = $data->whereIn('trademark_id', explode(',', $trademarkID));
        }

        if (!empty($countryID)) {
            $data = $data->whereIn('id', function ($query) use ($countryID) {
                $query->select('drug_id')->from('drug_reports')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('mf.country_id', explode(',', $countryID));
            });
        }

        return $data = $data->where([
            ['name', 'LIKE', '%' . $keyword . '%'],
            ['is_active',  $isActive],
            ['deleted',  $isDeleted]
        ])->get()->take(30);
    }

    public static function scopeSearchByID($id, $dtIDList, $isActive, $isDeleted)
    {
        $data = self::query()->select('id', 'name')->whereIn('id', $id);
        if (empty($dtIDList)) {
            if (!UserRole::scopeIsAdmin(\Auth::user()->id) || !UserRole::scopeIsEmp(\Auth::user()->id)) {
                $idList = [];
                $userAccess = User::select('users.id')->with('access')->where('id', \Auth::user()->id)->first();
                foreach ($userAccess->access as $item) {
                    $idList[] = $item->type_id;
                }
                if (empty($idList)) {
                    $idList[] = 209999;
                }
                $data = $data->whereIn('dt_id', $idList);
            }
        } else {
            $data = $data->whereIn('dt_id', explode(',', $dtIDList));
        }

        return $data = $data->where([
            ['is_active', $isActive],
            ['deleted', $isDeleted]
        ])->get();
    }

    public function trademark()
    {
        return $this->belongsTo(Trademark::class, 'trademark_id');
    }

    /**
     * Get the comments for the blog post.
     */
    public function _manufacturers()
    {
        return $this->hasMany(DrugManufacturer::class);
    }

    /**
     * Get the user that owns the Drug
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function _dt()
    {
        return $this->belongsTo(DrugType::class, 'dt_id');
    }

    /**
     * Get the drug_inn that owns the Drug
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function drug_inn()
    {
        return $this->belongsTo(DrugInn::class, 'di_id');
    }

    /**
     * Get the drug_form that owns the Drug
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function drug_form()
    {
        return $this->belongsTo(DrugForm::class, 'df_id');
    }

    /**
     * Get the drug_form_group that owns the Drug
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function drug_form_group()
    {
        return $this->belongsTo(DrugFarmGroup::class, 'dfg_id');
    }

    /**
     * Get the drug_ts_group that owns the Drug
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function drug_ts_group()
    {
        return $this->belongsTo(DrugTsGroup::class, 'dtg_id');
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
            ])->update(['is_active' => 1, 'deleted' => 0]);
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

    public static function scopeActiveDeactivateList($id, $isActive, $isDeleted)
    {
        //delete or active status for data
        if ($id && $isActive * $isDeleted != 1) {
            self::query()->whereIn('id', $id)->update(['deleted' => $isDeleted, 'is_active' => $isActive]);
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
            self::query()->whereIn('id', $DataID)->update(['deleted' => 1, 'is_active' => 0]);
        } else {
            //else activate it if id equal 0
            self::query()->whereIn('id', $DataID)->update(['deleted' => 0]);
        }
        return;
    }
}
