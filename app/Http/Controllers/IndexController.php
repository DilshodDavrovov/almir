<?php

namespace App\Http\Controllers;

use App\Models\Distributor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dist = Distributor::limit(25)->get();
        $result = [];

        foreach ($dist as $key) {
            

            $totalData = DB::select('
            SELECT
  mode_40_distributor,
  SUM(sum_price_usd) AS USD,
  SUM(sum_price_uzs) AS UZS,
  SUM(sum_price_eur) AS EUR,
  SUM(sum_price_rub) AS RUB
FROM drug_report
WHERE mode_40_distributor ='. $key->id .' AND mode_40_date BETWEEN "01.01.2018" AND  "31.12.2020"
GROUP BY mode_40_distributor
ORDER BY USD DESC
LIMIT 1');

$totalDataD = DB::select('
            SELECT
  drug_name,
  SUM(sum_price_usd) AS USD,
  SUM(sum_price_uzs) AS UZS,
  SUM(sum_price_eur) AS EUR,
  SUM(sum_price_rub) AS RUB
FROM drug_report
WHERE mode_40_distributor =' . $key->id . ' AND mode_40_date BETWEEN "01.01.2018" AND  "31.12.2020"
GROUP BY drug_name
ORDER BY USD DESC
LIMIT 5');

$totalDataT = DB::select('
            SELECT
  sender_company,
  SUM(sum_price_usd) AS USD,
  SUM(sum_price_uzs) AS UZS,
  SUM(sum_price_eur) AS EUR,
  SUM(sum_price_rub) AS RUB
FROM drug_report
WHERE mode_40_distributor =' . $key->id . ' AND mode_40_date BETWEEN "01.01.2018" AND  "31.12.2020"
GROUP BY sender_company
ORDER BY USD DESC
LIMIT 5');


$totalDataM = DB::select('
            SELECT
  trademark,
  SUM(sum_price_usd) AS USD,
  SUM(sum_price_uzs) AS UZS,
  SUM(sum_price_eur) AS EUR,
  SUM(sum_price_rub) AS RUB
FROM drug_report
WHERE mode_40_distributor =' . $key->id . ' AND mode_40_date BETWEEN "01.01.2018" AND  "31.12.2020"
GROUP BY trademark
ORDER BY USD DESC
LIMIT 5');



            $dist_list = [
                "id" => $key->id,
                "name" => $key->name,
                "data" => $totalData,
                "drug_n" => $totalDataD,
                "drug_t" => $totalDataT,
                "drug_m" => $totalDataM
            ];
            array_push($result, $dist_list);

            //$result->push(\json_decode($dist_list));
        }
        //dd($result);

        return response()->json($result, 419);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
