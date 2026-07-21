<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\GetDataCollection;
use App\Models\Drugs\DrugReport;
use App\Models\Drugs\DrugManufacturer;
use Auth;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DRCImport;
use App\Imports\DRCPImport;
use App\Models\Drugs\Contrahens;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Validator;
use Illuminate\Support\Str;
use App\Models\System\ExchangeRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Distributor;

class DRController extends Controller
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

    public function getData(Request $request)
    {
        $inputs = $request->all();
        $SortBy = $inputs["sortBy"] ?? "created_at";
        $SortByDesc = $request->has('sortByDesc') && $inputs["sortByDesc"] == true ? "DESC" : "ASC";

        // Asosiy so'rov
        $query = DrugReport::select('drug_reports.*')
        ->with([
            'user:id,first_name,last_name',
            'drug_name:id,dt_id,name',
            'sender_company:id,name',
            'distributor:id,name',
            'counterparty:id,name',
            '_manufacturer:id,name'
        ])
        ->where(function ($q) use ($inputs, $request) {
            $q->where([
                ['drug_reports.data_type', $inputs['data_type'] ?? 1],
                ['drug_reports.is_active', $inputs["is_active"] == true ? 1 : 0],
                ['drug_reports.is_deleted', $inputs["is_deleted"] == true ? 1 : 0],
            ]);

            if ($request->has('sortNullDate') && $inputs['sortNullDate']) {
                $q->where('drug_reports.mode_40_date', '=', null);
            }

            if ($request->has('periodCode') && $inputs['periodCode']) {
                $q->where('drug_reports.period_code', $inputs['periodCode']);
            }

            if ($request->has('dataID') && !empty($inputs['dataID'])) {
                $q->whereIn('drug_reports.drug_id', $inputs['dataID']);
            }

            if ($request->has('fromDate') && !empty($inputs['fromDate'])) {
                //$fromDate = Carbon::parse($inputs['fromDate']); //->format('Y-m-d');
                //$toDate = Carbon::parse($inputs['toDate']); //->format('Y-m-d');

                $fromDate = Carbon::parse($inputs['fromDate'])->toDateString(); // "2025-11-20"
                $toDate   = Carbon::parse($inputs['toDate'])->toDateString();   // "2025-11-20"

                //if from date and to date same
                if ($fromDate === $toDate) {
                    $q->whereDate('drug_reports.created_at', '=', $toDate);
                } else {
                    $q->whereDate('drug_reports.created_at', '>=', $fromDate)
                        ->whereDate('drug_reports.created_at', '<=', $toDate);
                }
            }
            if ($request->has('dtID') && !empty($inputs['dtID'])) {
                $q->whereHas('drug_name', function ($query) use ($inputs) {
                    $query->whereIn('dt_id', $inputs['dtID']);
                });
            }

            if ($request->has('drugCode') && !empty($inputs['drugCode'])) {
                $q->whereHas('drugManufacturer', function ($q) use ($inputs) {
                    $q->whereIn('counter', $inputs['drugCode']);
                })->with(['drugManufacturer' => function ($q) use ($inputs) {
                    $q->whereIn('counter', $inputs['drugCode']);
                }]);
            }

            if ($request->has('drugCodex') && !empty($inputs['drugCodex'])) {
                $q->whereHas('drug_code', function ($query) use ($inputs) {
                    $query->whereIn('counter', $inputs['drugCode']);
                });
            }
        });

        // Ma'lumotlarni paginate qilish
        $perPage = $inputs['limit'] ?? 50; // Default limit: 50
        $data = $query->orderBy($SortBy, $SortByDesc)
            ->paginate($perPage);

        // Natijalarni qaytarish

        if (empty($data)) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            $error = "Data not found";
            return _sendError(404, $message, $error);
        }
        return response()->json(new GetDataCollection($data), 201);
    }


    public function getDrugReports(Request $request)
    {
        //   if (auth()->user()->can('edit-passwords')) {
        //     $user->password = $request->password;
        // }
        $inputs = $request->all();
        $isActive = $inputs["is_active"] == true ? 1 : 0;
        $IsDeleted = $inputs["is_deleted"] == true ? 1 : 0;
        $dataType = $inputs["data_type"] ?? 1;
        $SortBy = $inputs["sortBy"] ?? "created_at";
        $SortByDesc = $request->has('sortByDesc') && $inputs["sortByDesc"] == true ? "DESC" : "ASC";
        //->orderBy($SortBy, $SortByDesc)

        $searcher = [
            ['drug_reports.data_type', $dataType],
            ['drug_reports.is_active', $isActive],
            ['drug_reports.is_deleted', $IsDeleted],
        ];

        if ($request->has('sortNullDate') && $inputs['sortNullDate']) {
            $searcher[] = ['drug_reports.mode_40_date', '=', null];
        }

        if ($request->has('periodCode') && $inputs['periodCode']) {
            //$searcher[] = ['drug_reports.period_code', 'LIKE', '%'. $inputs['periodCode']. '%'];
            $searcher[] = ['drug_reports.period_code', '=', $inputs['periodCode']];
        }
        
        $data = DrugReport::select('drug_reports.*')
            ->with('user:id,first_name,last_name')
            ->with('drug_name:id,dt_id,name')
            ->with('sender_company:id,name')
            ->with('distributor:id,name')
            ->with('_manufacturer:id,name')
            ->with('region:id,name')
            ->with('district:id,name')
            ->where($searcher);

        if ($request->has('dataID') && !empty($inputs['dataID'])) {
            $data = $data->whereIn('drug_reports.drug_id', $inputs['dataID']);
        }

        if ($request->has('fromDate') && !empty($inputs['fromDate'])) {
            $fromDate = Carbon::parse($inputs['fromDate']);
            $toDate = Carbon::parse($inputs['toDate']);

            //if from date and to date same
            if ($inputs['fromDate'] == $inputs['toDate']) {
                $data = $data->whereDate('drug_reports.mode_40_date', $fromDate);
                //return;
            } else {
                $data = $data->whereBetween('drug_reports.mode_40_date', [$fromDate, $toDate]);
            }
        }

        // if ($request->has('fromDate') && !empty($inputs['fromDate'])) {
        //     //parse from string text date
        //     $fromDate = Carbon::parse($inputs['fromDate']);
        //     $toDate = Carbon::parse($inputs['toDate']);
        //     $data = $data->whereBetween('drug_reports.mode_40_date', [$fromDate, $toDate]);
        // }

        //Added search by Types
        if ($request->has('dtID') && !empty($inputs['dtID'])) {
            $data->whereHas('drug_name', function ($q) use ($inputs){
                $q->whereIn('dt_id', $inputs['dtID']);
            });
        }

        //Added search by Drug Counter
        if ($request->has('drugCode') && !empty($inputs['drugCode'])) {
            $data->whereHas('drug_code', function ($q) use ($inputs) {
                $q->whereIn('counter', $inputs['drugCode']);
            });
        }
        $data = $data->orderBy($SortBy, $SortByDesc)->paginate($inputs['limit']);

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

    /**
     * BULK IMPORT
     */
    public function ImportBulkData(Request $request)
    {
        try {
            if ($request->has('data_type') && $request->get('data_type') == 2) {
                return self::ImportBulkSellsData($request);
                //$importer = new DRCPImport;
                //Excel::import($importer, $request->file);
            }
            else {
                $importer = new DRCImport;
                Excel::import($importer, $request->file);
            }

            if ($importer->errList) {
                $message = "Возникли проблемы с загрузкой данных. Некоторые данные, показанные ниже, не были загружены в систему.";
                return _sendError(402, $message, $importer->errList);
            }
            $message = "Данные успешно импортированы без каких-либо проблем.";
            return _sendResponse(201, $message);
        } catch (\Exception $error) {
            $message = "Возникли проблемы с загрузкой данных. Некоторые данные, показанные ниже, не были загружены в систему. \n".$error->getMessage();
            return _sendError(500, $message, $error->getTrace());
        }
    }

    public function ImportBulkSellsData(Request $request)
    {
        // Clear memory and caches
        gc_collect_cycles(); // Force garbage collection
        //unset($GLOBALS); // Clear global variables (use cautiously)

        ini_set('memory_limit', '-1');
        set_time_limit(0);
        ini_set('max_execution_time', 600); // Set to 300 seconds (5 minutes)

        try {
            // Validate file upload
            $request->validate([
                'file' => 'required|file|mimes:csv,txt',
            ]);

            // Get uploaded file
            $file = $request->file('file');
            $filePath = $file->getRealPath();

            if (!file_exists($filePath)) {
                return _sendError(400, 'File not found', ['message' => "File path: $filePath does not exist"]);
            }

            // Helper function: Normalize scientific notation numbers to string
            $normalizeNumber = function ($value) {
                $value = trim((string)$value);
                if ($value === '') return null;
                $value = str_replace(',', '.', $value); // 3,05E+08 -> 3.05E+08
                if (stripos($value, 'e') !== false) {
                    return (string)(int)((float)$value);
                }
                return preg_replace('/\D/', '', $value); // Keep only digits
            };

            // Preload data into RAM
            $drugManufacturers = DrugManufacturer::select('drug_mxik', 'drug_id', 'manufacturer_id')
                ->get()
                ->mapWithKeys(fn($item) => [strtolower(trim($item->drug_mxik)) => [
                    'drug_id' => $item->drug_id,
                    'manufacturer_id' => $item->manufacturer_id
                ]])
                ->toArray();

            $distributors = Distributor::pluck('id', 'distributor_inn')->toArray();

            $counterparties = Contrahens::select('inn', 'id', 'region_id', 'district_id')
                ->get()
                ->mapWithKeys(fn($item) => [trim((string)$item->inn) => [
                    'id' => $item->id,
                    'region_id' => $item->region_id,
                    'district_id' => $item->district_id
                ]])
                ->toArray();

            $exchangeRates = ExchangeRate::select('usd_price_rate', 'eur_price_rate', 'rub_price_rate', 'rate_date')
                ->get()
                ->mapWithKeys(fn($item) => [$item->rate_date => [
                    'usd_price_rate' => $item->usd_price_rate,
                    'eur_price_rate' => $item->eur_price_rate,
                    'rub_price_rate' => $item->rub_price_rate,
                    'uzs_price_rate' => 1,
                ]])
                ->toArray();

            $imported = 0;
            $skipped = 0;
            $total = 0;
            $errors = [];
            $batchData = [];
            $batchSize = 10000; // Batch size for insert

            if (($handle = fopen($filePath, 'r')) !== false) {
                $header = fgetcsv($handle, 0, ','); // Skip header row

                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $total++;
                    $rowNumber = $total + 1; // Account for header row

                    // Extract and normalize data
                    $period_code = trim($row[0] ?? '');
                    $drug_mxik_raw = $row[1] ?? '';
                    $drug_mxik = strtolower($normalizeNumber($drug_mxik_raw));

                    // Parse date (dd.mm.YYYY)
                    $mode_40_date = null;
                    if (!empty($row[2])) {
                        try {
                            $mode_40_date = Carbon::createFromFormat('d.m.Y', trim($row[2]));
                        } catch (\Exception $e) {
                            $errors[] = [
                                "message" => "Неверный формат даты в строке:  {$rowNumber}: {$row[2]}"
                            ];
                            // $errors[] = [
                            //     'row' => $rowNumber,
                            //     'column' => 3,
                            //     'message' => "Invalid date format: {$row[2]}"
                            // ];
                            $skipped++;
                            continue;
                        }
                    }

                    $distributor_inn = $normalizeNumber($row[3] ?? '');
                    $cont_inn = $normalizeNumber($row[4] ?? '');
                    $quantity = (float)($row[5] ?? 0);
                    $price_ccy_rate = (float)($row[6] ?? 0);
                    $price_ccy = strtoupper(trim($row[7] ?? ''));

                    // Validation
                    if (!$period_code) {
                        $errors["message"] = "Код периода отсутствует в строке:  {$rowNumber}";
                        // $errors[] = [
                        //     'row' => $rowNumber,
                        //     'column' => 1,
                        //     'message' => "Period code is missing"
                        // ];
                        $skipped++;
                        continue;
                    }

                    // 🔍 period_code mavjudligini tekshirish (RAM yemasin, OOM bo‘lmasin)
                    $exists = DB::table('drug_reports')
                        ->where('period_code', $period_code)
                        ->exists();

                    if ($exists) {
                        $errors["message"] = "Код периода уже существует в строке:  {$rowNumber}: {$period_code}";
                        $skipped++;
                        continue;
                    }

                    if (!$drug_mxik) {
                        $errors["message"] = "MXIK отсутствует или недействителен в строке:  {$rowNumber}: {$drug_mxik_raw}";
                        // $errors[] = [
                        //     'row' => $rowNumber,
                        //     'column' => 2,
                        //     'message' => "MXIK is missing or invalid: {$drug_mxik_raw}"
                        // ];
                        $skipped++;
                        continue;
                    }
                    if (!$mode_40_date) {
                        $errors[] = [
                            "message" => "Дата отсутствует в строке:  {$rowNumber}"
                        ];

                        // $errors[] = [
                        //     'row' => $rowNumber,
                        //     'column' => 3,
                        //     'message' => "Date is missing"
                        // ];
                        $skipped++;
                        continue;
                    }
                    if (!isset($drugManufacturers[$drug_mxik])) {
                        $errors[] = [
                            "message" => "MXIK не найден в строке:  {$rowNumber}: {$drug_mxik}"
                        ];
                        
                        // $errors[] = [
                        //     'row' => $rowNumber,
                        //     'column' => 2,
                        //     'message' => "MXIK not found: {$drug_mxik}"
                        // ];
                        $skipped++;
                        continue;
                    }
                    if (!isset($distributors[$distributor_inn])) {
                        $errors[] = [
                            "message" => "Дистрибьютор не найден в строке:  {$rowNumber}: {$distributor_inn}"
                        ];
                        // $errors[] = [
                        //     'row' => $rowNumber,
                        //     'column' => 4,
                        //     'message' => "Distributor not found: {$distributor_inn}"
                        // ];
                        $skipped++;
                        continue;
                    }
                    if (!isset($counterparties[$cont_inn])) {
                        $errors[] = [
                            "message" => "Контрагент не найден в строке:  {$rowNumber}: {$cont_inn}"
                        ];
                        
                        // $errors[] = [
                        //     'row' => $rowNumber,
                        //     'column' => 5,
                        //     'message' => "Contrahens not found: {$cont_inn}"
                        // ];
                        $skipped++;
                        continue;
                    }

                    $rate_values = $exchangeRates[$mode_40_date->format('Y-m-d')] ?? null;
                    if (!$rate_values) {
                        $errors[] = [
                            "message" => "Обменный курс не найден для даты в строке:  {$rowNumber}: {$mode_40_date->format('Y-m-d')}"
                        ];
                        
                        // $errors[] = [
                        //     'row' => $rowNumber,
                        //     'column' => 3,
                        //     'message' => "Exchange rate not found for date: {$mode_40_date->format('Y-m-d')}"
                        // ];
                        $skipped++;
                        continue;
                    }

                    // Currency conversion
                    switch ($price_ccy) {
                        case "UZS":
                            $converted = [
                                'usd' => $rate_values['usd_price_rate'] ? $price_ccy_rate / $rate_values['usd_price_rate'] : 0,
                                'eur' => $rate_values['eur_price_rate'] ? $price_ccy_rate / $rate_values['eur_price_rate'] : 0,
                                'rub' => $rate_values['rub_price_rate'] ? $price_ccy_rate / $rate_values['rub_price_rate'] : 0,
                                'uzs' => $price_ccy_rate,
                            ];
                            break;
                        case "USD":
                            $converted = [
                                'usd' => $price_ccy_rate,
                                'eur' => $price_ccy_rate * $rate_values['eur_price_rate'],
                                'rub' => $price_ccy_rate * $rate_values['rub_price_rate'],
                                'uzs' => $price_ccy_rate * $rate_values['uzs_price_rate'],
                            ];
                            break;
                        case "EUR":
                            $converted = [
                                'usd' => $rate_values['eur_price_rate'] ? $price_ccy_rate / $rate_values['eur_price_rate'] : 0,
                                'eur' => $price_ccy_rate,
                                'rub' => $price_ccy_rate * $rate_values['rub_price_rate'],
                                'uzs' => $price_ccy_rate * $rate_values['uzs_price_rate'],
                            ];
                            break;
                        case "RUB":
                            $converted = [
                                'usd' => $rate_values['rub_price_rate'] ? $price_ccy_rate / $rate_values['rub_price_rate'] : 0,
                                'eur' => $rate_values['rub_price_rate'] ? $price_ccy_rate / $rate_values['rub_price_rate'] : 0,
                                'rub' => $price_ccy_rate,
                                'uzs' => $price_ccy_rate * $rate_values['uzs_price_rate'],
                            ];
                            break;
                        default:
                            $errors[] = [
                                "message" => "Недействительная валюта в строке:  {$rowNumber}: {$price_ccy}"
                            ];
                            
                            // $errors[] = [
                            //     'row' => $rowNumber,
                            //     'column' => 8,
                            //     'message' => "Invalid currency: {$price_ccy}"
                            // ];
                            $skipped++;
                            continue 2; // Skip to next row
                    }

                    $drugInfo = $drugManufacturers[$drug_mxik];
                    $contr = $counterparties[$cont_inn];

                    // Prepare batch data
                    $batchData[] = [
                        'user_id' => auth()->id() ?? 1, // Use authenticated user or default to 1
                        'data_type' => 2,
                        'period_code' => $period_code,
                        'serial_number' => null,
                        'shelf_life' => null,
                        'is_updated' => false,
                        'sc_id' => null,
                        'mode_40_date' => $mode_40_date,
                        'm40d_id' => $distributors[$distributor_inn],
                        'drug_id' => $drugInfo['drug_id'],
                        'mf_id' => $drugInfo['manufacturer_id'],
                        'counterparty_id' => $contr['id'],
                        'region_id' => $contr['region_id'],
                        'district_id' => $contr['district_id'],
                        'quantity' => $quantity,
                        'price_ccy_rate' => $price_ccy_rate,
                        'price_ccy' => $price_ccy,
                        'price_usd' => $converted['usd'],
                        'price_rub' => $converted['rub'],
                        'price_uzs' => $converted['uzs'],
                        'price_eur' => $converted['eur'],
                        'sum_price_usd' => round($converted['usd'] * $quantity, 2),
                        'sum_price_rub' => round($converted['rub'] * $quantity, 2),
                        'sum_price_uzs' => round($converted['uzs'] * $quantity, 2),
                        'sum_price_eur' => round($converted['eur'] * $quantity, 2),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batchData) >= $batchSize) {
                        DB::table('drug_reports')->insert($batchData);
                        $imported += count($batchData);
                        $batchData = [];
                    }
                }
                fclose($handle);

                // Insert remaining batch
                if (!empty($batchData)) {
                    DB::table('drug_reports')->insert($batchData);
                    $imported += count($batchData);
                }
            } else {
                return _sendError(500, 'Failed to open file', ['message' => 'Unable to read the uploaded CSV file']);
            }

            // Prepare response
            $result = [
                'total' => $total,
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];

            if (count($errors) > 0) {
                return _sendError(422, 'Import completed with some errors', $errors);
                //return _sendResponse(200, 'Import completed with some errors', $result);
            }

            return _sendResponse(200, 'Import completed successfully', $result);
        } catch (\Exception $e) {
            Log::error('ImportBulkData Error: ' . $e->getMessage());
            return _sendError(500, $e->getMessage(), [
                [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ]);
            // return _sendError(500, 'An error occurred during import', [
            //     'message' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
        }
    }

    /**
     * Add Data
     */
    public function AddData(Request $request)
    {
        try {
            //Starts manual transaction
            \DB::beginTransaction();

            $inputs = $request->all();
            $dataID = $request->route('DataID');
            $drugId = $inputs['drug_id'];
            $dataType = $inputs['data_type'] ?? 1;

            if (!empty($dataID)) {
                $data = DrugReport::findOrFail($dataID);
                $_dm = DrugManufacturer::where(['drug_id' => $drugId, 'manufacturer_id' => $data->mf_id])->first();
            } else {
                $data = new DrugReport;
                $_dm = DrugManufacturer::findOrFail($drugId);
            }
            
            $rules = [
                'drug_id' => 'required',
                //'serial_number' => 'required|string',
                //'shelf_life' => 'required',
                'mode_40_date' => 'required',
                'm40d_id' => 'required',
            ];

            if ($dataType != 2) {
                $rules['serial_number'] = 'required|string';
                $rules['shelf_life'] = 'required';
                $rules['sc_id'] = 'required';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }

            //start storing
            $data->data_type = $dataType;
            $data->user_id = Auth::user()->id;
            $data->drug_id = $_dm->drug_id;
            $data->mf_id = $_dm->manufacturer_id;
            $data->serial_number = Str::upper($inputs['serial_number']);
            if (isset($inputs['shelf_life']) && !empty($inputs['shelf_life'])) {
                $data->shelf_life = Carbon::parse($inputs['shelf_life']);
            }
            
            $data->m70d_id = $inputs['m70d_id'] ?? null;
            $data->counterparty_id = $inputs['counterparty_id'] ?? null;
            if (isset($inputs['counterparty_id']) && !empty($inputs['counterparty_id'])) {
                $contrName = Contrahens::where('id', $inputs['counterparty_id'])->first();
                $data->region_id = $contrName->region_id ?? null;
                $data->district_id = $contrName->district_id ?? null;
            }

            if (isset($inputs['mode_70_date'])) {
                $data->mode_70_date = Carbon::parse($inputs['mode_70_date']);
            } else {
                $data->mode_70_date = null;
            }
            $data->period_code = $inputs['period_code'];
            $data->mode_40_date = Carbon::parse($inputs['mode_40_date']);
            $data->m40d_id = $inputs['m40d_id'];
            $data->sc_id = $inputs['sc_id'] ?? null;
            $data->c_price_ccy = $inputs['c_price_ccy'];
            $data->c_price_ccy_rate = $inputs['c_price_ccy_rate'];
            $data->c_price_usd = $inputs['c_price_usd'];
            $data->c_price_uzs = $inputs['c_price_uzs'];
            $data->c_price_eur = $inputs['c_price_eur'];
            $data->c_price_rub = $inputs['c_price_rub'];
            $data->price_ccy = $inputs['price_ccy'];
            $data->price_ccy_rate = $inputs['price_ccy_rate'];
            $data->price_usd = $inputs['price_usd'];
            $data->price_uzs = $inputs['price_uzs'];
            $data->price_eur = $inputs['price_eur'];
            $data->price_rub = $inputs['price_rub'];
            $data->quantity = $inputs['quantity'];
            $data->sum_price_usd = $inputs['sum_price_usd'];
            $data->sum_price_uzs = $inputs['sum_price_uzs'];
            $data->sum_price_eur = $inputs['sum_price_eur'];
            $data->sum_price_rub = $inputs['sum_price_rub'];
            $data->is_local = $inputs['is_local'];
            $data->save();

            \DB::commit();

            $message = "Данные успешно сохранены";
            return _sendResponse(201, $message, $data);
        } catch (\Exception $error) {
            \DB::rollback();

            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже! \n". $error->getMessage();
            return _sendError(402, $message, $error->getTrace());
        }
    }

    /***
     * GET BY ID
     * @var ID = INT
     */
    public function GetByID(Request $request)
    {
        try {
            $dataID = $request->route('DataID');
            $inputs = $request->all();
            $dataType = $inputs['data_type'] ?? 1;

            $data = DrugReport::with('user:id,first_name,last_name')
                ->with('distributor70:id,name')
                ->with('distributor40:id,name')
                ->with('drug_name:id,name')
                ->with('sender_company:id,name')
                ->with('_manufacturer:id,name')
                ->with('counterparty:id,name')
                ->with('region:id,name')
                ->with('district:id,name')
                ->where([['id', $dataID], ['data_type', $dataType]])->first();
                //->findOrFail($dataID);
            if (empty($data)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "Data not found";
                return _sendError(404, $message, $error);
            }
            $message = "Data found";
            return _sendResponse(201, $message, $data);
        } catch (\Exception $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже! \n". $error->getMessage();
            return _sendError(402, $message, $error->getTrace());
        }
    }

    /**
     * CHANGE STATUS
     * @var Boolean IsActive
     * @var Boolean Deleted
     */
    public function ChangeStatus(Request $request)
    {
        try {
            $inputs = $request->all();
            $dataID = $request->route('DataID');
            $rules = [
                'is_active' => 'required|boolean',
                'deleted' => 'required|boolean',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }
            $data = DrugReport::scopeDeleteIt($dataID, $inputs["is_active"] == true ? 1 : 0, $inputs["deleted"] == true ? 1 : 0);
            switch ($data) {
                case $data->is_active && !$data->deleted:
                    $message = "Данные: " . $data->name . " успешно активированы.";
                    return _sendResponse(201, $message, $data);
                    break;
                case !$data->is_active && !$data->deleted:
                    $message = "Данные: " . $data->name . " успешно деактивированы.";
                    return _sendResponse(201, $message, $data);
                    break;
                case !$data->is_active && $data->deleted:
                    $message = "Данные: " . $data->name . " успешно перемещены в корзину.";
                    return _sendResponse(201, $message, $data);
                    break;
                default:
                    $message = "Что-то пошло не так при изменении статуса данных: " . $data->name;
                    return _sendResponse(201, $message, $data);
                    break;
            }
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * DELETE DATA BY ID
     * @var ID
     */
    public function RemoveDataByID(Request $request)
    {
        try {
            $dataID = $request->route('DataID');
            
            if (empty($dataID)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "ID not found or error ID displayed.";
                return _sendError(422, $message, $error);
            }

            $data = DrugReport::findOrFail($dataID);
            $data->delete();

            //Also get from drug_reports_p and drug_reports_s tables by DB if exists
            $data_p = DB::table('drug_reports_p')->where('id', $dataID)->first();
            if ($data_p) {
                DB::table('drug_reports_p')->where('id', $dataID)->delete();
            }
            $data_s = DB::table('drug_reports_s')->where('id', $dataID)->first();
            if ($data_s) {
                DB::table('drug_reports_s')->where('id', $dataID)->delete();
            }
            
            $message = "Drug data removed successfully";
            return _sendResponse(201, $message);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже! \n". $error->getMessage();
            return _sendError(402, $message, $error->getTrace());
        }
    }

    /**
     * Trash id list
     */

    public function RemoveListStatus(Request $request)
    {
        try {
            $inputs = $request->all();
            $rules = [
                'dataID' => 'required',
                'is_active' => 'required|boolean',
                'deleted' => 'required|boolean',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message, $validator->errors());
            }
            $isActive = $inputs["is_active"] == true ? 1 : 0;
            $isDeleted = $inputs["deleted"] == true ? 1 : 0;
            $data = DrugReport::scopeActiveDeactivateList($inputs['dataID'], $isActive, $isDeleted);
            if ($data) {
                $message = "Выбранные данные успешно изменены.";
                return _sendResponse(201, $message);
            }
            if (!$data) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                return _sendError(422, $message);
            }
        } catch (\Exception $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже! \n". $error->getMessage();
            return _sendError(402, $message, $error->getTrace());
        }
    }

    /**
     * Remove Multiple IDs
     */
    public function RemoveIdList(Request $request)
    {
        try {
            $dataID = $request->all();
            if (empty($dataID)) {
                $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
                $error = "ID not found or error ID displayed.";
                return _sendError(422, $message, $error);
            }

            $data = DrugReport::whereIn('id', $dataID['dataID']);
            $data->delete();

            //Also get from drug_reports_p and drug_reports_s tables by DB if exists
            $data_p = DB::table('drug_reports_p')->whereIn('id', $dataID['dataID']);
            if ($data_p) {
                $data_p->delete();
            }       
            $data_s = DB::table('drug_reports_s')->whereIn('id', $dataID['dataID']);
            if ($data_s) {
                $data_s->delete();
            }

            $message = "Data removed successfully";
            return _sendResponse(201, $message);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }

    /**
     * Converter
     * @var CCV - DECIMAL => Rate value
     * @var CCY - STRING => Valyuta tipi
     */
    public function RateConverter(Request $request)
    {
        try {
            $inputs = $request->all();
            $date = Carbon::parse($inputs['date'])->format('Y-m-d');
            $cbu_res = Http::timeout(15)->post('https://cbu.uz/uz/arkhiv-kursov-valyut/json/all/' . $date . '/', [
                #'name' => 'Steve',
                #'role' => 'Network Administrator',
            ])->object();

            $uzs_rate = $inputs['ccv'];
            $usd_price_rate =  $cbu_res[array_search("USD", array_column($cbu_res, 'Ccy'))]->Rate;
            $eur_price_rate = $cbu_res[array_search("EUR", array_column($cbu_res, 'Ccy'))]->Rate;
            $rub_price_rate =  $cbu_res[array_search("RUB", array_column($cbu_res, 'Ccy'))]->Rate;
            switch ($inputs['ccy']) {
                case "UZS":
                    $usd_price_rate = round($uzs_rate / $usd_price_rate, 2);
                    $eur_price_rate = round($uzs_rate / $eur_price_rate, 2);
                    $rub_price_rate = round($uzs_rate / $rub_price_rate, 2);
                    break;
                case "USD":
                    $uzs_rate = $usd_price_rate * $inputs['ccv'];
                    $usd_price_rate = round($uzs_rate / $usd_price_rate, 2);
                    $eur_price_rate = round($uzs_rate / $eur_price_rate, 2);
                    $rub_price_rate = round($uzs_rate / $rub_price_rate, 2);
                    break;
                case "EUR":
                    $uzs_rate = $eur_price_rate * $inputs['ccv'];
                    $usd_price_rate = round($uzs_rate / $usd_price_rate, 2);
                    $eur_price_rate = round($uzs_rate / $eur_price_rate, 2);
                    $rub_price_rate = round($uzs_rate / $rub_price_rate, 2);
                    break;
                case "RUB":
                    $uzs_rate = $rub_price_rate * $inputs['ccv'];
                    $usd_price_rate = round($uzs_rate / $usd_price_rate, 2);
                    $eur_price_rate = round($uzs_rate / $eur_price_rate, 2);
                    $rub_price_rate = round($uzs_rate / $rub_price_rate, 2);
                    break;
            }
            $data = [
                'uzs_price_rate' => $uzs_rate,
                'usd_price_rate' => $usd_price_rate,
                'eur_price_rate' => $eur_price_rate,
                'rub_price_rate' => $rub_price_rate,
            ];

            $message = "Data converted successfully";
            return _sendResponse(201, $message, $data);
        } catch (\Throwable $error) {
            $message = "Что-то пошло не так! Пожалуйста, попробуйте позже!";
            return _sendError(402, $message, $error);
        }
    }
}
