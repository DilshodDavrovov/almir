<?php

namespace App\Http\Controllers\API\v1\Stats;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\Users\UserActivity;
use Auth;
use Illuminate\Support\Str;

class GraphController extends Controller
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
     * GET DATA BY FILTER FEILDS
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function getDataGraphByFilterOld(Request $request)
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

        $idList = "";
        if (isset($inputs['drugID']) && !empty($inputs['drugID'])) {
            $idList = join(',', $inputs['drugID']);
        }

        $resData = DB::select('CALL getDataByFilterPeriod("' . $fromDateFirst . '", "' . $toDateFirst . '","' . $idList . '","' . $innIdList . '","' . $tIdList . '","' . $distIdList . '","' . $dfIdList . '","' . $dfgIdList . '","' . $dtIdList . '","' . $dtgIdList . '","' . $scIdList . '","' . $mfIdList . '","' . $cIdList . '", ' . $isActive . ',' . $IsDeleted . ',' . $_limit . ',' . $currentPage . ',"' . $_sortByDesc . '", "' . $_filterBy . '", ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');

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
                $periodData = Redis::get($byTable . $_filterBy  . '_src_tcp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict);

                if ($periodData) {
                    $item['period_' . $counter]->totalCommonPerPrice = json_decode($periodData);
                } else {
                    $periodData = DB::select('CALL getDataByFilterList("' . $inputFromDate . '", "' . $inputToDate . '","' . $idList . '","' . $innIdList . '","' . $tIdList . '","' . $distIdList . '","' . $dfIdList . '","' . $dfgIdList . '","' . $dtIdList . '","' . $dtgIdList . '","' . $scIdList . '","' . $mfIdList . '","' . $cIdList . '", ' . $isActive . ',' . $IsDeleted . ',"' . $_filterBy . '",' . $item['id'] . ', ' . $byDataType . ', "' . $byRegion . '", "' . $byDistrict . '")');

                    $item['period_' . $counter]->totalCommonPerPrice = $periodData[0] ?? null;
                    Redis::set($byTable . $_filterBy  . '_src_tcp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate . '_' . $idList . '_' . $innIdList . '_' . $tIdList . '_' . $distIdList . '_' . $dfIdList . '_' . $dfgIdList . '_' . $tIdList . '_' . $dtgIdList . '_' . $scIdList . '_' . $mfIdList . '_' . $cIdList, json_encode($periodData[0] ?? null));
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

    public function getDataGraphByFilter(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $isDeleted = $inputs["is_deleted"] == true ? 1 : 0;
        $periodType = $inputs["periodType"] ?? 'total';
        $_limit = (int) $inputs["limit"];
        $_filterBy = Str::lower($inputs['filterBy'] ?? 'drug_name');

        $byDataType = (int) ($inputs['dataType'] ?? 1);
        // $byRegion = $inputs['region_id'] ?? "";
        // $byDistrict = $inputs['district_id'] ?? "";

        $byRegion = "";
        if (isset($inputs['regionID']) && !empty($inputs['regionID'])) {
            $byRegion = join(',', $inputs['regionID']);
        }
        $byDistrict = "";
        if (isset($inputs['districtID']) && !empty($inputs['districtID'])) {
            $byDistrict = join(',', $inputs['districtID']);
        }
        //o'chiriladi keyinchalik
        if (isset($inputs['region_id']) && !empty($inputs['region_id'])) {
            $byRegion = $inputs['region_id']; //join(',', $inputs['region_id']);
        }
        if (isset($inputs['district_id']) && !empty($inputs['district_id'])) {
            $byDistrict = $inputs['district_id']; //join(',', $inputs['district_id']);
        }

        $_sortPeriod = $request->has('sortPeriod') && $inputs['sortPeriod'] <= 4 ? (int) $inputs['sortPeriod'] : 1;
        $_sortBy = $request->has('sortBy') && $inputs['sortBy'] ? $inputs['sortBy'] : "USD";
        $_sortByDesc = $request->has('sortByDesc') && $inputs['sortByDesc'] ? "$_sortBy DESC" : "$_sortBy ASC";

        $fromDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['fromDate'])->format('m.d.Y');
        $toDateFirst = Carbon::parse($request->filterByDate[$_sortPeriod - 1]['toDate'])->format('m.d.Y');

        $filterKeys = ['innID', 'trademarkID', 'distID', 'dfID', 'dfgID', 'dtID', 'dtgID', 'companyID', 'mfID', 'countryID', 'drugID'];
        $filterValues = [];
        foreach ($filterKeys as $key) {
            $filterValues[$key] = isset($inputs[$key]) && !empty($inputs[$key]) ? implode(',', $inputs[$key]) : "";
        }

        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employee')) && empty($filterValues['dtID'])) {
            $typeIDList = array_map(fn($item) => $item->type_id, Auth::user()->access);
            $filterValues['dtID'] = implode(',', $typeIDList);
        }

        $resData = DB::select('CALL graphDataByFilterPeriod(
            "' . $fromDateFirst . '",
             "' . $toDateFirst . '",
            "' . $filterValues['drugID'] . '",
            "' . $filterValues['innID'] . '",
            "' . $filterValues['trademarkID'] . '",
            "' . $filterValues['distID'] . '",
            "' . $filterValues['dfID'] . '",
            "' . $filterValues['dfgID'] . '",
            "' . $filterValues['dtID'] . '",
            "' . $filterValues['dtgID'] . '",
            "' . $filterValues['companyID'] . '",
            "' . $filterValues['mfID'] . '",
            "' . $filterValues['countryID'] . '",
            ' . $isActive . ',
            ' . $isDeleted . ',
            ' . $_limit . ',
            "' . $_sortByDesc . '", 
            "' . $_filterBy . '", 
            ' . $byDataType . ', 
            "' . $byRegion . '", 
            "' . $byDistrict . '")'
        );

        $resData = json_decode(json_encode($resData), true);
        $result = [];
        $count = count($resData);

        foreach ($resData as $item) {
            unset($item['USD'], $item['qty']);
            foreach ($request->filterByDate as $index => $dates) {
                $counter = $index + 1;
                $inputFromDate = Carbon::parse($dates['fromDate'])->format('m.d.Y');
                $inputToDate = Carbon::parse($dates['toDate'])->format('m.d.Y');

                $item['period_' . $counter] = new \stdClass();

                $cacheKey = "sfs_". $periodType. '_' . $_filterBy . '_src_tcp_' . $item['id'] . '_' . $inputFromDate . '_' . $inputToDate . '_' . $byDataType . '_' . $byRegion . '_' . $byDistrict;

                $periodData = DB::select(
                    'CALL graphDataByFilterList(
                        "' . $inputFromDate . '", 
                        "' . $inputToDate . '",
                        "' . $filterValues['drugID'] . '",
                        "' . $filterValues['innID'] . '",
                        "' . $filterValues['trademarkID'] . '",
                        "' . $filterValues['distID'] . '",
                        "' . $filterValues['dfID'] . '",
                        "' . $filterValues['dfgID'] . '",
                        "' . $filterValues['dtID'] . '",
                        "' . $filterValues['dtgID'] . '",
                        "' . $filterValues['companyID'] . '",
                        "' . $filterValues['mfID'] . '",
                        "' . $filterValues['countryID'] . '",
                        ' . $isActive . ',
                        ' . $isDeleted . ',
                        "' . $_filterBy . '",
                        ' . $item['id'] . ', 
                        ' . $byDataType . ', 
                        "' . $byRegion . '", 
                        "' . $byDistrict . '", 
                        "' . $periodType . '")'
                );

                $startDate = Carbon::parse($dates['fromDate']);
                $endDate = Carbon::parse($dates['toDate']);

                // Agar $periodData stdClass bo'lsa, uni massivga aylantiramiz
                if (is_object($periodData) || (isset($periodData[0]) && is_object($periodData[0]))) {
                    $periodData = json_decode(json_encode($periodData), true);
                }
                // Bo‘sh qiymatli array yaratish
                $missingData = [];

                if (!empty($periodData) && $periodType == 'monthly') {
                    // 1. Oyma-oy qo‘shish
                    for ($date = $startDate->copy(); $date->lte($endDate); $date->addMonth()) {
                        $exists = false;
                        foreach ($periodData as $data) {
                            if (isset($data['month']) && $data['year'] == $date->year && $data['month'] == $date->month) {
                                $exists = true;
                                break;
                            }
                        }

                        if (!$exists) {
                            $missingData[] = [
                                'year' => $date->year,
                                'month' => $date->month,
                                'sum_price_usd' => "0",
                                'sum_price_uzs' => "0",
                                'sum_price_eur' => "0",
                                'sum_price_rub' => "0",
                                'quantity' => "0"
                            ];
                        }
                    }
                    // Final array
                    $periodData = array_merge($periodData, $missingData);
                    // **Saralash funksiyasi (Yil -> Kvartal -> Oy tartibi)**
                    usort($periodData, function ($a, $b) {
                        if ($a['year'] == $b['year']) {
                            return $a['month'] - $b['month']; // Oy bo‘yicha tartiblash
                        }
                    });

                    $item['period_' . $counter]->totalCommonPerPriceByMonth = $periodData ?? null;
                    //Redis::set($cacheKey, json_encode($periodData ?? null));
                } else if (!empty($periodData) && $periodType == 'quarterly') {
                    // 2. Kvartallarni tekshirish va qo‘shish
                    for ($date = $startDate->copy(); $date->lte($endDate); $date->addQuarter()) {
                        $exists = false;
                        foreach ($periodData as $data) {
                            if (isset($data['quarter']) && $data['year'] == $date->year && $data['quarter'] == $date->quarter) {
                                $exists = true;
                                break;
                            }
                        }

                        if (!$exists) {
                            $missingData[] = [
                                'year' => $date->year,
                                'quarter' => $date->quarter,
                                'sum_price_usd' => "0",
                                'sum_price_uzs' => "0",
                                'sum_price_eur' => "0",
                                'sum_price_rub' => "0",
                                'quantity' => "0"
                            ];
                        }
                    }
                    // Final array
                    $periodData = array_merge($periodData, $missingData);
                    // **Saralash funksiyasi (Yil -> Kvartal -> Oy tartibi)**
                    usort($periodData, function ($a, $b) {
                        if ($a['year'] == $b['year']) {
                            if ($a['quarter'] == $b['quarter']) {
                                return $a['month'] - $b['month']; // Oy bo‘yicha tartiblash
                            }
                            return $a['quarter'] - $b['quarter']; // Kvartal bo‘yicha tartiblash
                        }
                        return $a['year'] - $b['year']; // Yil bo‘yicha tartiblash
                    });
                    $item['period_' . $counter]->totalCommonPerPriceByQuarter = $periodData ?? null;
                    //Redis::set($cacheKey, json_encode($periodData ?? null));
                } else {
                    if(!empty($periodData) && isset($periodData[0]['sum_price_usd'])) {
                        $periodData[0]['sum_price_usd'] = number_format($periodData[0]['sum_price_usd'], 2, '.', '');
                        $periodData[0]['sum_price_uzs'] = number_format($periodData[0]['sum_price_uzs'], 2, '.', '');
                        $periodData[0]['sum_price_eur'] = number_format($periodData[0]['sum_price_eur'], 2, '.', '');
                        $periodData[0]['sum_price_rub'] = number_format($periodData[0]['sum_price_rub'], 2, '.', '');
                        $periodData[0]['quantity'] = number_format($periodData[0]['quantity'], 2, '.', '');
                    }
                    else {
                        $periodData[0] = [
                            'sum_price_usd' => "0",
                            'sum_price_uzs' => "0",
                            'sum_price_eur' => "0",
                            'sum_price_rub' => "0",
                            'quantity' => "0"
                        ];
                    }
                    $item['period_' . $counter]->totalCommonPerPrice = $periodData[0];
                    //Redis::set($cacheKey, json_encode($periodData[0] ?? null));
                }

            }
            $result[] = $item;
        }

        if ($request->getContent()) {
            UserActivity::create([
                'name' => $request->filterByDate[0]['fromDate'] . ' - ' . $request->filterByDate[0]['toDate'],
                'by_table' => 'sfs',
                'user_id' => Auth::user()->id,
                'body' => $request->getContent()
            ]);
        }

        return _responseGraphPeriods(201, $result, $count);
    }

}
