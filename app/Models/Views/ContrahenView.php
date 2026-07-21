<?php

namespace App\Models\Views;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContrahenView extends Model
{
    use HasFactory;
    //counterparty_
    public $table = "counterparty_";

    /**
     * SEARCH DATA By KEYWORD
     * @var STRING Keyword
     */
    public static function scopeSearchByWord(
        $keyword,
        $dtIDList,
        $scID,
        $countryID,
        $mfID,
        $dfID,
        $innID,
        $dtgID,
        $dfgID,
        $trademarkID,
        $drugID,
        $isActive,
        $isDeleted
    ) {
        $data = self::query();
        if (!empty($scID)) {
            $data = $data->whereIn('id', function ($query) use ($scID) {
                $query->select('counterparty_id')->distinct()->from('drug_reports')
                ->whereIn('sc_id', explode(',', $scID));
            });
        }

        if (!empty($countryID)) {
            $data = $data->whereIn('id', function ($query) use ($countryID) {
                $query->select('drug_reports.counterparty_id')->distinct()->from('drug_reports')
                ->leftJoin('manufacturers as mf', 'drug_reports.mf_id', '=', 'mf.id')
                ->whereIn('mf.country_id', explode(',', $countryID));
            });
        }

        if (!empty($mfID)) {
            $data = $data->whereIn('id', function ($query) use ($mfID) {
                $query->select('counterparty_id')->distinct()->from('drug_reports')
                ->whereIn('mf_id', explode(',', $mfID));
            });
        }

        if (!empty($dfID)) {
            $data = $data->whereIn('id', function ($query) use ($dfID) {
                $query->select('drug_reports.counterparty_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.df_id', explode(',', $dfID));
            });
        }

        if (!empty($innID)) {
            $data = $data->whereIn('id', function ($query) use ($innID) {
                $query->select('drug_reports.counterparty_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.di_id', explode(',', $innID));
            });
        }

        if (!empty($dtgID)) {
            $data = $data->whereIn('id', function ($query) use ($dtgID) {
                $query->select('drug_reports.counterparty_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.dtg_id', explode(',', $dtgID));
            });
        }

        if (!empty($dfgID)) {
            $data = $data->whereIn('id', function ($query) use ($dfgID) {
                $query->select('drug_reports.counterparty_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.dfg_id', explode(',', $dfgID));
            });
        }

        if (!empty($trademarkID)) {
            $data = $data->whereIn('id', function ($query) use ($trademarkID) {
                $query->select('drug_reports.counterparty_id')->distinct()->from('drug_reports')
                ->leftJoin('drugs as d', 'drug_reports.drug_id', '=', 'd.id')
                ->whereIn('d.trademark_id', explode(',', $trademarkID));
            });
        }

        if (!empty($drugID)) {
            $data = $data->whereIn('id', function ($query) use ($drugID) {
                $query->select('counterparty_id')->distinct()->from('drug_reports')
                ->whereIn('drug_id', explode(',', $drugID));
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
