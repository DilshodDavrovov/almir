<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GetDataCollection;
use App\Models\Drugs\DrugReport;
use App\Models\User;
use App\Models\Users\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Auth;

class FilterController extends Controller
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
     * Run a stored-procedure SELECT with a Redis cache layer.
     * Returns rows as an associative array. Caches with a TTL so a new
     * import eventually invalidates the entry instead of it living forever.
     */
    private function cachedProc(string $key, string $sql, int $ttl = 21600): array
    {
        $cached = Redis::get($key);
        if ($cached !== null) {
            return json_decode($cached, true) ?? [];
        }
        $rows = json_decode(json_encode(DB::select($sql)), true);
        Redis::set($key, json_encode($rows), 'EX', $ttl);
        return $rows;
    }

    /**
     * GET Common Prices
     */
    public function getPeriodCommonPrice(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $byTable = isset($inputs['byTable']) ? $inputs['byTable'] : "";
        
        $byDataType = $inputs['dataType'] ?? 1;
       
        $idList = "";

        $dtIdList = "";
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $dtIdList = join(',', $inputs['dtID']);
        }

        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }

        $byDistrict = "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $dtIdList = join(',', $typeIDList);
        }

        $result = [];

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $idList = join(',', $inputs['dataIDList']);
        }
        foreach ($request->filterByDate as $index => $dates) {
            $counter = $index + 1;
            $item['period_' . $counter] = new \stdClass();

            //parse from string text date
            $fromDate = Carbon::parse($dates["fromDate"])->format('m.d.Y');
            $toDate = Carbon::parse($dates["toDate"])->format('m.d.Y');

            $ckey = 'commprice_' . $byTable . '_' . $idList . '_' . $fromDate . '_' . $toDate . '_' . $isActive . '_' . $IsDeleted . '_' . $dtIdList . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict;
            $cached = Redis::get($ckey);
            if ($cached !== null) {
                $item['period_' . $counter] = json_decode($cached);
            } else {
                $TotalCommonPrice = DB::select(
                    'CALL getCommPrice("' .
                        $fromDate . '","' .
                        $toDate . '", "' .
                        $byTable . '", "' .
                        $idList . '",' .
                        $isActive . ', ' . $IsDeleted .', "' .
                        $dtIdList . '", "' .
                        $byDataType . '", "' .
                        $byRegion . '", "' .
                        $byDistrict . '")'
                );
                $item['period_' . $counter] = $TotalCommonPrice[0] ?? null;
                Redis::set($ckey, json_encode($TotalCommonPrice[0] ?? null), 'EX', 21600);
            }
        }
        array_push($result, $item);
        return _sendResponse(201, "Success", $result);
    }

    /**
     * GET DISTRIBUTER
     */
    public function getFilterByDistributors(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byDataType = $inputs['dataType'] ?? 1;
       
        $byTable = "dist";
        $idList = "";
        $count = 0;

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $typeID = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict = "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;
        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'.
                    $typeID . '", '.
                    $byDataType . ', "'.
                    $byRegion . '", "' . 
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_'. $typeID.'_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $dist = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . 
                $count . ', ' . 
                $_limit . ', ' . 
                $currentPage . ', ' . 
                $isActive . ', ' . 
                $IsDeleted . ', "' . 
                $byTable . '", "' . 
                $idList . '", "' . 
                $_sortByDesc . '", "' .
                $typeID . '", ' .
                $byDataType . ', "' .
                $byRegion . '", "' . 
                $byDistrict . '")'
        );
        $dist = json_decode(json_encode($dist), true);
        $result = [];

        $perPage = round($count / $_limit);

        foreach ($dist as $item) {
            unset($item['USD'], $item['qty']);
            foreach ($request->filterByDate as $index => $dates) {
                $counter = $index + 1;

                $inputFromDate = Carbon::parse($dates['fromDate'])->format('m.d.Y');
                $inputToDate = Carbon::parse($dates['toDate'])->format('m.d.Y');

                $item['period_' . $counter] = new \stdClass();
                //$TotalCommonPrice = DB::select('CALL getMfTotalData(STR_TO_DATE("'.$inputFromDate.'", "%m.%d.%Y"),STR_TO_DATE("'.$dates["toDate"]., "%m.%d.%Y") )');
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable .'_src_tcp_' . $typeID . '_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalDrugNames']) {
                    $data = Redis::get($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNames = json_decode($data);
                    } else {
                        $data = DB::select('CALL getDrugNames("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNames = $data;
                        Redis::set($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugNamesQty']) {
                    $data = Redis::get($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNamesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "drugs", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNamesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalTrademarks']) {
                    $data = Redis::get($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarks = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTrademarks("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarks = $data;
                        Redis::set($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalTrademarksQty']) {
                    $data = Redis::get($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarksQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "trademark", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarksQty = $data[0]->qty;
                        Redis::set($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalCompanies']) {
                    $data = Redis::get($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompanies = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalCompanies("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompanies = $data;
                        Redis::set($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalCompaniesQty']) {
                    $data = Redis::get($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompaniesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompaniesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' .$byTable . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugFormGroups']) {
                    $data = Redis::get($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugFormGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroups = $data;
                        Redis::set($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormGroupsQty']) {
                    $data = Redis::get($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dfg", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugTempGroups']) {
                    $data = Redis::get($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTsGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroups = $data;
                        Redis::set($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugTempGroupsQty']) {
                    $data = Redis::get($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dts", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalManufacturers']) {
                    $data = Redis::get($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturers = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalManufacturers("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturers = $data;
                        Redis::set($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalManufacturersQty']) {
                    $data = Redis::get($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturersQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "mf", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturersQty = $data[0]->qty;
                        Redis::set($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugInn']) {
                    $data = Redis::get($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInn = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugInn("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' .$byTable . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInn = $data;
                        Redis::set($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugInnQty']) {
                    $data = Redis::get($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInnQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "inn", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInnQty = $data[0]->qty;
                        Redis::set($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                #$item['period_' . ($index + 1)] = $periods;
            }
            array_push($result, $item);
        }
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

    public function getFilterByDistributorsById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $dataType = $inputs["dataType"] ?? 1;

        $byDataType = $inputs['dataType'] ?? 1;
        $byRegion = $inputs['region_id'] ?? "";
        $byDistrict = $inputs['district_id'] ?? "";

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'dist.name as name_uz',
            'd.name as drug_name',
            'mf.name AS manufacturer',
            'country.name AS country',
            't.name as trademark',
            'dt.name as drug_type',
            'df.name as drug_form',
            'dfg.name as drug_farm_group',
            'di.name as drug_inn',
            'c.name as sender_company',
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
                ["data_type", $dataType],
                ['drug_reports.m40d_id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
        }

        $data = $data->orderBy("drug_reports.mode_40_date", "ASC")
            ->paginate($inputs['limit']);

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
     * GET SENDER COMPANIES
     * @var bool is_active TRUE || FALSE
     * @var bool deleted TRUE || FALSE
     * @var array filterByDate - Object of array dates
     * @var object filterCol - Object of filtered columns
     * @var INT limit limit of returned data
     */
    public function getFilterByCompanies(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byDataType = $inputs['dataType'] ?? 1;
      
        $byTable = "sc";
        $idList = "";
        $count = 0;

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict =  "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }

        // Search by Types
        $typeID = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'. 
                    $typeID . '", ' .
                    $byDataType . ', "' .
                    $byRegion . '", "' .
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_'. $typeID.'_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $resData = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . 
                $count . ', ' . 
                $_limit . ', ' . 
                $currentPage . ', ' . 
                $isActive . ', ' . 
                $IsDeleted . ', "' . 
                $byTable . '", "' . 
                $idList . '", "' . 
                $_sortByDesc . '", "' . 
                $typeID . '", ' .
                $byDataType . ', "' .
                $byRegion . '", "' .
                $byDistrict . '")'
        );
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
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalDrugNames']) {
                    $data = Redis::get($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNames = json_decode($data);
                    } else {
                        $data = DB::select('CALL getDrugNames("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNames = $data;
                        Redis::set($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugNamesQty']) {
                    $data = Redis::get($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNamesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "drugs", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNamesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalTrademarks']) {
                    $data = Redis::get($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarks = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTrademarks("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarks = $data;
                        Redis::set($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalTrademarksQty']) {
                    $data = Redis::get($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarksQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "trademark", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarksQty = $data[0]->qty;
                        Redis::set($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDistributors']) {
                    $data = Redis::get($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributors = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDistributors("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "'. $byTable . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributors = $data;
                        Redis::set($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDistributorsQty']) {
                    $data = Redis::get($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributorsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dist", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributorsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "'. $byTable . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugFormGroups']) {
                    $data = Redis::get($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugFormGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "'. $byTable . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroups = $data;
                        Redis::set($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormGroupsQty']) {
                    $data = Redis::get($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dfg", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugTempGroups']) {
                    $data = Redis::get($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTsGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "'. $byTable . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroups = $data;
                        Redis::set($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugTempGroupsQty']) {
                    $data = Redis::get($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dts", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' .$inputToDate . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalManufacturers']) {
                    $data = Redis::get($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturers = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalManufacturers("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturers = $data;
                        Redis::set($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalManufacturersQty']) {
                    $data = Redis::get($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturersQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "mf", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturersQty = $data[0]->qty;
                        Redis::set($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugInn']) {
                    $data = Redis::get($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInn = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugInn("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInn = $data;
                        Redis::set($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugInnQty']) {
                    $data = Redis::get($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInnQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "inn", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInnQty = $data[0]->qty;
                        Redis::set($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
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


    public function getFilterByCompaniesById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $dataType = $inputs["dataType"] ?? 1;

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'c.name as name_uz',
            'd.name as drug_name',
            'mf.name AS manufacturer',
            'country.name AS country',
            't.name as trademark',
            'dt.name as drug_type',
            'df.name as drug_form',
            'dfg.name as drug_farm_group',
            'di.name as drug_inn',
            'dist.name as distributor',
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
                ["data_type", $dataType],
                ['drug_reports.sc_id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
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
     * GET  MANUFACTURERS
     * @var bool is_active TRUE || FALSE
     * @var bool deleted TRUE || FALSE
     * @var array filterByDate - Object of array dates
     * @var object filterCol - Object of filtered columns
     * @var INT limit limit of returned data
     */
    public function getFilterByManufacturers(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";
        
        $byDataType = $inputs['dataType'] ?? 1;
        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byTable = "mf";
        $idList = "";
        $count = 0;

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict =  "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }
        //$_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;

        // Search by Types
        $typeID = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'. 
                    $typeID . '", ' .
                    $byDataType . ', "' .
                    $byRegion . '", "' .
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_'. $typeID.'_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $resData = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . $count . ', ' . $_limit . ', ' . $currentPage . ', ' . $isActive . ', ' . $IsDeleted . ', "' . $byTable . '", "' . $idList . '", "' . $_sortByDesc . '", "' . 
                $typeID . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")'
        );
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
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalDrugNames']) {
                    $data = Redis::get($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNames = json_decode($data);
                    } else {
                        $data = DB::select('CALL getDrugNames("'. $inputFromDate . '", "'. $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNames = $data;
                        Redis::set($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugNamesQty']) {
                    $data = Redis::get($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNamesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "drugs", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNamesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalTrademarks']) {
                    $data = Redis::get($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarks = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTrademarks("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarks = $data;
                        Redis::set($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalTrademarksQty']) {
                    $data = Redis::get($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarksQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "trademark", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarksQty = $data[0]->qty;
                        Redis::set($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDistributors']) {
                    $data = Redis::get($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributors = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDistributors("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributors = $data;
                        Redis::set($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDistributorsQty']) {
                    $data = Redis::get($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributorsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributorsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugFormGroups']) {
                    $data = Redis::get($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugFormGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroups = $data;
                        Redis::set($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormGroupsQty']) {
                    $data = Redis::get($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dfg", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugTempGroups']) {
                    $data = Redis::get($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTsGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroups = $data;
                        Redis::set($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugTempGroupsQty']) {
                    $data = Redis::get($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dts", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalCompanies']) {
                    $data = Redis::get($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompanies = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalCompanies("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompanies = $data;
                        Redis::set($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalCompaniesQty']) {
                    $data = Redis::get($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompaniesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompaniesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugInn']) {
                    $data = Redis::get($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInn = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugInn("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInn = $data;
                        Redis::set($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugInnQty']) {
                    $data = Redis::get($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInnQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "inn", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInnQty = $data[0]->qty;
                        Redis::set($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
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

    public function getFilterByManufacturersById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $dataType = $inputs["dataType"] ?? 1;

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'mf.name AS name_uz',
            'country.name AS country',
            'd.name as drug_name',
            't.name as trademark',
            'dt.name as drug_type',
            'df.name as drug_form',
            'dfg.name as drug_farm_group',
            'di.name as drug_inn',
            'dist.name as distributor',
            'c.name as sender_company',
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
                ["data_type", $dataType],
                ['drug_reports.mf_id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
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
     * GET  INNS
     * @var bool is_active TRUE || FALSE
     * @var bool deleted TRUE || FALSE
     * @var array filterByDate - Object of array dates
     * @var object filterCol - Object of filtered columns
     * @var INT limit limit of returned data
     */
    public function getFilterByInns(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byDataType = $inputs['dataType'] ?? 1;
        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byTable = "inn";
        $idList = "";
        $count = 0;

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict =  "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }
        
        // Search by Types
        $typeID = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'. 
                    $typeID . '", ' .
                    $byDataType . ', "' .
                    $byRegion . '", "' .
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_'.$typeID.'_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $resData = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . $count . ', ' . $_limit . ', ' . $currentPage . ', ' . $isActive . ', ' . $IsDeleted . ', "' . $byTable . '", "' . $idList . '", "' . $_sortByDesc . '", "' . 
                $typeID . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")'
        );
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
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalDrugNames']) {
                    $data = Redis::get($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNames = json_decode($data);
                    } else {
                        $data = DB::select('CALL getDrugNames("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNames = $data;
                        Redis::set($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugNamesQty']) {
                    $data = Redis::get($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNamesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "drugs", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNamesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalTrademarks']) {
                    $data = Redis::get($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarks = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTrademarks("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarks = $data;
                        Redis::set($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalTrademarksQty']) {
                    $data = Redis::get($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarksQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "trademark", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarksQty = $data[0]->qty;
                        Redis::set($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDistributors']) {
                    $data = Redis::get($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributors = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDistributors("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributors = $data;
                        Redis::set($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDistributorsQty']) {
                    $data = Redis::get($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributorsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dist", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributorsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugFormGroups']) {
                    $data = Redis::get($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugFormGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroups = $data;
                        Redis::set($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormGroupsQty']) {
                    $data = Redis::get($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dfg", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugTempGroups']) {
                    $data = Redis::get($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTsGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroups = $data;
                        Redis::set($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugTempGroupsQty']) {
                    $data = Redis::get($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dts", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalCompanies']) {
                    $data = Redis::get($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompanies = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalCompanies("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompanies = $data;
                        Redis::set($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalCompaniesQty']) {
                    $data = Redis::get($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompaniesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompaniesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalManufacturers']) {
                    $data = Redis::get($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturers = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalManufacturers("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturers = $data;
                        Redis::set($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalManufacturersQty']) {
                    $data = Redis::get($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturersQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "mf", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturersQty = $data[0]->qty;
                        Redis::set($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
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

    public function getFilterByInnsById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $dataType = $inputs["dataType"] ?? 1;

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'di.name as name_uz',
            'd.name as drug_name',
            'mf.name as manufacturer',
            'country.name AS country',
            't.name as trademark',
            'dt.name as drug_type',
            'df.name as drug_form',
            'dfg.name as drug_farm_group',
            'dist.name as distributor',
            'c.name as sender_company',
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
                ["data_type", $dataType],
                ['d.di_id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
        }

        $data = $data->orderBy("drug_reports.mode_40_date", "ASC")
            ->paginate($inputs['limit']);

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
     * GET  DRUG FORMS
     * @var bool is_active TRUE || FALSE
     * @var bool deleted TRUE || FALSE
     * @var array filterByDate - Object of array dates
     * @var object filterCol - Object of filtered columns
     * @var INT limit limit of returned data
     */
    public function getFilterByDrugForms(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byDataType = $inputs['dataType'] ?? 1;
        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byTable = "df";
        $idList = "";
        $count = 0;

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict =  "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }

        // Search by Types
        $typeID = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }


        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'. 
                    $typeID . '", ' .
                    $byDataType . ', "' .
                    $byRegion . '", "' .
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_' . $typeID.'_'. $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $resData = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . $count . ', ' . $_limit . ', ' . $currentPage . ', ' . $isActive . ', ' . $IsDeleted . ', "' . $byTable . '", "' . $idList . '", "' . 
                $_sortByDesc . '", "' . 
                $typeID . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")'
        );
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
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalDrugNames']) {
                    $data = Redis::get($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNames = json_decode($data);
                    } else {
                        $data = DB::select('CALL getDrugNames("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNames = $data;
                        Redis::set($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugNamesQty']) {
                    $data = Redis::get($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNamesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "drugs", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNamesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalTrademarks']) {
                    $data = Redis::get($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarks = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTrademarks("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarks = $data;
                        Redis::set($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalTrademarksQty']) {
                    $data = Redis::get($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarksQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "trademark", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarksQty = $data[0]->qty;
                        Redis::set($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDistributors']) {
                    $data = Redis::get($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributors = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDistributors("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributors = $data;
                        Redis::set($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDistributorsQty']) {
                    $data = Redis::get($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributorsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dist", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributorsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugInn']) {
                    $data = Redis::get($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInn = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugInn("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInn = $data;
                        Redis::set($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugInnQty']) {
                    $data = Redis::get($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInnQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "inn", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInnQty = $data[0]->qty;
                        Redis::set($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugFormGroups']) {
                    $data = Redis::get($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugFormGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroups = $data;
                        Redis::set($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormGroupsQty']) {
                    $data = Redis::get($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dfg", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugTempGroups']) {
                    $data = Redis::get($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTsGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroups = $data;
                        Redis::set($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugTempGroupsQty']) {
                    $data = Redis::get($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dts", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalCompanies']) {
                    $data = Redis::get($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompanies = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalCompanies("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompanies = $data;
                        Redis::set($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalCompaniesQty']) {
                    $data = Redis::get($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompaniesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompaniesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalManufacturers']) {
                    $data = Redis::get($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturers = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalManufacturers("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturers = $data;
                        Redis::set($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalManufacturersQty']) {
                    $data = Redis::get($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturersQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "mf", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturersQty = $data[0]->qty;
                        Redis::set($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
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

    public function getFilterByDrugFormsById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $dataType = $inputs["dataType"] ?? 1;

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'df.name as name_uz',
            'd.name as drug_name',
            'mf.name as manufacturer',
            'country.name AS country',
            't.name as trademark',
            'dt.name as drug_type',
            'dfg.name as drug_farm_group',
            'di.name as drug_inn',
            'dist.name as distributor',
            'c.name as sender_company',
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
                ["data_type", $dataType],
                ['d.df_id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
        }

        $data = $data->orderBy("drug_reports.mode_40_date", "ASC")
            ->paginate($inputs['limit']);

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
     * GET  DRUG Drug Form Group
     * @var bool is_active TRUE || FALSE
     * @var bool deleted TRUE || FALSE
     * @var array filterByDate - Object of array dates
     * @var object filterCol - Object of filtered columns
     * @var INT limit limit of returned data
     */
    public function getFilterByDrugDFG(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byDataType = $inputs['dataType'] ?? 1;
        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byTable = "dfg";
        $idList = "";
        $count = 0;


        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict =  "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;

        // Search by Types
        $typeID = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'. 
                    $typeID . '", ' .
                    $byDataType . ', "' .
                    $byRegion . '", "' .
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_'.$typeID.'_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $resData = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . 
                $count . ', ' . $_limit . ', ' . $currentPage . ', ' . $isActive . ', ' . 
                $IsDeleted . ', "' . 
                $byTable . '", "' . 
                $idList . '", "' . 
                $_sortByDesc . '", "' . 
                $typeID . '", ' .
                $byDataType . ', "' .
                $byRegion . '", "' .
                $byDistrict . '")'
        );
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
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalDrugNames']) {
                    $data = Redis::get($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNames = json_decode($data);
                    } else {
                        $data = DB::select('CALL getDrugNames("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNames = $data;
                        Redis::set($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugNamesQty']) {
                    $data = Redis::get($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNamesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "drugs", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNamesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalTrademarks']) {
                    $data = Redis::get($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarks = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTrademarks("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarks = $data;
                        Redis::set($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalTrademarksQty']) {
                    $data = Redis::get($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarksQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "trademark", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarksQty = $data[0]->qty;
                        Redis::set($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDistributors']) {
                    $data = Redis::get($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributors = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDistributors("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributors = $data;
                        Redis::set($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDistributorsQty']) {
                    $data = Redis::get($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributorsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dist", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributorsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugInn']) {
                    $data = Redis::get($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInn = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugInn("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInn = $data;
                        Redis::set($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugInnQty']) {
                    $data = Redis::get($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInnQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "inn", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInnQty = $data[0]->qty;
                        Redis::set($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugTempGroups']) {
                    $data = Redis::get($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTsGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroups = $data;
                        Redis::set($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugTempGroupsQty']) {
                    $data = Redis::get($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dts", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalCompanies']) {
                    $data = Redis::get($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompanies = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalCompanies("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompanies = $data;
                        Redis::set($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalCompaniesQty']) {
                    $data = Redis::get($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompaniesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompaniesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalManufacturers']) {
                    $data = Redis::get($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturers = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalManufacturers("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturers = $data;
                        Redis::set($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalManufacturersQty']) {
                    $data = Redis::get($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturersQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "mf", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturersQty = $data[0]->qty;
                        Redis::set($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
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

    public function getFilterByDrugFormsGroupById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $dataType = $inputs["dataType"] ?? 1;

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'dfg.name as name_uz',
            'd.name as drug_name',
            'mf.name as manufacturer',
            'country.name AS country',
            't.name as trademark',
            'dt.name as drug_type',
            'df.name as drug_form',
            'di.name as drug_inn',
            'dist.name as distributor',
            'c.name as sender_company',
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
                ["data_type", $dataType],
                ['d.dfg_id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
        }

        $data = $data->orderBy("drug_reports.mode_40_date", "ASC")
            ->paginate($inputs['limit']);

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
     * GET  DRUG FORMS
     * @var bool is_active TRUE || FALSE
     * @var bool deleted TRUE || FALSE
     * @var array filterByDate - Object of array dates
     * @var object filterCol - Object of filtered columns
     * @var INT limit limit of returned data
     */
    public function getFilterByDrugDTG(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byDataType = $inputs['dataType'] ?? 1;
        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byTable = "dtg";
        $idList = "";
        $count = 0;

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict =  "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;

        // Search by Types
        $typeID = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'. 
                    $typeID . '", ' .
                    $byDataType . ', "' .
                    $byRegion . '", "' .
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_' .$typeID.'_'. $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $resData = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . 
                $count . ', ' . $_limit . ', ' . $currentPage . ', ' . $isActive . ', ' . $IsDeleted . ', "' . $byTable . '", "' . $idList . '", "' . 
                $_sortByDesc . '", "' . 
                $typeID . '", ' .
                $byDataType . ', "' .
                $byRegion . '", "' .
                $byDistrict . '")'
        );
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
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalDrugNames']) {
                    $data = Redis::get($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNames = json_decode($data);
                    } else {
                        $data = DB::select('CALL getDrugNames("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNames = $data;
                        Redis::set($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugNamesQty']) {
                    $data = Redis::get($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNamesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "drugs", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNamesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalTrademarks']) {
                    $data = Redis::get($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarks = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTrademarks("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarks = $data;
                        Redis::set($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalTrademarksQty']) {
                    $data = Redis::get($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarksQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "trademark", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarksQty = $data[0]->qty;
                        Redis::set($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDistributors']) {
                    $data = Redis::get($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributors = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDistributors("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributors = $data;
                        Redis::set($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDistributorsQty']) {
                    $data = Redis::get($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributorsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dist", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributorsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugInn']) {
                    $data = Redis::get($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInn = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugInn("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInn = $data;
                        Redis::set($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugInnQty']) {
                    $data = Redis::get($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInnQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "inn", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInnQty = $data[0]->qty;
                        Redis::set($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugFormGroups']) {
                    $data = Redis::get($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugFormGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroups = $data;
                        Redis::set($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormGroupsQty']) {
                    $data = Redis::get($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dfg", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalCompanies']) {
                    $data = Redis::get($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompanies = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalCompanies("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompanies = $data;
                        Redis::set($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalCompaniesQty']) {
                    $data = Redis::get($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompaniesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompaniesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalManufacturers']) {
                    $data = Redis::get($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturers = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalManufacturers("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturers = $data;
                        Redis::set($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalManufacturersQty']) {
                    $data = Redis::get($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturersQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "mf", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturersQty = $data[0]->qty;
                        Redis::set($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
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

    public function getFilterByDrugTsGroupById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $dataType = $inputs["dataType"] ?? 1;

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            'dtg.name as name_uz',
            'd.name as drug_name',
            'mf.name as manufacturer',
            'country.name AS country',
            't.name as trademark',
            'dt.name as drug_type',
            'df.name as drug_form',
            'dfg.name as drug_farm_group',
            'di.name as drug_inn',
            'dist.name as distributor',
            'c.name as sender_company',
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
                ["data_type", $dataType],
                ['d.dtg_id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
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
     * GET  DRUG Trademarks
     * @var bool is_active TRUE || FALSE
     * @var bool deleted TRUE || FALSE
     * @var array filterByDate - Object of array dates
     * @var object filterCol - Object of filtered columns
     * @var INT limit limit of returned data
     */
    public function getFilterByDrugTrademarks(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byDataType = $inputs['dataType'] ?? 1;
        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byTable = "trademark";
        $idList = "";
        $count = 0;

        //parse from string text date
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //$toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict =  "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;

        // Search by Types
        $typeID = "";
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }

       

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'. 
                    $typeID . '", ' .
                    $byDataType . ', "' .
                    $byRegion . '", "' .
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_'.$typeID.'_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $resData = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . 
                $count . ', ' . 
                $_limit . ', ' . 
                $currentPage . ', ' . 
                $isActive . ', ' . 
                $IsDeleted . ', "' . 
                $byTable . '", "' . 
                $idList . '", "' . 
                $_sortByDesc . '", "' . 
                $typeID . '", ' .
                $byDataType . ', "' .
                $byRegion . '", "' .
                $byDistrict . '")'
        );
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
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalDrugNames']) {
                    $data = Redis::get($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNames = json_decode($data);
                    } else {
                        $data = DB::select('CALL getDrugNames("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNames = $data;
                        Redis::set($byTable . '_src_tdn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugNamesQty']) {
                    $data = Redis::get($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugNamesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "drugs", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugNamesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tdnq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }

                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDistributors']) {
                    $data = Redis::get($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributors = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDistributors("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributors = $data;
                        Redis::set($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDistributorsQty']) {
                    $data = Redis::get($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributorsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributorsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugInn']) {
                    $data = Redis::get($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInn = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugInn("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInn = $data;
                        Redis::set($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugInnQty']) {
                    $data = Redis::get($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInnQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "inn", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInnQty = $data[0]->qty;
                        Redis::set($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugFormGroups']) {
                    $data = Redis::get($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugFormGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroups = $data;
                        Redis::set($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormGroupsQty']) {
                    $data = Redis::get($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dfg", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalCompanies']) {
                    $data = Redis::get($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompanies = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalCompanies("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompanies = $data;
                        Redis::set($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalCompaniesQty']) {
                    $data = Redis::get($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompaniesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompaniesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalManufacturers']) {
                    $data = Redis::get($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturers = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalManufacturers("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturers = $data;
                        Redis::set($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalManufacturersQty']) {
                    $data = Redis::get($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturersQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "mf", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturersQty = $data[0]->qty;
                        Redis::set($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
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

    public function getFilterByDrugTrademarkById(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $dataID = $inputs["dataID"];
        $dataType = $inputs["dataType"] ?? 1;

        //parse from string text date
        $fromDate = Carbon::parse($inputs['fromDate']);
        $toDate = Carbon::parse($inputs['toDate']);

        $data = DrugReport::select(
            't.name as name_uz',
            'd.name as drug_name',
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
                ["drug_reports.data_type", $dataType],
                ['d.trademark_id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
        }

        $data = $data->orderBy("drug_reports.mode_40_date", "ASC")
            ->paginate($inputs['limit']);

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
     * GET  DRUG Trademarks
     * @var bool is_active TRUE || FALSE
     * @var bool deleted TRUE || FALSE
     * @var array filterByDate - Object of array dates
     * @var object filterCol - Object of filtered columns
     * @var INT limit limit of returned data
     */
    public function getFilterByDrugs(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $_limit = $inputs["limit"];
        $_filterCols = $inputs['filterCol'];
        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? $_sortBy . " DESC" : $_sortBy . " ASC";

        $byDataType = $inputs['dataType'] ?? 1;
        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byRegion = "";
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = join(',', $inputs['region_id']);
        }
        $byDistrict =  "";
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = join(',', $inputs['district_id']);
        }

        $byTable = "drugs";
        $idList = "";
        $count = 0;

        //parse from string text date
        if ($_sortPeriod > count($request->filterByDate)) {
            $_sortPeriod = 1;
        }
        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        //Changed period filter by toDate last element
        $toDateFirst = Carbon::parse($request->filterByDate[count($request->filterByDate) - 1]['toDate'])->format('m.d.Y');

        $currentPage = $request->has('page') && (int)$inputs['page'] > 1 ? (((int)$inputs['page'] - 1) *  $_limit + 1) : 0;

        // Search by Types
        $typeID = "";
        if (isset($inputs['dtID']) && !empty($inputs['dtID'])) {
            $typeID = join(',', $inputs['dtID']);
        }
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $typeID = join(',', $typeIDList);
        }

        if (isset($inputs['dataIDList']) && !empty($inputs['dataIDList'])) {
            $count = count($inputs['dataIDList']);
            $idList = join(',', $inputs['dataIDList']);
        } else {
            $count = Redis::get($byTable . '_resCount_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
        }

        //Calculate data count for pagination
        if (!$count) {
            $counter = DB::select(
                'CALL getCalcCounts("' .
                    $fromDateFirst . '","' .
                    $toDateFirst . '",' .
                    $isActive . ', ' . 
                    $IsDeleted . ',"' . 
                    $byTable . '", "'. 
                    $typeID . '", ' .
                    $byDataType . ', "' .
                    $byRegion . '", "' .
                    $byDistrict.'")'
            );
            $count = $counter[0]->counts;
            Redis::set($byTable . '_resCount_' .$typeID.'_'. $fromDateFirst . '_' . $toDateFirst.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, $count, 'EX', 21600);
        }

        $resData = $this->cachedProc(
            'pdl_' . $byTable . '_' . $typeID . '_' . $fromDateFirst . '_' . $toDateFirst . '_' . $count . '_' . $_limit . '_' . $currentPage . '_' . $isActive . '_' . $IsDeleted . '_' . $idList . '_' . $_sortByDesc . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict,
            'CALL getPeriodDataList("' .
                $fromDateFirst  . '", "' .
                $toDateFirst  . '", ' . 
                $count . ', ' . 
                $_limit . ', ' . 
                $currentPage . ', ' . 
                $isActive . ', ' . 
                $IsDeleted . ', "' . 
                $byTable . '", "' . 
                $idList . '", "' . 
                $_sortByDesc . '", "' . 
                $typeID . '", ' .
                $byDataType . ', "' .
                $byRegion . '", "' .
                $byDistrict . '")'
        );
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
                if ($_filterCols['totalCommonPerPrice']) {
                    $data = Redis::get($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCommonPerPrice = json_decode($data);
                    } else {
                        $data = DB::select('CALL getCommonPerPrice("'. $inputFromDate. '", "'. $inputToDate.'", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "'. $typeID. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCommonPerPrice = $data[0] ?? null;
                        Redis::set($byTable . '_src_tcp_'. $typeID . '_' .  $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0] ?? null));
                    }
                }
                if ($_filterCols['totalTrademarks']) {
                    $data = Redis::get($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarks = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTrademarks("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarks = $data;
                        Redis::set($byTable . '_src_tr_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalTrademarksQty']) {
                    $data = Redis::get($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalTrademarksQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "trademark", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalTrademarksQty = $data[0]->qty;
                        Redis::set($byTable . '_src_trq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDistributors']) {
                    $data = Redis::get($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributors = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDistributors("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributors = $data;
                        Redis::set($byTable . '_src_dist_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDistributorsQty']) {
                    $data = Redis::get($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDistributorsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDistributorsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dist_q_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugInn']) {
                    $data = Redis::get($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInn = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugInn("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInn = $data;
                        Redis::set($byTable . '_src_inn_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugInnQty']) {
                    $data = Redis::get($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugInnQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "di", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugInnQty = $data[0]->qty;
                        Redis::set($byTable . '_src_innq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugForms']) {
                    $data = Redis::get($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugForms = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugForms("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugForms = $data;
                        Redis::set($byTable . '_src_df_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormsQty']) {
                    $data = Redis::get($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "df", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugFormGroups']) {
                    $data = Redis::get($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalDrugFormGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroups = $data;
                        Redis::set($byTable . '_src_dfg_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugFormGroupsQty']) {
                    $data = Redis::get($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugFormGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dfg", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugFormGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_dfgq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalCompanies']) {
                    $data = Redis::get($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompanies = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalCompanies("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompanies = $data;
                        Redis::set($byTable . '_src_c_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalCompaniesQty']) {
                    $data = Redis::get($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalCompaniesQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "sc", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalCompaniesQty = $data[0]->qty;
                        Redis::set($byTable . '_src_cq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalDrugTempGroups']) {
                    $data = Redis::get($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroups = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalTsGroups("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroups = $data;
                        Redis::set($byTable . '_src_tp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalDrugTempGroupsQty']) {
                    $data = Redis::get($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalDrugTempGroupsQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "dts", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalDrugTempGroupsQty = $data[0]->qty;
                        Redis::set($byTable . '_src_tpq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
                }
                if ($_filterCols['totalManufacturers']) {
                    $data = Redis::get($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturers = json_decode($data);
                    } else {
                        $data = DB::select('CALL getTotalManufacturers("' . $inputFromDate . '","' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable. '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturers = $data;
                        Redis::set($byTable . '_src_mf_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data));
                    }
                }
                //qty
                if ($_filterCols['totalManufacturersQty']) {
                    $data = Redis::get($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict);
                    if ($data) {
                        $item['period_' . $counter]->totalManufacturersQty = json_decode($data);
                    } else {
                        $data = DB::select('CALL getQtyData("' . $inputFromDate . '", "' . $inputToDate . '", ' . $item['id'] . ', ' . $isActive . ',' . $IsDeleted . ', "' . $byTable .  '", "mf", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');
                        $item['period_' . $counter]->totalManufacturersQty = $data[0]->qty;
                        Redis::set($byTable . '_src_mfq_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate.'_'.$byDataType.'_'.$byRegion.'_'.$byDistrict, json_encode($data[0]->qty));
                    }
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
        $dataType = $inputs["dataType"] ?? 1;

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
                ["data_type", $dataType],
                ['d.id', $dataID],
                ['drug_reports.is_active', $isActive],
                ['drug_reports.is_deleted', $IsDeleted],
            ]);

        //Added search by Types
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($typeID)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $data->whereIn('d.dt_id', $typeIDList);
        }
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereIn('d.dt_id', $inputs['dtID']);
        }

        $data = $data->orderBy("drug_reports.mode_40_date", "ASC")
            ->paginate($inputs['limit']);

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
