<?php

namespace App\Console\Commands;

use App\Models\System\ExchangeRate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ImportCBURates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cbu:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import CBU USD, EUR, RUB rates from 2018-01-01 to today';


    public function handle()
    {
        $startDate = Carbon::createFromDate(2018, 1, 1);
        $endDate = Carbon::today();

        $this->info("⏳ Yuklash boshlandi: {$startDate->toDateString()} dan {$endDate->toDateString()} gacha");

        while ($startDate->lte($endDate)) {
            $dateStr = $startDate->format('Y-m-d');
            $url = "https://cbu.uz/uz/arkhiv-kursov-valyut/json/all/{$dateStr}/";

            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $data = collect($response->json());

                    $usd = $data->firstWhere('Ccy', 'USD');
                    $eur = $data->firstWhere('Ccy', 'EUR');
                    $rub = $data->firstWhere('Ccy', 'RUB');

                    if ($usd && $eur && $rub) {
                        ExchangeRate::updateOrCreate(
                            ['rate_date' => $dateStr],
                            [
                                'usd_price_rate' => $usd['Rate'],
                                'eur_price_rate' => $eur['Rate'],
                                'rub_price_rate' => $rub['Rate'],
                                //'rate_date'      => $dateStr,
                            ]
                        );

                        $this->info("✅ {$dateStr} => USD: {$usd['Rate']} | EUR: {$eur['Rate']} | RUB: {$rub['Rate']}");
                    } else {
                        $this->warn("⚠️ {$dateStr} - Kerakli valyutalar topilmadi");
                    }
                } else {
                    $this->warn("❌ {$dateStr} - JSON so‘rov muvaffaqiyatsiz");
                }
            } catch (\Exception $e) {
                $this->error("🚨 Xatolik ({$dateStr}): " . $e->getMessage());
            }

            $startDate->addDay();
        }

        $this->info("🎉 Yuklash yakunlandi.");
    }
}
