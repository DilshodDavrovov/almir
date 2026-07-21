<?php

namespace App\Imports;
use Auth;
use App\Models\Drugs\Company;
//use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class CompanyImport implements ToCollection, WithHeadingRow, WithHeadings, WithChunkReading, SkipsOnError
{
    use Importable, SkipsErrors;

    
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
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
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

    public function headings(): array
    {
        return [
            'name',
        ];
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        set_time_limit(6000);
        #$inputs = $request->all();
        # Excel::import(new CheckListImport($report_id), 'example1.xlsx');

        foreach ($rows as $index => $row) {
            $res = Company::where('name', '=',  $row['naimenovanie'])->first();
            if (empty($res)) {
                Company::create([
                    'name' => $row['naimenovanie'],
                    'user_id' =>  Auth::user()->id,
                    'is_active' => 1, #($row[5] === 'x' || $row[5] === "X") ? false : true,
                    'deleted' => 0, #($row[3] === 'x' || $row[3] === "X") ? true : false,
                    '_id' => "111"
                ]);
                $index++;
            }
        }
    }

    /**
     * @param \Throwable $e
     */
    public function onError(\Throwable $e)
    {
        // Handle the exception how you'd like.
    }
}
