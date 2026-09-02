<?php

namespace App\Http\Controllers\API\v1\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\CubeQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;

/**
 * "Аналитика продаж" (KPI, диаграммы, геоотчёт) и "Сводный отчёт (pivot)".
 * Data source: monthly cubes dr_cube_l1 / dr_cube_l2 (database/perf/p3_analytics_cubes.sql),
 * with a fact-table fallback for combinations that are not in the cubes.
 */
class AnalyticsController extends Controller
{
    private const CACHE_TTL = 1800; // cubes refresh every 30 minutes

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /** Dimension / measure metadata for the UI. */
    public function meta(Request $request)
    {
        $meta = CubeQuery::meta();
        $meta['restricted'] = $this->dtRestriction($request) !== null;
        // Combined range across both data types (not filtered) so the period pickers stay usable
        // regardless of which "Тип данных" radio is selected — switching it never needs a refetch.
        $range = DB::selectOne('SELECT MIN(ym) AS min_ym, MAX(ym) AS max_ym FROM dr_cube_l2');
        $byType = DB::select('SELECT data_type, MIN(ym) AS min_ym, MAX(ym) AS max_ym FROM dr_cube_l2 GROUP BY data_type');
        $meta['range'] = ['from' => self::ymToStr($range->min_ym ?? null), 'to' => self::ymToStr($range->max_ym ?? null)];
        $meta['range_by_type'] = collect($byType)->mapWithKeys(fn ($r) => [(int) $r->data_type => ['from' => self::ymToStr($r->min_ym), 'to' => self::ymToStr($r->max_ym)]]);
        $state = DB::selectOne('SELECT last_at FROM dr_cube_state WHERE id = 1');
        $meta['refreshed_at'] = $state->last_at ?? null;
        return _sendResponse(200, 'Success', $meta);
    }

    /**
     * KPI + monthly time series for one or more periods.
     * body: dataType, periods: [{from:'YYYY-MM', to:'YYYY-MM'}], dtID[], filters{}
     */
    public function summary(Request $request)
    {
        try {
            $base = $this->baseParams($request);
            $out = [];
            foreach ($this->periods($request) as $i => $period) {
                $p = array_merge($base, $period, ['dims' => ['month'], 'measures' => ['qty', 'usd', 'uzs', 'eur', 'rub'], 'orderBy' => 'month', 'orderDir' => 'asc', 'limit' => 200]);
                $res = $this->cached('sum', $p, fn () => CubeQuery::run($p));
                $total = ['qty' => 0, 'usd' => 0, 'uzs' => 0, 'eur' => 0, 'rub' => 0];
                foreach ($res['rows'] as $r) {
                    foreach ($total as $k => $v) $total[$k] += (float) $r[$k];
                }
                $out[] = [
                    'index' => $i + 1,
                    'from' => self::ymToStr($period['fromYm']),
                    'to' => self::ymToStr($period['toYm']),
                    'total' => $total,
                    'months' => array_map(fn ($r) => ['ym' => (int) $r['month_id'], 'label' => $r['month_name'], 'qty' => (float) $r['qty'], 'usd' => (float) $r['usd'], 'uzs' => (float) $r['uzs'], 'eur' => (float) $r['eur'], 'rub' => (float) $r['rub']], $res['rows']),
                    'cube' => $res['cube'],
                    'elapsed_ms' => $res['elapsed_ms'],
                ];
            }
            return _sendResponse(200, 'Success', $out);
        } catch (InvalidArgumentException $e) {
            return _sendError(422, $e->getMessage());
        }
    }

    /**
     * Top-N by one dimension for each period (with share of the period total).
     * body: dim, limit, dataType, periods[], dtID[], filters{}
     */
    public function top(Request $request)
    {
        try {
            $dim = (string) $request->input('dim', 'distributor');
            $limit = max(1, min((int) $request->input('limit', 10), 500));
            $base = $this->baseParams($request);
            $out = [];
            foreach ($this->periods($request) as $i => $period) {
                $p = array_merge($base, $period, ['dims' => [$dim], 'measures' => ['qty', 'usd', 'uzs', 'eur', 'rub'], 'orderBy' => (string) $request->input('orderBy', 'usd'), 'orderDir' => 'desc', 'limit' => $limit]);
                $res = $this->cached('top', $p, fn () => CubeQuery::run($p));
                $tp = array_merge($base, $period, ['dims' => [], 'measures' => ['qty', 'usd', 'uzs', 'eur', 'rub'], 'limit' => 1]);
                $tot = $this->cached('tot', $tp, fn () => CubeQuery::run($tp));
                $total = $tot['rows'][0] ?? ['qty' => 0, 'usd' => 0, 'uzs' => 0, 'eur' => 0, 'rub' => 0];
                $rows = [];
                foreach ($res['rows'] as $r) {
                    $rows[] = [
                        'id' => $r[$dim . '_id'],
                        'name' => $r[$dim . '_name'] ?? (string) $r[$dim . '_id'],
                        'qty' => (float) $r['qty'], 'usd' => (float) $r['usd'], 'uzs' => (float) $r['uzs'], 'eur' => (float) $r['eur'], 'rub' => (float) $r['rub'],
                        'share_usd' => (float) $total['usd'] > 0 ? round((float) $r['usd'] / (float) $total['usd'] * 100, 2) : 0,
                        'share_qty' => (float) $total['qty'] > 0 ? round((float) $r['qty'] / (float) $total['qty'] * 100, 2) : 0,
                    ];
                }
                $out[] = [
                    'index' => $i + 1,
                    'from' => self::ymToStr($period['fromYm']),
                    'to' => self::ymToStr($period['toYm']),
                    'dim' => $dim,
                    'total' => array_map('floatval', array_intersect_key($total, array_flip(['qty', 'usd', 'uzs', 'eur', 'rub']))),
                    'rows' => $rows,
                    'cube' => $res['cube'],
                    'elapsed_ms' => $res['elapsed_ms'],
                ];
            }
            return _sendResponse(200, 'Success', $out);
        } catch (InvalidArgumentException $e) {
            return _sendError(422, $e->getMessage());
        }
    }

    /**
     * Geo report: totals by region (with soato_id for the map) or by district of one region.
     * body: level 'region'|'district', regionID (for district level), dataType, periods[], dtID[], filters{}
     */
    public function geo(Request $request)
    {
        try {
            $level = $request->input('level', 'region') === 'district' ? 'district' : 'region';
            $base = $this->baseParams($request);
            if ($level === 'district') {
                $regionId = (int) $request->input('regionID');
                if ($regionId <= 0) return _sendError(422, 'regionID is required for district level');
                $base['filters']['region'] = [$regionId];
            }
            $regions = collect(DB::select('SELECT id, name, name_uz, name_oz, soato_id FROM regions'))->keyBy('id');
            $out = [];
            foreach ($this->periods($request) as $i => $period) {
                $p = array_merge($base, $period, ['dims' => [$level], 'measures' => ['qty', 'usd', 'uzs', 'eur', 'rub'], 'orderBy' => 'usd', 'orderDir' => 'desc', 'limit' => 500]);
                $res = $this->cached('geo', $p, fn () => CubeQuery::run($p));
                $totalUsd = 0; $totalQty = 0;
                foreach ($res['rows'] as $r) { $totalUsd += (float) $r['usd']; $totalQty += (float) $r['qty']; }
                $rows = [];
                foreach ($res['rows'] as $r) {
                    $id = (int) $r[$level . '_id'];
                    $row = [
                        'id' => $id,
                        'name' => $r[$level . '_name'] ?? ($id ? (string) $id : '—'),
                        'qty' => (float) $r['qty'], 'usd' => (float) $r['usd'], 'uzs' => (float) $r['uzs'], 'eur' => (float) $r['eur'], 'rub' => (float) $r['rub'],
                        'share_usd' => $totalUsd > 0 ? round((float) $r['usd'] / $totalUsd * 100, 2) : 0,
                        'share_qty' => $totalQty > 0 ? round((float) $r['qty'] / $totalQty * 100, 2) : 0,
                    ];
                    if ($level === 'region' && isset($regions[$id])) {
                        $row['soato_id'] = (int) $regions[$id]->soato_id;
                        $row['name_uz'] = $regions[$id]->name_uz;
                        $row['name_oz'] = $regions[$id]->name_oz;
                    }
                    $rows[] = $row;
                }
                $out[] = [
                    'index' => $i + 1,
                    'from' => self::ymToStr($period['fromYm']),
                    'to' => self::ymToStr($period['toYm']),
                    'level' => $level,
                    'total' => ['qty' => $totalQty, 'usd' => $totalUsd],
                    'rows' => $rows,
                    'cube' => $res['cube'],
                    'elapsed_ms' => $res['elapsed_ms'],
                ];
            }
            return _sendResponse(200, 'Success', $out);
        } catch (InvalidArgumentException $e) {
            return _sendError(422, $e->getMessage());
        }
    }

    /**
     * Pivot: arbitrary rows x columns x measures.
     * body: rows[], cols[], measures[], dataType, from, to, dtID[], filters{}, limit, allowFact
     */
    public function pivot(Request $request)
    {
        try {
            $base = $this->baseParams($request);
            $rows = array_values(array_filter((array) $request->input('rows', []), 'is_string'));
            $cols = array_values(array_filter((array) $request->input('cols', []), 'is_string'));
            $measures = array_values(array_filter((array) $request->input('measures', ['usd', 'qty']), 'is_string'));
            if (count($rows) + count($cols) === 0) return _sendError(422, 'Select at least one row or column dimension');
            if (count($rows) + count($cols) > 5) return _sendError(422, 'Too many dimensions (max 5)');
            $period = $this->periods($request)[0];
            $p = array_merge($base, $period, [
                'dims' => array_values(array_unique(array_merge($rows, $cols))),
                'measures' => $measures ?: ['usd'],
                'orderBy' => $measures[0] ?? 'usd',
                'orderDir' => 'desc',
                'limit' => max(100, min((int) $request->input('limit', 20000), 50000)),
                'allowFact' => (bool) $request->input('allowFact', false),
            ]);
            $res = $this->cached('pivot', $p, fn () => CubeQuery::run($p));
            return _sendResponse(200, 'Success', [
                'rows_dims' => $rows,
                'cols_dims' => $cols,
                'measures' => $p['measures'],
                'from' => self::ymToStr($period['fromYm']),
                'to' => self::ymToStr($period['toYm']),
                'records' => $res['rows'],
                'cube' => $res['cube'],
                'elapsed_ms' => $res['elapsed_ms'],
                'truncated' => $res['truncated'],
                'limit' => $res['limit'],
            ]);
        } catch (InvalidArgumentException $e) {
            return _sendError(422, $e->getMessage());
        }
    }

    /** Which source would be used for a pivot layout (so the UI can warn before a heavy fact query). */
    public function plan(Request $request)
    {
        $dims = array_values(array_unique(array_merge((array) $request->input('rows', []), (array) $request->input('cols', []))));
        $filters = array_keys(array_filter((array) $request->input('filters', []), fn ($v) => is_array($v) && count($v)));
        $registry = CubeQuery::dimensions();
        $needs = [];
        foreach (array_merge($dims, $filters) as $d) {
            if (!isset($registry[$d])) return _sendError(422, "Unknown dimension: $d");
            $needs = array_merge($needs, $registry[$d]['needs']);
        }
        if ($this->dtRestriction($request) !== null) $needs[] = 'dt_id';
        $needs = array_values(array_unique($needs));
        $cube = CubeQuery::CUBE_FACT;
        foreach ([['l2', ['ym', 'dt_id', 'region_id', 'district_id', 'm40d_id', 'party_id']], ['l1', ['ym', 'dt_id', 'region_id', 'm40d_id', 'mf_id', 'drug_id']]] as [$name, $cols]) {
            if (!array_diff($needs, $cols)) { $cube = $name; break; }
        }
        return _sendResponse(200, 'Success', ['cube' => $cube, 'heavy' => $cube === CubeQuery::CUBE_FACT]);
    }

    // ------------------------------------------------------------------ helpers

    /** Common parameters: dataType, dt restriction, filters, activity flags. */
    private function baseParams(Request $request): array
    {
        $dataType = (int) $request->input('dataType', 2) === 1 ? 1 : 2;
        $dt = $this->dtRestriction($request);
        $filters = [];
        foreach ((array) $request->input('filters', []) as $dim => $vals) {
            if (is_array($vals) && count($vals)) $filters[(string) $dim] = array_values($vals);
        }
        return [
            'dataType' => $dataType,
            'dtIds' => $dt,
            'filters' => $filters,
            'isActive' => 1,
            'isDeleted' => 0,
        ];
    }

    /**
     * Drug-type restriction: admins/employees see everything unless they pick types;
     * other users are limited to their access list (like FilterController).
     */
    private function dtRestriction(Request $request): ?array
    {
        $user = $request->user();
        $picked = array_values(array_filter(array_map('intval', (array) $request->input('dtID', []))));
        if ($user->hasRole('admin') || $user->hasRole('employe')) {
            return $picked ?: null;
        }
        $allowed = [];
        foreach ($user->access as $item) $allowed[] = (int) $item->type_id;
        if ($picked) $allowed = array_values(array_intersect($allowed, $picked));
        return $allowed ?: [209999];
    }

    /** Periods from the request: periods[] of {from,to} (YYYY-MM) or single from/to. Defaults to the last 12 months in the cube. */
    private function periods(Request $request): array
    {
        $list = (array) $request->input('periods', []);
        if (!$list) $list = [['from' => $request->input('from'), 'to' => $request->input('to')]];
        $dataType = (int) $request->input('dataType', 2) === 1 ? 1 : 2;
        $max = DB::selectOne('SELECT MAX(ym) AS m FROM dr_cube_l2 WHERE data_type = ?', [$dataType]);
        $maxYm = (int) ($max->m ?? (int) date('Ym'));
        $y = intdiv($maxYm, 100); $mo = $maxYm % 100;
        $defFrom = ($mo === 12) ? $y * 100 + 1 : ($y - 1) * 100 + $mo + 1; // 12 months window
        $out = [];
        foreach (array_slice($list, 0, 4) as $pp) {
            $from = CubeQuery::ym($pp['from'] ?? null, $defFrom);
            $to = CubeQuery::ym($pp['to'] ?? null, $maxYm);
            if ($from > $to) [$from, $to] = [$to, $from];
            $out[] = ['fromYm' => $from, 'toYm' => $to];
        }
        return $out;
    }

    private function cached(string $kind, array $p, callable $fn): array
    {
        $key = 'an_' . $kind . '_' . md5(json_encode($p));
        try {
            $hit = Redis::get($key);
            if ($hit) return json_decode($hit, true);
        } catch (\Throwable $e) {
            // Redis unavailable: fall through to a live query
        }
        $res = $fn();
        try {
            Redis::set($key, json_encode($res), 'EX', self::CACHE_TTL);
        } catch (\Throwable $e) {
        }
        return $res;
    }

    private static function ymToStr($ym): ?string
    {
        if (!$ym) return null;
        $ym = (int) $ym;
        return sprintf('%04d-%02d', intdiv($ym, 100), $ym % 100);
    }
}
