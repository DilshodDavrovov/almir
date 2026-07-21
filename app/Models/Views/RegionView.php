<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionView extends Model
{
    public $table = "regions_";

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
        $drugID,
        $isActive,
        $isDeleted,
        $CountryId
    ) {


        $data = self::query();
        if (!empty($distID)) {
            $data = $data->whereIn('id', function ($query) use ($distID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('drug_reports.m40d_id', explode(',', $distID));
            });
        }

        if (!empty($scID)) {
            $data = $data->whereIn('id', function ($query) use ($scID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('drug_reports.sc_id', explode(',', $scID));
            });
        }

        if (!empty($mfID)) {
            $data = $data->whereIn('id', function ($query) use ($mfID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->whereIn('drug_reports.mf_id', explode(',', $mfID));
            });
        }

        if (!empty($dfID)) {
            $data = $data->whereIn('id', function ($query) use ($dfID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('d.df_id', explode(',', $dfID));
            });
        }

        if (!empty($innID)) {
            $data = $data->whereIn('id', function ($query) use ($innID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('d.di_id', explode(',', $innID));
            });
        }

        if (!empty($dtgID)) {
            $data = $data->whereIn('id', function ($query) use ($dtgID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('d.dtg_id', explode(',', $dtgID));
            });
        }

        if (!empty($dfgID)) {
            $data = $data->whereIn('id', function ($query) use ($dfgID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('d.dfg_id', explode(',', $dfgID));
            });
        }

        if (!empty($trademarkID)) {
            $data = $data->whereIn('id', function ($query) use ($trademarkID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('d.trademark_id', explode(',', $trademarkID));
            });
        }

        if (!empty($drugID)) {
            $data = $data->whereIn('id', function ($query) use ($drugID) {
                $query->select('drug_reports.region_id')->distinct()->from('drug_reports')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('drug_reports.drug_id', explode(',', $drugID));
            });
        }

        $query = [
            ['full_name', 'LIKE', '%' . $keyword . '%'],
            ['is_active', $isActive],
            ['is_deleted', $isDeleted]
        ];
        if ($CountryId) {
            $query[] = ['country_id', $CountryId];
        }
        return $data = $data->where($query)->get()->take(30);
    }

    public static function scopeSearchByID($id, $isActive, $isDeleted, $CountryId)
    {
        $query = [
            ['is_active', $isActive],
            ['is_deleted', $isDeleted]
        ];
        if ($CountryId) {
            $query[] = ['country_id', $CountryId];
        }
        return self::query()
            ->whereIn('id', $id)
            ->where($query)->get();
    }
}
