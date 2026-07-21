<?php

namespace App\Imports;

use App\Models\Drugs\Contrahens;
use Auth;
//use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Str;

class ContrahenImport implements ToCollection, WithChunkReading, SkipsOnError
{
  use Importable, SkipsErrors;

  public $errList = [];
  public $ddd = [];
  public $res;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    //$this->middleware('auth:sanctum');
  }

  public function batchSize(): int
  {
    return 200;
  }

  public function chunkSize(): int
  {
    return 200;
  }

  public function rules(): array
  {
    return [
      'name' => [
        'required',
        'string',
      ],
    ];
  }

  public function collection(Collection $rows)
  {
    set_time_limit(6000);

    $dataInn = Contrahens::whereIn('inn', $rows->pluck(1)->unique()->filter()->values())->pluck('inn')->toArray();

    $inserted = [];
    $errData = [];

    if ($rows->count() < 2) {
      $this->errList[] = ['message' => 'Недостаточно данных для загрузки.'];
      return;
    }

    foreach ($rows->skip(1) as $k => $data) { // Skip header row
      if (empty($data[1])) {
        continue; // Skip empty drug counter rows
      }

      $insert = [
        'user_id' => Auth::user()->id,
        'name' => Str::upper($data[0]),
        'inn' => $data[1],
        'region_id' => $data[2] ?? null,
        'district_id' => $data[3] ?? null,
        'is_active' => 1,
        'deleted' => 0,
      ];

      // Check for existing period code
      if (in_array($data[1], $dataInn)) {
        $this->errList[] = ['message' => "Данные {$data[0]} уже введены в систему. Мы не загружали эту информацию. Строка данных: " . ($k + 2)];
        continue;
      }

      // Validate required fields
      if ($insert['inn']) {
        $inserted[] = $insert;
        $this->ddd[] = $insert;
      } else {
        $this->errList[] = ['message' => 'Ошибка в данных в строке ' . ($k + 2) . '. Пожалуйста, проверьте эти строки.'];
      }
    }

    // Bulk insert
    if (!empty($inserted)) {
      try {
        Contrahens::insert($inserted);
      } catch (\Exception $ex) {
        $this->errList[] = ['message' => 'Ошибка при массовой вставке данных: ' . $ex->getMessage()];
      }
    }
  }

  /**
   * @param \Throwable $e
   */
  public function onError(\Throwable $e)
  {
    $this->errList[] = ['message' => 'Ошибка: ' . $e->getMessage(), 'trace' => $e->getTrace()];
    // Handle the exception how you'd like.
  }
}
