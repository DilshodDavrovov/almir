<?php

namespace App\Http\Controllers\API\v1\Filters;

use App\Http\Controllers\Controller;
use App\Http\Resources\GetDataCollection;
use App\Models\Drug;
use App\Models\Drugs\DrugReport;
use Illuminate\Http\Request;
//use Carbon\Carbon;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\Users\UserActivity;
use Auth;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * GET Common Prices
     */
    public function getPeriodCommonPrice(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;

        $innIdList = "";
        if (isset($inputs['innID']) && !empty($inputs['innID'])) {
            $innIdList = join(',', $inputs['innID']);
        }

        $tIdList = "";
        if (isset($inputs['trademarkID']) && !empty($inputs['trademarkID'])) {
            $tIdList = join(',', $inputs['trademarkID']);
        }

        $distIdList = "";
        if (isset($inputs['distID']) && !empty($inputs['distID'])) {
            $distIdList = join(',', $inputs['distID']);
        }

        $dfIdList = "";
        if (isset($inputs['dfID']) && !empty($inputs['dfID'])) {
            $dfIdList = join(',', $inputs['dfID']);
        }
        $dfgIdList = "";
        if (isset($inputs['dfgID']) && !empty($inputs['dfgID'])) {
            $dfgIdList = join(',', $inputs['dfgID']);
        }
        $dtIdList = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($dtIdList)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $dtIdList = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $dtIdList = join(',', $inputs['dtID']);
        }

        $dtgIdList = "";
        if (isset($inputs['dtgID']) && !empty($inputs['dtgID'])) {
            $dtgIdList = join(',', $inputs['dtgID']);
        }

        $scIdList = "";
        if (isset($inputs['companyID']) && !empty($inputs['companyID'])) {
            $scIdList = join(',', $inputs['companyID']);
        }

        $mfIdList = "";
        if (isset($inputs['mfID']) && !empty($inputs['mfID'])) {
            $mfIdList = join(',', $inputs['mfID']);
        }

        $cIdList = "";
        if (isset($inputs['countryID']) && !empty($inputs['countryID'])) {
            $cIdList = join(',', $inputs['countryID']);
        }

        $idList = "";
        if (isset($inputs['drugID']) && !empty($inputs['drugID'])) {
            $idList = join(',', $inputs['drugID']);
        }

        $byDataType = $inputs['dataType'] ?? 1;

        $byRegion = "";
        if (isset($inputs['regionID']) && !empty($inputs['regionID'])) {
            $byRegion = join(',', $inputs['regionID']);
        }
        //$byDistrict = $inputs['districtID'] ?? "";
        $byDistrict = "";
        if (isset($inputs['districtID']) && !empty($inputs['districtID'])) {
            $byDistrict = join(',', $inputs['districtID']);
        }

        $result = [];

        foreach ($request->filterByDate as $index => $dates) {
            $counter = $index + 1;
            $item['period_' . $counter] = new \stdClass();

            //parse from string text date
            $fromDate = Carbon::parse($dates["fromDate"])->format('m.d.Y');
            $toDate = Carbon::parse($dates["toDate"])->format('m.d.Y');

            $TotalCommonPrice = DB::select('CALL getSearchCommonPeriod("' . $fromDate . '", "' . $toDate . '", "' . $idList . '", "' . $innIdList . '", "' . $tIdList . '", "' . $distIdList . '", "' . $dfIdList . '", "' . $dfgIdList . '", "' . $dtIdList . '", "' . $dtgIdList . '", "' . $scIdList . '", "' . $mfIdList . '", "' . $cIdList . '", ' . $isActive . ', ' . $IsDeleted . ', ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
            $item['period_' . $counter] = $TotalCommonPrice[0];
        }
        //$re = 'CALL getSearchCommonPeriod("' . $fromDate . '", "' . $toDate . '", "' . $idList . '", "' . $innIdList . '", "' . $tIdList . '", "' . $distIdList . '", "' . $dfIdList . '", "' . $dfgIdList . '", "' . $dtIdList . '", "' . $dtgIdList . '", "' . $scIdList . '", "' . $mfIdList . '", "' . $cIdList . '", ' . $isActive . ', ' . $IsDeleted . ')';
        //\Log::info(['Searched', $re]);
        array_push($result, $item);
        return _sendResponse(201, "Success", $result);
    }

    public function getPeriodCommonData(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["is_deleted"] == true ? 1 : 0;
        $result = [];

        $searcher = [
            ['dr.is_active', $isActive],
            ['dr.is_deleted', $IsDeleted],
        ];

        foreach ($inputs['filterByDate'] as $key => $dates) {
            $fromDate = Carbon::parse($dates['fromDate']);
            $toDate = Carbon::parse($dates['toDate']);

            $data = Drug::selectRaw("sum(dr.quantity) as quantity, sum(dr.sum_price_usd) as sum_price_usd, sum(dr.sum_price_uzs) as sum_price_uzs, sum(dr.sum_price_eur) as sum_price_eur, sum(dr.sum_price_rub) as sum_price_rub");
            if ($request->has('drugID') && !empty($inputs['drugID'])) {
                $data->whereIn('dr.drug_id', $inputs['drugID']);
            }
            if ($request->has('companyID') && !empty($inputs['companyID'])) {
                $data->whereIn('dr.sc_id', $inputs['companyID']);
            }
            if ($request->has('distID') && !empty($inputs['distID'])) {
                $data->whereIn('dr.m70d_id', $inputs['distID']);
            }
            if ($request->has('mfID') && !empty($inputs['mfID'])) {
                $data->whereIn('dr.mf_id', $inputs['mfID']);
            }
            if ($request->has('dfID') && !empty($inputs['dfID'])) {
                $data->whereIn('drugs.df_id', $inputs['dfID']);
            }
            if ($request->has('dfgID') && !empty($inputs['dfgID'])) {
                $data->whereIn('drugs.dfg_id', $inputs['dfgID']);
            }
            if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($inputs['dtID'])) {
                $typeIDList = [];
                foreach (\Auth::user()->access as $item) {
                    $typeIDList[] = $item->type_id;
                }
                $data->whereIn('drugs.dt_id', $typeIDList);
                //$dtIdList = join(',', $typeIDList);
            }
            if ($request->has('dtID') && !empty($inputs['dtID'])) {
                $data->whereIn('drugs.dt_id', $inputs['dtID']);
            }
            if ($request->has('dtgID') && !empty($inputs['dtgID'])) {
                $data->whereIn('drugs.dtg_id', $inputs['dtgID']);
            }
            if ($request->has('trademarkID') && !empty($inputs['trademarkID'])) {
                $data->whereIn('drugs.trademark_id', $inputs['trademarkID']);
            }
            $data = $data->leftJoin('drug_reports as dr', 'drugs.id', '=', 'dr.drug_id');
            $data = $data->whereBetween('dr.mode_40_date', [$fromDate, $toDate]);
            $data = $data->where($searcher)->first();
            $item['period_' . ($key + 1)] = $data;
        }
        array_push($result, $item);


        $message = "Data successfully";
        return _sendResponse(201, $message, $result);
    }


    public function getData(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["is_deleted"] == true ? 1 : 0;
        $SortBy = $inputs["sortBy"] ?? "dr.mode_40_date";
        $SortByDesc = $request->has('sortByDesc') && $inputs["sortByDesc"] == true ? "DESC" : "ASC";
        //->orderBy($SortBy, $SortByDesc)
        $_filterCols = $inputs['filterCol'];
        //parse from string text date
        $fromDate = Carbon::parse($inputs['filterByDate'][0]['fromDate']);
        $toDate = Carbon::parse($inputs['filterByDate'][0]['toDate']);

        $fromDate2 = count($inputs['filterByDate']) >= 2 ?  Carbon::parse($inputs['filterByDate'][1]['fromDate']) : null;
        $toDate2 =  count($inputs['filterByDate']) >= 2 ?  Carbon::parse($inputs['filterByDate'][1]['toDate']) : null;

        $searcher = [
            ['dr.is_active', $isActive],
            ['dr.is_deleted', $IsDeleted],
        ];

        $selector = [
            "drugs.id as id",
            "drugs.name as drug_name",
            "drugs.is_rx",
            "drugs.is_otc",
            "dr.mode_40_date",
            "dr.mode_70_date",
            DB::raw("SUM(dr.quantity) as quantity"),
            "dr.price_usd as price_usd",
            "dr.price_uzs as price_uzs",
            "dr.price_eur as price_eur",
            "dr.price_rub as price_rub",
            DB::raw("SUM(dr.sum_price_usd) as sum_price_usd"),
            DB::raw("SUM(dr.sum_price_uzs) as sum_price_uzs"),
            DB::raw("SUM(dr.sum_price_eur) as sum_price_eur"),
            DB::raw("SUM(dr.sum_price_rub) as sum_price_rub"),
            // DB::raw("SUM(dr.price_usd) as price_usd"),
            // DB::raw("SUM(dr.price_uzs) as price_uzs"),
            // DB::raw("SUM(dr.price_eur) as price_eur"),
            // DB::raw("SUM(dr.price_rub) as price_rub"),
        ];
        $groupBy = [
            "drugs.id",
            "drugs.name",
            "drugs.is_rx",
            "drugs.is_otc",
            "dr.mode_40_date",
            "dr.mode_70_date",
            "dr.price_uzs",
            "dr.price_usd",
            "dr.price_rub",
            "dr.price_eur",
        ];

        if ($_filterCols['totalDrugInn']) {
            $selector[] = "di.name as drug_inn";
            $groupBy[] = "di.name";
        }
        if ($_filterCols['totalTrademarks']) {
            $selector[] = "t.name as trademark_name";
            $groupBy[] = "t.name";
        }
        if ($_filterCols['totalDistributors']) {
            $selector[] = "dist.name as distributor_name";
            $groupBy[] = "dist.name";
        }
        if ($_filterCols['totalDrugForms']) {
            $selector[] = "df.name as df_name";
            $groupBy[] = "df.name";
        }
        if ($_filterCols['totalDrugFormGroups']) {
            $selector[] = "dfg.name as dfg_name";
            $groupBy[] = "dfg.name";
        }
        if ($_filterCols['totalDrugTempGroups']) {
            $selector[] = "dtg.name as dtg_name";
            $groupBy[] = "dtg.name";
        }
        if ($_filterCols['totalCompanies']) {
            $selector[] = "sc.name as company_name";
            $groupBy[] = "sc.name";
        }
        if ($_filterCols['totalManufacturers']) {
            $selector[] = "mf.name as mf_name";
            $groupBy[] = "mf.name";
        }

        $data = Drug::select($selector);

        if ($request->has('drugID') && !empty($inputs['drugID'])) {
            $data->whereIn('dr.drug_id', $inputs['drugID']);
        }
        if ($request->has('companyID') && !empty($inputs['companyID'])) {
            $data->whereIn('dr.sc_id', $inputs['companyID']);
        }
        if ($request->has('distID') && !empty($inputs['distID'])) {
            $data->whereIn('dr.m70d_id', $inputs['distID']);
        }
        if ($request->has('mfID') && !empty($inputs['mfID'])) {
            $data->whereIn('dr.mf_id', $inputs['mfID']);
        }
        if ($request->has('innID') && !empty($inputs['innID'])) {
            $data->whereIn('drugs.di_id', $inputs['innID']);
        }
        if ($request->has('dfID') && !empty($inputs['dfID'])) {
            $data->whereIn('drugs.df_id', $inputs['dfID']);
        }
        if ($request->has('dfgID') && !empty($inputs['dfgID'])) {
            $data->whereIn('drugs.dfg_id', $inputs['dfgID']);
        }

        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($inputs['dtID'])) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('drugs.dt_id', $typeIDList);
            //$dtIdList = join(',', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('drugs.dt_id', $inputs['dtID']);
        }
        if ($request->has('dtgID') && !empty($inputs['dtgID'])) {
            $data->whereIn('drugs.dtg_id', $inputs['dtgID']);
        }
        if ($request->has('trademarkID') && !empty($inputs['trademarkID'])) {
            $data->whereIn('drugs.trademark_id', $inputs['trademarkID']);
        }
        $data = $data->leftJoin('drug_reports as dr', 'drugs.id', '=', 'dr.drug_id');
        $data = $data->leftJoin('distributors as dist', 'dr.m70d_id', '=', 'dist.id');
        $data = $data->leftJoin('manufacturers as mf', 'dr.mf_id', '=', 'mf.id');
        $data = $data->leftJoin('companies as sc', 'dr.sc_id', '=', 'sc.id');
        $data = $data->leftJoin('drug_inns as di', 'drugs.di_id', '=', 'di.id');
        $data = $data->leftJoin('trademarks as t', 'drugs.trademark_id', '=', 't.id');
        $data = $data->leftJoin('drug_forms as df', 'drugs.df_id', '=', 'df.id');
        $data = $data->leftJoin('drug_farm_groups as dfg', 'drugs.dfg_id', '=', 'dfg.id');
        $data = $data->leftJoin('drug_types as dt', 'drugs.dt_id', '=', 'dt.id');
        $data = $data->leftJoin('drug_ts_groups as dtg', 'drugs.dtg_id', '=', 'dtg.id');
        $data = $data->whereBetween('dr.mode_40_date', [$fromDate, $fromDate2 ? $toDate2 : $toDate]);
        $data = $data->where($searcher);
        $data = $data->groupBy($groupBy);
        $data = $data->orderBy($SortBy, $SortByDesc)
            ->paginate($inputs['limit']);

        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($data), 201);
        //return response()->json($data, 201);

        //$message = "Data successfully";
        //return _sendResponse(201, $message, $data);
    }


    public function getFilterByGroupT(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["is_deleted"] == true ? 1 : 0;
        $SortBy = $inputs["sortBy"] ?? "drug_name";
        $SortByDesc = $request->has('sortByDesc') && $inputs["sortByDesc"] == true ? "DESC" : "ASC";
        //->orderBy($SortBy, $SortByDesc)
        $_filterCols = $inputs['filterCol'];
        //parse from string text date
        $fromDate = Carbon::parse($inputs['filterByDate'][0]['fromDate']);
        $toDate = Carbon::parse($inputs['filterByDate'][0]['toDate']);

        $fromDate2 = count($inputs['filterByDate']) >= 2 ?  Carbon::parse($inputs['filterByDate'][1]['fromDate']) : null;
        $toDate2 =  count($inputs['filterByDate']) >= 2 ?  Carbon::parse($inputs['filterByDate'][1]['toDate']) : null;

        $searcher = [
            ['dr.is_active', $isActive],
            ['dr.is_deleted', $IsDeleted],
        ];

        $selector = [
            "drugs.id as id",
            "drugs.name as drug_name",
            "drugs.is_rx",
            "drugs.is_otc",
            DB::raw("SUM(dr.quantity) as quantity"),
            DB::raw("SUM(dr.sum_price_usd) as sum_price_usd"),
            DB::raw("SUM(dr.sum_price_uzs) as sum_price_uzs"),
            DB::raw("SUM(dr.sum_price_eur) as sum_price_eur"),
            DB::raw("SUM(dr.sum_price_rub) as sum_price_rub"),
        ];
        $groupBy = [
            "drugs.id",
            "drugs.name",
            "drugs.is_rx",
            "drugs.is_otc",
        ];

        if ($_filterCols['totalDrugInn']) {
            $selector[] = "di.name as drug_inn";
            $groupBy[] = "di.name";
        }
        if ($_filterCols['totalTrademarks']) {
            $selector[] = "t.name as trademark_name";
            $groupBy[] = "t.name";
        }
        if ($_filterCols['totalDistributors']) {
            //$selector[] = "dist.name as distributor_name";
            $groupBy[] = "dr.drug_id"; //"dist.name";
        }
        if ($_filterCols['totalDrugForms']) {
            $selector[] = "df.name as df_name";
            $groupBy[] = "df.name";
        }
        if ($_filterCols['totalDrugFormGroups']) {
            $selector[] = "dfg.name as dfg_name";
            $groupBy[] = "dfg.name";
        }
        if ($_filterCols['totalDrugTempGroups']) {
            $selector[] = "dtg.name as dtg_name";
            $groupBy[] = "dtg.name";
        }
        if ($_filterCols['totalCompanies']) {
            //$selector[] = "sc.name as company_name";
            $groupBy[] = "dr.sc_id";
        }
        if ($_filterCols['totalManufacturers']) {
            $selector[] = "mf.name as mf_name";
            $selector[] = "country.name as country";
            $groupBy[] = "mf.name";
            $groupBy[] = "country.name";
        }

        $data = Drug::select($selector);

        if ($request->has('drugID') && !empty($inputs['drugID'])) {
            $data->whereIn('dr.drug_id', $inputs['drugID']);
        }
        if ($request->has('companyID') && !empty($inputs['companyID'])) {
            $data->whereIn('dr.sc_id', $inputs['companyID']);
        }
        if ($request->has('distID') && !empty($inputs['distID'])) {
            $data->whereIn('dr.m40d_id', $inputs['distID']);
        }
        if ($request->has('mfID') && !empty($inputs['mfID'])) {
            $data->whereIn('dr.mf_id', $inputs['mfID']);
        }
        if ($request->has('innID') && !empty($inputs['innID'])) {
            $data->whereIn('drugs.di_id', $inputs['innID']);
        }
        if ($request->has('dfID') && !empty($inputs['dfID'])) {
            $data->whereIn('drugs.df_id', $inputs['dfID']);
        }
        if ($request->has('dfgID') && !empty($inputs['dfgID'])) {
            $data->whereIn('drugs.dfg_id', $inputs['dfgID']);
        }
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($inputs['dtID'])) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('drugs.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('drugs.dt_id', $inputs['dtID']);
        }
        if ($request->has('dtgID') && !empty($inputs['dtgID'])) {
            $data->whereIn('drugs.dtg_id', $inputs['dtgID']);
        }
        if ($request->has('trademarkID') && !empty($inputs['trademarkID'])) {
            $data->whereIn('drugs.trademark_id', $inputs['trademarkID']);
        }
        
        $data = $data->leftJoin('drug_reports as dr', 'drugs.id', '=', 'dr.drug_id');
        //$data = $data->leftJoin('distributors as dist', 'dr.m70d_id', '=', 'dist.id');
        $data = $data->leftJoin('manufacturers as mf', 'dr.mf_id', '=', 'mf.id');
        $data = $data->leftJoin('countries as country', 'mf.country_id', '=', 'country.id');
        //$data = $data->leftJoin('companies as sc', 'dr.sc_id', '=', 'sc.id');
        $data = $data->leftJoin('drug_inns as di', 'drugs.di_id', '=', 'di.id');
        $data = $data->leftJoin('trademarks as t', 'drugs.trademark_id', '=', 't.id');
        $data = $data->leftJoin('drug_forms as df', 'drugs.df_id', '=', 'df.id');
        $data = $data->leftJoin('drug_farm_groups as dfg', 'drugs.dfg_id', '=', 'dfg.id');
        $data = $data->leftJoin('drug_types as dt', 'drugs.dt_id', '=', 'dt.id');
        $data = $data->leftJoin('drug_ts_groups as dtg', 'drugs.dtg_id', '=', 'dtg.id');
        $data = $data->whereBetween('dr.mode_40_date', [$fromDate, $fromDate2 ? $toDate2 : $toDate]);
        $data = $data->where($searcher);
        $data = $data->groupBy($groupBy);
        $data = $data->orderBy($SortBy, $SortByDesc)
            ->paginate($inputs['limit']);

        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($data), 201);
        //return response()->json($data, 201);

        //$message = "Data successfully";
        //return _sendResponse(201, $message, $data);
    }

    public function getFilterByGroup(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["is_deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";
        $_filterCols = $inputs['filterCol'];

        $byTable = "sf";
        $count = 0;

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int) $inputs['page'] > 1 ? (((int) $inputs['page'] - 1) * $_limit + 1) : 0;

        $innIdList = "";
        if (isset($inputs['innID']) && !empty($inputs['innID'])) {
            $innIdList = join(',', $inputs['innID']);
        }

        $tIdList = "";
        if (isset($inputs['trademarkID']) && !empty($inputs['trademarkID'])) {
            $tIdList = join(',', $inputs['trademarkID']);
        }

        $distIdList = "";
        if (isset($inputs['distID']) && !empty($inputs['distID'])) {
            $distIdList = join(',', $inputs['distID']);
        }

        $dfIdList = "";
        if (isset($inputs['dfID']) && !empty($inputs['dfID'])) {
            $dfIdList = join(',', $inputs['dfID']);
        }
        $dfgIdList = "";
        if (isset($inputs['dfgID']) && !empty($inputs['dfgID'])) {
            $dfgIdList = join(',', $inputs['dfgID']);
        }
        $dtIdList = "";
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $dtIdList = join(',', $inputs['dtID']);
        }
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($dtIdList)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            //$data->whereIn('drugs.dt_id', $typeIDList);
            $dtIdList = join(',', $typeIDList);
        }

        $dtgIdList = "";
        if (isset($inputs['dtgID']) && !empty($inputs['dtgID'])) {
            $dtgIdList = join(',', $inputs['dtgID']);
        }

        $scIdList = "";
        if (isset($inputs['companyID']) && !empty($inputs['companyID'])) {
            $scIdList = join(',', $inputs['companyID']);
        }

        $mfIdList = "";
        if (isset($inputs['mfID']) && !empty($inputs['mfID'])) {
            $mfIdList = join(',', $inputs['mfID']);
        }

        $cIdList = "";
        if (isset($inputs['countryID']) && !empty($inputs['countryID'])) {
            $cIdList = join(',', $inputs['countryID']);
        }

        $idList = "";
        if (isset($inputs['drugID']) && !empty($inputs['drugID'])) {
            $idList = join(',', $inputs['drugID']);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select('CALL getSearchCounts("' . $fromDateFirst . '", "' . $toDateFirst . '", "' . $idList . '", "' . $innIdList . '", "' . $tIdList . '", "' . $distIdList . '", "' . $dfIdList . '", "' . $dfgIdList . '", "' . $dtIdList . '", "' . $dtgIdList . '", "' . $scIdList . '", "' . $mfIdList . '", "' . $cIdList . '", ' . $isActive . ', ' . $IsDeleted . ')');
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_' . $fromDateFirst . '_' . $toDateFirst, $count);
        }
        $resData = DB::select('CALL getSearchData("' . $fromDateFirst . '", "' . $toDateFirst . '","' . $idList . '","' . $innIdList . '","' . $tIdList . '","' . $distIdList . '","' . $dfIdList . '","' . $dfgIdList . '","' . $dtIdList . '","' . $dtgIdList . '","' . $scIdList . '","' . $mfIdList . '","' . $cIdList . '", ' . $isActive . ',' . $IsDeleted . ',' . $_limit . ',' . $currentPage . ',"' . $_sortByDesc . '")');

        $resData = json_decode(json_encode($resData), true);
        $result = [];

        $perPage = round($count / $_limit);

        foreach ($resData as $item) {
            unset($item['USD'], $item['qty']);

            foreach ($request->filterByDate as $index => $dates) {
                $counter = $index + 1;

                $inputFromDate = Carbon::parse($dates['fromDate'])->format('m.d.Y');
                $inputToDate = Carbon::parse($dates['toDate'])->format('m.d.Y');

                $item['period_' . $counter] = new \stdClass();
                //$TotalCommonPrice = DB::select('CALL getMfTotalData(STR_TO_DATE("'.$inputFromDate.'", "%m.%d.%Y"),STR_TO_DATE("'.$dates["toDate"]., "%m.%d.%Y") )');
                $periodData = Redis::get($byTable . '_src_tcp_' . $item['drug_id'] . '_' . $item['mf_id'] .  '_' . $inputFromDate . '_' . $inputToDate);
                if ($periodData) {
                    $item['period_' . $counter]->totalCommonPerPrice = json_decode($periodData);
                } else {
                    $periodData = DB::select('CALL getSearchDataPeriod("' . $inputFromDate . '", "' . $inputToDate . '","' . $item['drug_id'] . '","' . $innIdList . '","' . $tIdList . '","' . $distIdList . '","' . $dfIdList . '","' . $dfgIdList . '","' . $dtIdList . '","' . $dtgIdList . '","' . $scIdList . '","' . $item['mf_id'] . '","' . $cIdList . '", ' . $isActive . ',' . $IsDeleted . ')');
                    $item['period_' . $counter]->totalCommonPerPrice = $periodData[0] ?? null;
                    Redis::set($byTable . '_src_tcp_' . $item['drug_id'] . '_' . $item['mf_id'] . '_' . $inputFromDate . '_' . $inputToDate, json_encode($periodData[0] ?? null));
                }
                #$item['period_' . ($index + 1)] = $periods;
            }
            array_push($result, $item);
        }

        //User Activity 
        if ($request->getContent()) {
            $activity = new UserActivity;
            $activity->name = $request->filterByDate[0]['fromDate'] . ' - ' . $request->filterByDate[0]['toDate'];
            $activity->by_table = $byTable;
            $activity->user_id = Auth::user()->id;
            $activity->body = $request->getContent();
            $activity->save();
        }

        return _responsePeriods(201, $result, $count, $perPage, $_limit, $currentPage);
    }

    public function getFilterByDrugsById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];

        $byDataType = $inputs['dataType'] ?? 1;

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'd.name as name_uz',
            't.name as trademark',
            'dt.name as drug_type',
            'df.name as drug_form',
            'dfg.name as drug_farm_group',
            'di.name as drug_inn',
            'dist.name as distributor',
            'c.name as sender_company',
            'mf.name as manufacturer',
            'country.name AS country',
            'dtg.name as drug_ts_group',
            'drug_reports.*',
        )
            ->leftJoin('companies as c', 'sc_id', '=', 'c.id')
            ->leftJoin('distributors as dist', 'm40d_id', '=', 'dist.id')
            ->leftJoin('manufacturers as mf', 'mf_id', '=', 'mf.id')
            ->leftJoin('countries as country', 'mf.country_id', '=', 'country.id')
            ->leftJoin('drugs as d', 'drug_id', '=', 'd.id')
            ->leftJoin('trademarks as t', 'd.trademark_id', '=', 't.id')
            ->leftJoin('drug_types as dt', 'd.dt_id', '=', 'dt.id')
            ->leftJoin('drug_forms as df', 'd.df_id', '=', 'df.id')
            ->leftJoin('drug_farm_groups as dfg', 'd.dfg_id', '=', 'dfg.id')
            ->leftJoin('drug_inns as di', 'd.di_id', '=', 'di.id')
            ->leftJoin('drug_ts_groups as dtg', 'd.dtg_id', '=', 'dtg.id')
            ->whereBetween('mode_40_date', [$fromDate, $toDate])
            ->where([
                ['d.id', $dataID],
                ['drug_reports.data_type', $byDataType],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        if ($request->has('companyID') && !empty($inputs['companyID'])) {
            $data->whereIn('drug_reports.sc_id', $inputs['companyID']);
        }
        if ($request->has('distID') && !empty($inputs['distID'])) {
            $data->whereIn('drug_reports.m70d_id', $inputs['distID']);
        }
        if ($request->has('mfID') && !empty($inputs['mfID'])) {
            $data->whereIn('drug_reports.mf_id', $inputs['mfID']);
        }
        if ($request->has('innID') && !empty($inputs['innID'])) {
            $data->whereIn('d.di_id', $inputs['innID']);
        }
        if ($request->has('dfID') && !empty($inputs['dfID'])) {
            $data->whereIn('d.df_id', $inputs['dfID']);
        }
        if ($request->has('dfgID') && !empty($inputs['dfgID'])) {
            $data->whereIn('d.dfg_id', $inputs['dfgID']);
        }
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($inputs['dtID'])) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
        }
        if ($request->has('dtgID') && !empty($inputs['dtgID'])) {
            $data->whereIn('d.dtg_id', $inputs['dtgID']);
        }
        if ($request->has('trademarkID') && !empty($inputs['trademarkID'])) {
            $data->whereIn('d.trademark_id', $inputs['trademarkID']);
        }
        $data = $data->orderBy("drug_reports.mode_40_date", "ASC")->paginate($inputs['limit']);

        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($data), 201);

        //$message = "Data successfully";
        //return _sendResponse(201, $message, $data);
    }

    /**
     * GET DATA BY FILTER FEILDS
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getDataByFilterField(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["is_deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterBy = Str::lower($inputs['filterBy'] ?? 'drug_name');

        $byDataType = $inputs['dataType'] ?? 1;

        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byTable = "sfs";
        $count = 0;

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int) $inputs['page'] > 1 ? (((int) $inputs['page'] - 1) * $_limit + 1) : 0;

        $innIdList = "";
        if (isset($inputs['innID']) && !empty($inputs['innID'])) {
            $innIdList = join(',', $inputs['innID']);
        }

        $tIdList = "";
        if (isset($inputs['trademarkID']) && !empty($inputs['trademarkID'])) {
            $tIdList = join(',', $inputs['trademarkID']);
        }

        $distIdList = "";
        if (isset($inputs['distID']) && !empty($inputs['distID'])) {
            $distIdList = join(',', $inputs['distID']);
        }

        $dfIdList = "";
        if (isset($inputs['dfID']) && !empty($inputs['dfID'])) {
            $dfIdList = join(',', $inputs['dfID']);
        }
        $dfgIdList = "";
        if (isset($inputs['dfgID']) && !empty($inputs['dfgID'])) {
            $dfgIdList = join(',', $inputs['dfgID']);
        }
        $dtIdList = "";
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $dtIdList = join(',', $inputs['dtID']);
        }
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($dtIdList)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            //$data->whereIn('drugs.dt_id', $typeIDList);
            $dtIdList = join(',', $typeIDList);
        }

        $dtgIdList = "";
        if (isset($inputs['dtgID']) && !empty($inputs['dtgID'])) {
            $dtgIdList = join(',', $inputs['dtgID']);
        }

        $scIdList = "";
        if (isset($inputs['companyID']) && !empty($inputs['companyID'])) {
            $scIdList = join(',', $inputs['companyID']);
        }

        $mfIdList = "";
        if (isset($inputs['mfID']) && !empty($inputs['mfID'])) {
            $mfIdList = join(',', $inputs['mfID']);
        }

        $cIdList = "";
        if (isset($inputs['countryID']) && !empty($inputs['countryID'])) {
            $cIdList = join(',', $inputs['countryID']);
        }

        //$byRegion = $inputs['regionID'] ?? "";
        $byRegion = "";
        if (isset($inputs['regionID']) && !empty($inputs['regionID'])) {
            $byRegion = join(',', $inputs['regionID']);
        }
        //$byDistrict = $inputs['districtID'] ?? "";
        $byDistrict = "";
        if (isset($inputs['districtID']) && !empty($inputs['districtID'])) {
            $byDistrict = join(',', $inputs['districtID']);
        }

        $idList = "";
        if (isset($inputs['drugID']) && !empty($inputs['drugID'])) {
            $idList = join(',', $inputs['drugID']);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select('CALL getDataByFilterCount("' . $fromDateFirst . '", "' . $toDateFirst . '", "' . $idList . '", "' . $innIdList . '", "' . $tIdList . '", "' . $distIdList . '", "' . $dfIdList . '", "' . $dfgIdList . '", "' . $dtIdList . '", "' . $dtgIdList . '", "' . $scIdList . '", "' . $mfIdList . '", "' . $cIdList . '", ' . $isActive . ', ' . $IsDeleted . ', "' . $_filterBy . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
      
            $count = $counter[0]->counts;
            Redis::set($byTable . $_filterBy . '_resCount_' . $fromDateFirst . '_' . $toDateFirst. '_'. $byDataType. '_'. $byRegion. '_'.$byDistrict, $count);
        }
        $resData = DB::select('CALL getDataByFilterPeriod("' . $fromDateFirst . '", "' . $toDateFirst . '","' . $idList . '","' . $innIdList . '","' . $tIdList . '","' . $distIdList . '","' . $dfIdList . '","' . $dfgIdList . '","' . $dtIdList . '","' . $dtgIdList . '","' . $scIdList . '","' . $mfIdList . '","' . $cIdList . '", ' . $isActive . ',' . $IsDeleted . ',' . $_limit . ',' . $currentPage . ',"' . $_sortByDesc. '", "' . $_filterBy. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');

        $resData = json_decode(json_encode($resData), true);
        $result = [];

        //$perPage = round($count / $_limit);
        $perPage = ceil($count / $_limit);

        foreach ($resData as $item) {
            unset($item['USD'], $item['qty']);

            foreach ($request->filterByDate as $index => $dates) {
                $counter = $index + 1;

                $inputFromDate = Carbon::parse($dates['fromDate'])->format('m.d.Y');
                $inputToDate = Carbon::parse($dates['toDate'])->format('m.d.Y');

                $item['period_' . $counter] = new \stdClass();
                $periodData = Redis::get($byTable . $_filterBy  . '_src_tcp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate. '_'. $byDataType. '_'. $byRegion. '_'.$byDistrict);

                if ($periodData) {
                    $item['period_' . $counter]->totalCommonPerPrice = json_decode($periodData);
                } else {
                    $periodData = DB::select('CALL getDataByFilterList("' . $inputFromDate . '", "' . $inputToDate . '","' . $idList . '","' . $innIdList . '","' . $tIdList . '","' . $distIdList . '","' . $dfIdList . '","' . $dfgIdList . '","' . $dtIdList . '","' . $dtgIdList . '","' . $scIdList . '","' . $mfIdList . '","' . $cIdList . '", ' . $isActive . ',' . $IsDeleted . ',"' . $_filterBy . '",' . $item['id']. ',' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');

                    $item['period_' . $counter]->totalCommonPerPrice = $periodData[0] ?? null;
                    Redis::set($byTable . $_filterBy  . '_src_tcp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate. '_' . $idList. '_' .$innIdList. '_' .$tIdList. '_' .$distIdList. '_'.$dfIdList. '_' . $dfgIdList. '_' .$tIdList. '_' .$dtgIdList. '_' .$scIdList. '_' .$mfIdList. '_' .$cIdList, json_encode($periodData[0] ?? null));
                }
                #$item['period_' . ($index + 1)] = $periods;
            }
            array_push($result, $item);
        }

        //User Activity 
        if ($request->getContent()) {
            $activity = new UserActivity;
            $activity->name = $request->filterByDate[0]['fromDate'] . ' - ' . $request->filterByDate[0]['toDate'];
            $activity->by_table = $byTable;
            $activity->user_id = Auth::user()->id;
            $activity->body = $request->getContent();
            $activity->save();
        }

        return _responsePeriods(201, $result, $count, $perPage, $_limit, $currentPage);
    }

    public function getFilterDataInfo(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $_filterBy = Str::lower($inputs['filterBy'] ?? 'drug_name');
        $byDataType = $inputs['dataType'] ?? 1;

        switch ($_filterBy) {
            case 'inn':
                $_filterBy = "d.di_id";
                break;
            case 'drug_name':
                $_filterBy = "drug_reports.drug_id";
                break;
            case 'dt':
                $_filterBy = "d.dt_id";
                break;
            case 'dtg':
                $_filterBy = "d.dtg_id";
                break;
            case 'df':
                $_filterBy = "d.df_id";
                break;
            case 'dfg':
                $_filterBy = "d.dfg_id";
                break;
            case 'trademark':
                $_filterBy = "d.trademark_id";
                break;
            case 'mf':
                $_filterBy = "drug_reports.mf_id";
                break;
            case 'dist':
                $_filterBy = "drug_reports.m40d_id";
                break;
            case 'sc':
                $_filterBy = "drug_reports.sc_id";
                break;
            case 'country':
                $_filterBy = "mf.country_id";
                break;
            case 'region':
                $_filterBy = "drug_reports.region_id";
                break;
            case 'district':
                $_filterBy = "drug_reports.district_id";
                break;
            default:
                $_filterBy = "drug_reports.drug_id";
                break;
        }
        
        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'd.name as name_uz',
            't.name as trademark',
            'dt.name as drug_type',
            'df.name as drug_form',
            'dfg.name as drug_farm_group',
            'di.name as drug_inn',
            'dist.name as distributor',
            'c.name as sender_company',
            'mf.name as manufacturer',
            'country.name AS country',
            'dtg.name as drug_ts_group',
            'drug_reports.*',
        )
        ->leftJoin('companies as c', 'sc_id', '=', 'c.id')
            ->leftJoin('distributors as dist', 'm40d_id', '=', 'dist.id')
            ->leftJoin('manufacturers as mf', 'mf_id', '=', 'mf.id')
            ->leftJoin('countries as country', 'mf.country_id', '=', 'country.id')
            ->leftJoin('drugs as d', 'drug_id', '=', 'd.id')
            ->leftJoin('trademarks as t', 'd.trademark_id', '=', 't.id')
            ->leftJoin('drug_types as dt', 'd.dt_id', '=', 'dt.id')
            ->leftJoin('drug_forms as df', 'd.df_id', '=', 'df.id')
            ->leftJoin('drug_farm_groups as dfg', 'd.dfg_id', '=', 'dfg.id')
            ->leftJoin('drug_inns as di', 'd.di_id', '=', 'di.id')
            ->leftJoin('drug_ts_groups as dtg', 'd.dtg_id', '=', 'dtg.id')
            ->whereBetween('mode_40_date', [$fromDate, $toDate])
            ->where([
                [$_filterBy, $dataID],
                ['drug_reports.data_type', $byDataType],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        if ($request->has('drugID') && !empty($inputs['drugID'])) {
            $data->whereIn('drug_reports.drug_id', $inputs['drugID']);
        }
        if ($request->has('companyID') && !empty($inputs['companyID'])) {
            $data->whereIn('drug_reports.sc_id', $inputs['companyID']);
        }
        if ($request->has('distID') && !empty($inputs['distID'])) {
            $data->whereIn('drug_reports.m40d_id', $inputs['distID']);
        }
        if ($request->has('mfID') && !empty($inputs['mfID'])) {
            $data->whereIn('drug_reports.mf_id', $inputs['mfID']);
        }
        if ($request->has('innID') && !empty($inputs['innID'])) {
            $data->whereIn('d.di_id', $inputs['innID']);
        }
        if ($request->has('dfID') && !empty($inputs['dfID'])) {
            $data->whereIn('d.df_id', $inputs['dfID']);
        }
        if ($request->has('dfgID') && !empty($inputs['dfgID'])) {
            $data->whereIn('d.dfg_id', $inputs['dfgID']);
        }
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($inputs['dtID'])) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
        }
        if ($request->has('dtgID') && !empty($inputs['dtgID'])) {
            $data->whereIn('d.dtg_id', $inputs['dtgID']);
        }
        if ($request->has('trademarkID') && !empty($inputs['trademarkID'])) {
            $data->whereIn('d.trademark_id', $inputs['trademarkID']);
        }

        $data = $data->orderBy("drug_reports.mode_40_date", "ASC")->paginate($inputs['limit']);

        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($data), 201);

        //$message = "Data successfully";
        //return _sendResponse(201, $message, $data);
    }
}
