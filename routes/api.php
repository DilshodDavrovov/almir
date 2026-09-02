<?php

use App\Http\Controllers\API\v1\AuthController;
use App\Http\Controllers\API\v1\CompanyController;
use App\Http\Controllers\API\v1\ContrahenController;
use App\Http\Controllers\API\v1\UserController;
use App\Http\Controllers\API\v1\CountryController;
use App\Http\Controllers\API\v1\DistController;
use App\Http\Controllers\API\v1\DFGController;
use App\Http\Controllers\API\v1\DFController;
use App\Http\Controllers\API\v1\DTController;
use App\Http\Controllers\API\v1\DrugInnController;
use App\Http\Controllers\API\v1\ManufacturerController;
use App\Http\Controllers\API\v1\TMController;
use App\Http\Controllers\API\v1\TPGController;
use App\Http\Controllers\API\v1\DrugController;
use App\Http\Controllers\API\v1\DRController;
use App\Http\Controllers\API\v1\FilterController;
use App\Http\Controllers\API\v1\Filters\SearchController;
use App\Http\Controllers\API\v1\News\NewsController;
use App\Http\Controllers\API\v1\Region\DistrictController;
use App\Http\Controllers\API\v1\Region\RegionController;
use App\Http\Controllers\API\v1\Stats\GraphController;
use App\Http\Controllers\API\v1\Stats\StatController;
use App\Http\Controllers\API\v1\System\SettingsController;
use App\Http\Controllers\API\v1\Analytics\AnalyticsController;
use App\Http\Controllers\API\v1\UserActions\ActionController;
use App\Http\Controllers\API\v1\UserActions\ActivityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('v2.0/register', 'register');
    Route::post('v2.0/login', 'login');
    Route::get('v2.0/contact/info', 'getContactInfo');
});

Route::controller(CompanyController::class)->middleware('auth:sanctum')->group(function () {
    Route::post('v2.0/company/add', 'AddData');
});

Route::group(['prefix' => 'v2.0', 'middleware' => ['auth:sanctum']], function () {

    //USER
    Route::controller(UserController::class)->group(function () {
        Route::post('user/update/avatar', 'UpdateAvatar');
        Route::post('user/all', 'GetUserList');
        Route::get('user/get/{DataID}', 'GetByID');
        Route::put('user/update/info', 'UpdateUserInfo');
        Route::put('user/update/password', 'UpdateUserPassword');
        Route::get('user/my/info', 'GetUserInfo');
        Route::put('user/status/{DataID}', 'ChangeStatus');
        Route::put('user/delete/{DataID}', 'RemoveDataByID');

        Route::put('user/update/access/{DataID}', 'setUserAccess');

    });

    //User Action
    Route::controller(ActionController::class)->group(function () {
        Route::get('user/action', 'getData');
        Route::post('user/action', 'AddData');
        Route::delete('user/action/delete', 'RemoveDataByID');
    });
    
    //User Action
    Route::controller(ActivityController::class)->group(function () {
        Route::get('user/activity', 'getData');
        Route::delete('user/activity/delete', 'RemoveDataByID');
    });

    //News
    Route::controller(NewsController::class)->group(function () {
        Route::get('news/get/{DataID}', 'GetByID');
        Route::get('news', 'getSearchKeyword');
        Route::post('news/all', 'getData');
        Route::post('news/add', 'AddData');
        Route::post('news/update/{DataID}', 'AddData');
        Route::put('news/status/{DataID}', 'ChangeStatus');
        Route::delete('news/delete/{DataID}', 'RemoveDataByID');

        Route::post('news/bulk/import', 'ImportBulkData');
        Route::delete('news/bulk/remove', 'RemoveIdList');
        Route::put('news/bulk/status', 'RemoveListStatus');
    });

    //Company
    Route::controller(CompanyController::class)->group(function () {
        Route::get('company/get/{DataID}', 'GetByID');
        Route::get('company', 'getSearchKeyword');
        Route::post('company/all', 'getData');
        Route::post('company/add', 'AddData');
        Route::put('company/update/{DataID}', 'AddData');
        Route::put('company/status/{DataID}', 'ChangeStatus');
        Route::delete('company/delete/{DataID}', 'RemoveDataByID');

        Route::post('company/bulk/import', 'ImportBulkData');
        Route::delete('company/bulk/remove', 'RemoveIdList');
        Route::put('company/bulk/status', 'RemoveListStatus');
    });

    //Country
    Route::controller(CountryController::class)->group(function () {
        Route::get('country/get/{DataID}', 'GetByID');
        Route::get('country', 'getSearchKeyword');
        Route::post('country/all', 'getData');
        Route::post('country/add', 'AddData');
        Route::put('country/update/{DataID}', 'AddData');
        Route::put('country/status/{DataID}', 'ChangeStatus');
        Route::delete('country/delete/{DataID}', 'RemoveDataByID');

        Route::post('country/bulk/import', 'ImportBulkData');
        Route::delete('country/bulk/remove', 'RemoveIdList');
        Route::put('country/bulk/status', 'RemoveListStatus');
    });

    //Region
    Route::controller(RegionController::class)->group(function () {
        Route::get('region/get/{DataID}', 'GetByID');
        Route::get('region', 'getSearchKeyword');
        Route::post('region/all', 'getData');
        Route::post('region/add', 'AddData');
        Route::put('region/update/{DataID}', 'AddData');
        Route::put('region/status/{DataID}', 'ChangeStatus');
        Route::delete('region/delete/{DataID}', 'RemoveDataByID');

        Route::post('region/bulk/import', 'ImportBulkData');
        Route::delete('region/bulk/remove', 'RemoveIdList');
        Route::put('region/bulk/status', 'RemoveListStatus');
    });

    //District
    Route::controller(DistrictController::class)->group(function () {
        Route::get('district/get/{DataID}', 'GetByID');
        Route::get('district', 'getSearchKeyword');
        Route::post('district/all', 'getData');
        Route::post('district/add', 'AddData');
        Route::put('district/update/{DataID}', 'AddData');
        Route::put('district/status/{DataID}', 'ChangeStatus');
        Route::delete('district/delete/{DataID}', 'RemoveDataByID');

        Route::post('district/bulk/import', 'ImportBulkData');
        Route::delete('district/bulk/remove', 'RemoveIdList');
        Route::put('district/bulk/status', 'RemoveListStatus');
    });

    //Distributors
    Route::controller(DistController::class)->group(function () {
        Route::get('dist/get/{DataID}', 'GetByID');
        Route::get('dist', 'getSearchKeyword');
        Route::post('dist/all', 'getData');
        Route::post('dist/add', 'AddData');
        Route::put('dist/update/{DataID}', 'AddData');
        Route::put('dist/status/{DataID}', 'ChangeStatus');
        Route::delete('dist/delete/{DataID}', 'RemoveDataByID');

        Route::post('dist/bulk/import', 'ImportBulkData');
        Route::delete('dist/bulk/remove', 'RemoveIdList');
        Route::put('dist/bulk/status', 'RemoveListStatus');
    });

    //Drug Farm Group
    Route::controller(DFGController::class)->group(function () {
        Route::get('dfg/get/{DataID}', 'GetByID');
        Route::get('dfg', 'getSearchKeyword');
        Route::post('dfg/all', 'getData');
        Route::post('dfg/add', 'AddData');
        Route::put('dfg/update/{DataID}', 'AddData');
        Route::put('dfg/status/{DataID}', 'ChangeStatus');
        Route::delete('dfg/delete/{DataID}', 'RemoveDataByID');

        Route::post('dfg/bulk/import', 'ImportBulkData');
        Route::delete('dfg/bulk/remove', 'RemoveIdList');
        Route::put('dfg/bulk/status', 'RemoveListStatus');
    });

    //Drug form
    Route::controller(DFController::class)->group(function () {
        Route::get('df/get/{DataID}', 'GetByID');
        Route::get('df', 'getSearchKeyword');
        Route::post('df/all', 'getData');
        Route::post('df/add', 'AddData');
        Route::put('df/update/{DataID}', 'AddData');
        Route::put('df/status/{DataID}', 'ChangeStatus');
        Route::delete('df/delete/{DataID}', 'RemoveDataByID');

        Route::post('df/bulk/import', 'ImportBulkData');
        Route::delete('df/bulk/remove', 'RemoveIdList');
        Route::put('df/bulk/status', 'RemoveListStatus');
    });

    //Drug Type
    Route::controller(DTController::class)->group(function () {
        Route::get('dt/get/{DataID}', 'GetByID');
        Route::get('dt', 'getSearchKeyword');
        Route::post('dt/all', 'getData');
        Route::post('dt/add', 'AddData');
        Route::put('dt/update/{DataID}', 'AddData');
        Route::put('dt/status/{DataID}', 'ChangeStatus');
        Route::delete('dt/delete/{DataID}', 'RemoveDataByID');

        Route::post('dt/bulk/import', 'ImportBulkData');
        Route::delete('dt/bulk/remove', 'RemoveIdList');
        Route::put('dt/bulk/status', 'RemoveListStatus');
    });

    //Drug Inn
    Route::controller(DrugInnController::class)->group(function () {
        Route::get('inn/get/{DataID}', 'GetByID');
        Route::get('inn', 'getSearchKeyword');
        Route::post('inn/all', 'getData');
        Route::post('inn/add', 'AddData');
        Route::put('inn/update/{DataID}', 'AddData');
        Route::put('inn/status/{DataID}', 'ChangeStatus');
        Route::delete('inn/delete/{DataID}', 'RemoveDataByID');

        Route::post('inn/bulk/import', 'ImportBulkData');
        Route::delete('inn/bulk/remove', 'RemoveIdList');
        Route::put('inn/bulk/status', 'RemoveListStatus');
    });

    //Drug ManufacturerController
    Route::controller(ManufacturerController::class)->group(function () {
        Route::get('mf/get/{DataID}', 'GetByID');
        Route::get('mf', 'getSearchKeyword');
        Route::post('mf/all', 'getData');
        Route::post('mf/add', 'AddData');
        Route::put('mf/update/{DataID}', 'AddData');
        Route::put('mf/status/{DataID}', 'ChangeStatus');
        Route::delete('mf/delete/{DataID}', 'RemoveDataByID');

        Route::post('mf/bulk/import', 'ImportBulkData');
        Route::delete('mf/bulk/remove', 'RemoveIdList');
        Route::put('mf/bulk/status', 'RemoveListStatus');
    });

    //Терапевтический группа
    Route::controller(TPGController::class)->group(function () {
        Route::get('tpg/get/{DataID}', 'GetByID');
        Route::get('tpg', 'getSearchKeyword');
        Route::post('tpg/all', 'getData');
        Route::post('tpg/add', 'AddData');
        Route::put('tpg/update/{DataID}', 'AddData');
        Route::put('tpg/status/{DataID}', 'ChangeStatus');
        Route::delete('tpg/delete/{DataID}', 'RemoveDataByID');

        Route::post('tpg/bulk/import', 'ImportBulkData');
        Route::delete('tpg/bulk/remove', 'RemoveIdList');
        Route::put('tpg/bulk/status', 'RemoveListStatus');
    });

    //Drug TMController
    Route::controller(TMController::class)->group(function () {
        Route::get('trademark/get/{DataID}', 'GetByID');
        Route::get('trademark', 'getSearchKeyword');
        Route::post('trademark/all', 'getData');
        Route::post('trademark/add', 'AddData');
        Route::put('trademark/update/{DataID}', 'AddData');
        Route::put('trademark/status/{DataID}', 'ChangeStatus');
        Route::delete('trademark/delete/{DataID}', 'RemoveDataByID');

        Route::post('trademark/bulk/import', 'ImportBulkData');
        Route::delete('trademark/bulk/remove', 'RemoveIdList');
        Route::put('trademark/bulk/status', 'RemoveListStatus');
    });

    //DRUGS
    Route::controller(DrugController::class)->group(function () {
        Route::get('drug/get/{DataID}', 'GetByID');
        Route::get('drug', 'getSearchKeyword');
        Route::get('drug/find', 'getDrugSearchByKeyword');
        Route::post('drug/all', 'getData');
        Route::post('drug/add', 'AddData');
        Route::put('drug/update/{DataID}', 'AddData');
        Route::put('drug/status/{DataID}', 'ChangeStatus');
        Route::delete('drug/delete/{DataID}', 'RemoveDataByID');
        Route::post('drug/add/mf/{DataID}', 'AddMFData');
        Route::put('drug/mf/update/{DataID}', 'UpdateMFData');
        Route::delete('drug/mf/delete/{DrugID}/{mfID}', 'RemoveMFDataByID');

        Route::post('drug/bulk/import', 'ImportBulkData');
        Route::delete('drug/bulk/remove', 'RemoveIdList');
        Route::put('drug/bulk/status', 'RemoveListStatus');
    });

    //2025 added

    //Contravenes Inn
    Route::controller(ContrahenController::class)->group(function () {
        Route::get('cont/get/{DataID}', 'GetByID');
        Route::get('cont', 'getSearchKeyword');
        Route::post('cont/all', 'getData');
        Route::post('cont/add', 'AddData');
        Route::put('cont/update/{DataID}', 'AddData');
        Route::put('cont/status/{DataID}', 'ChangeStatus');
        Route::delete('cont/delete/{DataID}', 'RemoveDataByID');

        Route::post('cont/bulk/import', 'ImportBulkData');
        Route::delete('cont/bulk/remove', 'RemoveIdList');
        Route::put('cont/bulk/status', 'RemoveListStatus');
    });
    
    //DRUG Reports
    Route::controller(DRController::class)->group(function () {
        Route::get('drc/get/{DataID}', 'GetByID');
        Route::post('drc/all', 'getData');
        Route::post('drc/add', 'AddData');
        Route::post('drc/converter', 'RateConverter');
        Route::put('drc/update/{DataID}', 'AddData');
        Route::put('drc/status/{DataID}', 'ChangeStatus');
        Route::delete('drc/delete/{DataID}', 'RemoveDataByID');
        Route::post('drc/add/mf/{DataID}', 'AddMFData');
        Route::delete('drc/mf/delete/{DrugID}/{mfID}', 'RemoveMFDataByID');

        Route::post('drc/bulk/import', 'ImportBulkData');
        Route::delete('drc/bulk/remove', 'RemoveIdList');
        Route::put('drc/bulk/status', 'RemoveListStatus');
    });


    //DRUG Filters
    Route::controller(FilterController::class)->group(function () {
        Route::post('filter/period-data', 'getPeriodCommonPrice');

        Route::post('filter/dist', 'getFilterByDistributors');
        Route::post('filter/get/dist', 'getFilterByDistributorsById');
        Route::post('filter/companies', 'getFilterByCompanies');
        Route::post('filter/get/companies', 'getFilterByCompaniesById');
        Route::post('filter/manufacturers', 'getFilterByManufacturers');
        Route::post('filter/get/manufacturers', 'getFilterByManufacturersById');
        
        Route::post('filter/inns', 'getFilterByInns');
        Route::post('filter/get/inns', 'getFilterByInnsById');
        
        Route::post('filter/df', 'getFilterByDrugForms');
        Route::post('filter/get/df', 'getFilterByDrugFormsById');

        Route::post('filter/dfg', 'getFilterByDrugDFG');
        Route::post('filter/get/dfg', 'getFilterByDrugFormsGroupById');
        
        Route::post('filter/dtg', 'getFilterByDrugDTG');
        Route::post('filter/get/dtg', 'getFilterByDrugTsGroupById');
        
        Route::post('filter/trademarks', 'getFilterByDrugTrademarks');
        Route::post('filter/get/trademarks', 'getFilterByDrugTrademarkById');

        Route::post('filter/drugs', 'getFilterByDrugs');
        Route::post('filter/get/drugs', 'getFilterByDrugsById');
    });

    //DRUG Filters
    Route::controller(StatController::class)->group(function () {
        Route::post('stat/period-data', 'getStatPeriod');
        Route::post('stat/period-data-list', 'getStatPeriodList');
        Route::post('stat/data-counts', 'getDataCounts');
    });

    
    Route::get('/clear-redis-data', function (Request $request) {
        $clearData = Redis::flushDB();
        $message = "Database redis cleared";
        return _sendResponse(201, $message);
    });

    
    Route::get('/clear-optimize', function (Request $request) {
        \Artisan::call('cache:clear');
        \Artisan::call('route:clear');
        \Artisan::call('optimize:clear');
        \Artisan::call('config:clear');
        $message = "Cache cleared";
        return _sendResponse(201, $message);
    });
    
    //Search data
    Route::controller(SearchController::class)->group(function () {
        Route::post('/search/advanced', 'getData');
        Route::post('/search/advanced/group', 'getFilterByGroup');
        Route::post('/search/advanced/filter', 'getDataByFilterField');
        Route::post('/search/advanced/filter/get', 'getFilterDataInfo');
        Route::post('/search/advanced/get/full', 'getFilterByDrugsById');
        Route::post('/search/advanced/common', 'getPeriodCommonPrice');
    });

    //Search graph
    Route::controller(GraphController::class)->group(function () {
        Route::post('/graph/filter', 'getDataGraphByFilter');
    });

    //System Settings
    Route::controller(SettingsController::class)->group(function () {
        Route::get('/system/settings-info', 'getSettings');
        Route::post('/system/settings-info', 'updateSettings');
        Route::post('/contact/support', 'contactSupport');
        
        Route::get('/log/update/info', 'getLogs');
        Route::post('/log/update/edit', 'addUpdateLog');
    });

    //Analytics (charts, geo report) and Pivot — monthly cubes (database/perf/p3_analytics_cubes.sql)
    Route::controller(AnalyticsController::class)->prefix('analytics')->group(function () {
        Route::get('meta', 'meta');
        Route::post('summary', 'summary');
        Route::post('top', 'top');
        Route::post('geo', 'geo');
        Route::post('pivot', 'pivot');
        Route::post('pivot/plan', 'plan');
    });


    // API route for logout user
    Route::post('/logout', [AuthController::class, 'logout']);
});



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
