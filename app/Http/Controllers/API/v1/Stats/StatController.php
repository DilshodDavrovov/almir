<?php

namespace App\Http\Controllers\API\v1\Stats;

use App\Http\Controllers\Controller;
use App\Http\Resources\GetDataCollection;
use App\Models\Distributor;
use App\Models\Drug;
use App\Models\DrugFarmGroup;
use App\Models\DrugForm;
use App\Models\DrugInn;
use App\Models\Drugs\Company;
use App\Models\Drugs\DrugType;
use App\Models\DrugTsGroup;
use App\Models\Manufacturer;
use App\Models\Trademark;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class StatController extends Controller
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
     * GET STAT LISTS
     */
    public function getStatPeriod(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $byTable = $inputs["byTable"];
        $byLimit = $inputs["limit"] ?? 5;

        $dtIdList = [];
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($dtIdList)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $dtIdList = $typeIDList; //join(',', $typeIDList);
            if (empty($dtIdList)) {
                $dtIdList[] = 500000000;
            }
        }

        $result = [];

        switch ($inputs["byTable"]) {
            case "distributor":
                $byTable = "dist";
                break;
            case "manufacturer":
                $byTable = "mf";
                break;
            case "company":
                $byTable = "sc";
                break;
            case "inn":
                $byTable = "inn";
                break;
            case "drug":
                $byTable = "drug";
                break;
            case "drug_type":
                $byTable = "dt";
                break;
            case "drug_form":
                $byTable = "df";
                break;
            case "dtg":
                $byTable = "dtg";
                break;
            case "dfg":
                $byTable = "dfg";
                break;
            case "trademark":
                $byTable = "trademark";
                break;
        }

        //parse from string text date
        $fromDate = Carbon::parse($inputs["fromDate"])->format('m.d.Y');
        $toDate = Carbon::parse($inputs["toDate"])->format('m.d.Y');
        
        //StatDrug
        $statDrugs = json_decode(Redis::get('stat_' . $byTable . '_' . $fromDate .  '_' . $toDate));
        if (empty($statDrugs)) {
            $statDrugs = DB::select(
                'CALL getStatList("' . $fromDate . '","' . $toDate . '",' . $isActive . ',' . $IsDeleted . ',"' . $byTable . '",' . $byLimit . ',"' . join(',', $dtIdList) . '")'
            );
            Redis::set('stat_' .$byTable . '_"' . join(',', $dtIdList) . '"_' . $fromDate .  '_' . $toDate, json_encode($statDrugs ?? null));
        }

        $item['statDrug'] = new \stdClass();
        $item['statDrug'] = $statDrugs;
        $result[] = $item['statDrug'] ?? [];
        //array_push($result, $item);
        return _sendResponse(201, "Success", $result[0]);
    }

    /**
     * GET STAT LISTS
     */
    public function getStatPeriodList(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $byTableList = $inputs["byTableList"];
        $byLimit = $inputs["limit"] ?? 5;

        $dtIdList = [];
        if ((!$request->user()->hasRole('admin') && !$request->user()->hasRole('employe')) && empty($dtIdList)) {
            $typeIDList = [];
            foreach (\Auth::user()->access as $item) {
                $typeIDList[] = $item->type_id;
            }
            $dtIdList = $typeIDList; //join(',', $typeIDList);
            if (empty($dtIdList)) {
                $dtIdList[] = 500000000;
            }
        }

        $result = [];
        foreach ($byTableList as $item) {
            $byTable = $item ?? null;

            // switch ($byTable) {
            //     case "distributor":
            //         $byTable = "dist";
            //         break;
            //     case "manufacturer":
            //         $byTable = "mf";
            //         break;
            //     case "company":
            //         $byTable = "sc";
            //         break;
            //     case "inn":
            //         $byTable = "inn";
            //         break;
            //     case "drug":
            //         $byTable = "drug";
            //         break;
            //     case "drug_type":
            //         $byTable = "dt";
            //         break;
            //     case "drug_form":
            //         $byTable = "df";
            //         break;
            //     case "dtg":
            //         $byTable = "dtg";
            //         break;
            //     case "dfg":
            //         $byTable = "dfg";
            //         break;
            //     case "trademark":
            //         $byTable = "trademark";
            //         break;
            // }
            //parse from string text date
            $fromDate = Carbon::parse($inputs["fromDate"])->format('m.d.Y');
            $toDate = Carbon::parse($inputs["toDate"])->format('m.d.Y');
         
            $statDrugs = json_decode(Redis::get('stat_list_' . $byTable . $fromDate .  '_' . $toDate));
            if (empty($statDrugs)) {
                $statDrugs = DB::select(
                    'CALL getStatList("' . $fromDate . '","' . $toDate . '",' . $isActive . ',' . $IsDeleted . ',"' . $byTable . '",' . $byLimit . ', "'. join(',', $dtIdList) . '")'
                );
                Redis::set('stat_list_' . $byTable. '_"'. join(',', $dtIdList) . '"_'. $fromDate .  '_' . $toDate, json_encode($statDrugs ?? null));
            }

            //$item['statDrug'] = new \stdClass();
            //$item['statDrug'] = $statDrugs;
            $result[$byTable] = $statDrugs ?? [];
        }

        //array_push($result, $item);
        return _sendResponse(201, "Success", $result);
    }

    //Get Uniques stats

    public function getDataCounts(Request $request)
    {
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["deleted"] == true ? 1 : 0;
        $result = [];

        $item['totalDrugs'] = Drug::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalInn'] = DrugInn::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalManufacturer'] = Manufacturer::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalDistributor'] = Distributor::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalCompany'] = Company::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalDrugType'] = DrugType::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalTrademark'] = Trademark::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalDTG'] = DrugTsGroup::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalDFG'] = DrugFarmGroup::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        $item['totalDrugForm'] = DrugForm::where([['is_active', $isActive], ['deleted', $IsDeleted]])->count();
        //$result[] = $item['statDrug'];
        array_push($result, $item);
        return _sendResponse(201, "Success", $result[0]);
    }
}
