<?php
//use App\Models\AppSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

if (!function_exists('price_converter')) {

    /**
     * Converter
     * @var CCV - DECIMAL => Rate value
     * @var CCY - STRING => Valyuta tipi
     */
    function price_converter($date, $ccv, $ccy)
    {
        try {
            Cache::flush();
            if (Cache::has($date . '_usd')) {
                $data = [
                    'uzs_price_rate' => Cache::get($date . '_uzs'),
                    'usd_price_rate' => Cache::get($date . '_usd'),
                    'eur_price_rate' => Cache::get($date . '_eur'),
                    'rub_price_rate' => Cache::get($date . '_rub'),
                ];
                return $data;
            }
            $cbu_res = Http::timeout(15)->post('https://cbu.uz/uz/arkhiv-kursov-valyut/json/all/' . $date . '/', [
                #'name' => 'Steve',
                #'role' => 'Network Administrator',
            ])->object();

            $uzs_rate = $ccv;
            $usd_price_rate =  $cbu_res[array_search("USD", array_column($cbu_res, 'Ccy'))]->Rate;
            $eur_price_rate = $cbu_res[array_search("EUR", array_column($cbu_res, 'Ccy'))]->Rate;
            $rub_price_rate =  $cbu_res[array_search("RUB", array_column($cbu_res, 'Ccy'))]->Rate;
            switch ($ccy) {
                case "UZS":
                    $usd_price_rate = $uzs_rate / $usd_price_rate;
                    $eur_price_rate = $uzs_rate / $eur_price_rate;
                    $rub_price_rate = $uzs_rate / $rub_price_rate;
                    break;
                case "USD":
                    $uzs_rate = $usd_price_rate * $ccv;
                    $usd_price_rate = $uzs_rate / $usd_price_rate;
                    $eur_price_rate = $uzs_rate / $eur_price_rate;
                    $rub_price_rate = $uzs_rate / $rub_price_rate;
                    break;
                case "EUR":
                    $uzs_rate = $eur_price_rate * $ccv;
                    $usd_price_rate = $uzs_rate / $usd_price_rate;
                    $eur_price_rate = $uzs_rate / $eur_price_rate;
                    $rub_price_rate = $uzs_rate / $rub_price_rate;
                    break;
                case "RUB":
                    $uzs_rate = $rub_price_rate * $ccv;
                    $usd_price_rate = $uzs_rate / $usd_price_rate;
                    $eur_price_rate = $uzs_rate / $eur_price_rate;
                    $rub_price_rate = $uzs_rate / $rub_price_rate;
                    break;
            }

            $data = [
                'uzs_price_rate' => $uzs_rate,
                'usd_price_rate' => $usd_price_rate,
                'eur_price_rate' => $eur_price_rate,
                'rub_price_rate' => $rub_price_rate,
            ];
            // if ($ccv == 3.07) {
            //     dd($data);
            // }
            Cache::forever($date . '_uzs', $uzs_rate);
            Cache::forever($date . '_usd', $usd_price_rate);
            Cache::forever($date . '_eur', $eur_price_rate);
            Cache::forever($date . '_rub', $rub_price_rate);
            return $data;
        } catch (\Throwable $error) {
            $data = [
                'uzs_price_rate' => 0.00,
                'usd_price_rate' => 0.00,
                'eur_price_rate' => 0.00,
                'rub_price_rate' => 0.00,
            ];
            return $data;
        }
    }
}
