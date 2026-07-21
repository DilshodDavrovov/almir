<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Model;

class DTGView extends Model
{
    public $table = "drug_ts_groups_";

    /**
     * SEARCH DATA By KEYWORD
     * @var STRING Keyword
     */
    public static function scopeSearchByWord(
        $keyword,
        $dtIDList,
        $distID,
        $countryID,
        $scID,
        $dfgID,
        $dfID,
        $innID,
        $mfID,
        $trademarkID,
        $drugID,
        $isActive,
        $isDeleted
    ) {
        $data = self::query();

        if (!empty($scID)) {
            $data = $data->whereIn('id', function ($query) use ($scID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('drug_reports.sc_id', explode(',', $scID));
            });
        }

        if (!empty($countryID)) {
            $data = $data->whereIn('id', function ($query) use ($countryID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('mf.country_id', explode(',', $countryID));
            });
        }

        if (!empty($distID)) {
            $data = $data->whereIn('id', function ($query) use ($distID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('drug_reports.m40d_id', explode(',', $distID));
            });
        }

        if (!empty($dfgID)) {
            $data = $data->whereIn('id', function ($query) use ($dfgID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.dfg_id', explode(',', $dfgID));
            });
        }

        if (!empty($dfID)) {
            $data = $data->whereIn('id', function ($query) use ($dfID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.df_id', explode(',', $dfID));
            });
        }

        if (!empty($innID)) {
            $data = $data->whereIn('id', function ($query) use ($innID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.di_id', explode(',', $innID));
            });
        }

        if (!empty($mfID)) {
            $data = $data->whereIn('id', function ($query) use ($mfID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('drug_reports.mf_id', explode(',', $mfID));
            });
        }

        if (!empty($trademarkID)) {
            $data = $data->whereIn('id', function ($query) use ($trademarkID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.trademark_id', explode(',', $trademarkID));
            });
        }

        if (!empty($drugID)) {
            $data = $data->whereIn('id', function ($query) use ($drugID) {
                $query->select('d.dtg_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.id', explode(',', $drugID));
            });
        }

        return $data = $data->where([
            ['full_name', 'LIKE', '%' . $keyword . '%'],
            ['is_active', $isActive],
            ['is_deleted', $isDeleted]
        ])->get()->take(30);
    }
    
    public static function scopeSearchByID($id, $isActive, $isDeleted)
    {
        return self::query()
        ->whereIn('id', $id)
        ->where([
            ['is_active', $isActive],
            ['is_deleted', $isDeleted]
        ])->get();
    }
}
