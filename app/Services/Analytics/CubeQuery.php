<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Builds aggregate queries for the "Analytics" and "Pivot" sections on top of the monthly cubes
 * created by database/perf/p3_analytics_cubes.sql (dr_cube_l1, dr_cube_l2) with a fallback to the
 * fact table (drug_reports) for dimension combinations the cubes do not contain.
 *
 * All values go through query bindings; nothing from the request is concatenated into SQL.
 */
class CubeQuery
{
    public const CUBE_L2 = 'l2';
    public const CUBE_L1 = 'l1';
    public const CUBE_FACT = 'fact';

    /** Base columns each source can provide (before joins). */
    private const CUBE_COLUMNS = [
        self::CUBE_L2 => ['ym', 'dt_id', 'region_id', 'district_id', 'm40d_id', 'party_id'],
        self::CUBE_L1 => ['ym', 'dt_id', 'region_id', 'm40d_id', 'mf_id', 'drug_id'],
        self::CUBE_FACT => ['ym', 'dt_id', 'region_id', 'district_id', 'm40d_id', 'party_id', 'mf_id', 'drug_id'],
    ];

    public const MEASURES = [
        'qty' => 'qty',
        'usd' => 'sum_usd',
        'uzs' => 'sum_uzs',
        'eur' => 'sum_eur',
        'rub' => 'sum_rub',
    ];

    /**
     * Dimension registry.
     *  needs  - base columns required from the source
     *  key    - SQL expression of the key (c = cube alias, d = drugs, m = manufacturers)
     *  label  - SQL expression of the display name
     *  joins  - joins required (resolved by name in joinSql())
     */
    public static function dimensions(): array
    {
        return [
            'year'       => ['needs' => ['ym'], 'key' => 'FLOOR(c.ym / 100)', 'label' => 'FLOOR(c.ym / 100)', 'joins' => [], 'i18n' => 'analytics.dim_year', 'group' => 'period'],
            'quarter'    => ['needs' => ['ym'], 'key' => "CONCAT(FLOOR(c.ym / 100), '-Q', CEIL((c.ym % 100) / 3))", 'label' => "CONCAT(FLOOR(c.ym / 100), '-Q', CEIL((c.ym % 100) / 3))", 'joins' => [], 'i18n' => 'analytics.dim_quarter', 'group' => 'period'],
            'month'      => ['needs' => ['ym'], 'key' => 'c.ym', 'label' => "CONCAT(FLOOR(c.ym / 100), '-', LPAD(c.ym % 100, 2, '0'))", 'joins' => [], 'i18n' => 'analytics.dim_month', 'group' => 'period'],
            'region'     => ['needs' => ['region_id'], 'key' => 'c.region_id', 'label' => 'r.name', 'joins' => ['r'], 'i18n' => 'products.region', 'group' => 'geo'],
            'district'   => ['needs' => ['district_id'], 'key' => 'c.district_id', 'label' => 'ds.name', 'joins' => ['ds'], 'i18n' => 'products.district', 'group' => 'geo'],
            'distributor'=> ['needs' => ['m40d_id'], 'key' => 'c.m40d_id', 'label' => 'dist.name', 'joins' => ['dist'], 'i18n' => 'products.dist', 'group' => 'party'],
            'party'      => ['needs' => ['party_id'], 'key' => 'c.party_id', 'label' => 'party.name', 'joins' => ['party'], 'i18n' => 'analytics.dim_party', 'group' => 'party'],
            'manufacturer' => ['needs' => ['mf_id'], 'key' => 'c.mf_id', 'label' => 'm.name', 'joins' => ['m'], 'i18n' => 'products.mf', 'group' => 'product'],
            'country'    => ['needs' => ['mf_id'], 'key' => 'm.country_id', 'label' => 'co.name', 'joins' => ['m', 'co'], 'i18n' => 'products.mfc', 'group' => 'product'],
            'drug'       => ['needs' => ['drug_id'], 'key' => 'c.drug_id', 'label' => 'd.name', 'joins' => ['d'], 'i18n' => 'products.med', 'group' => 'product'],
            'inn'        => ['needs' => ['drug_id'], 'key' => 'd.di_id', 'label' => 'inn.name', 'joins' => ['d', 'inn'], 'i18n' => 'products.mnn', 'group' => 'product'],
            'form'       => ['needs' => ['drug_id'], 'key' => 'd.df_id', 'label' => 'df.name', 'joins' => ['d', 'df'], 'i18n' => 'products.df', 'group' => 'product'],
            'farm_group' => ['needs' => ['drug_id'], 'key' => 'd.dfg_id', 'label' => 'dfg.name', 'joins' => ['d', 'dfg'], 'i18n' => 'products.dfg', 'group' => 'product'],
            'ts_group'   => ['needs' => ['drug_id'], 'key' => 'd.dtg_id', 'label' => 'dtg.name', 'joins' => ['d', 'dtg'], 'i18n' => 'products.tpg', 'group' => 'product'],
            'trademark'  => ['needs' => ['drug_id'], 'key' => 'd.trademark_id', 'label' => 'tm.name', 'joins' => ['d', 'tm'], 'i18n' => 'products.td', 'group' => 'product'],
            'drug_type'  => ['needs' => ['dt_id'], 'key' => 'DT_KEY', 'label' => 'dt.name', 'joins' => ['dt'], 'i18n' => 'products.dt', 'group' => 'product'],
            'rx_otc'     => ['needs' => ['drug_id'], 'key' => "CASE WHEN d.is_otc = 1 THEN 'OTC' WHEN d.is_rx = 1 THEN 'RX' ELSE '-' END", 'label' => "CASE WHEN d.is_otc = 1 THEN 'OTC' WHEN d.is_rx = 1 THEN 'RX' ELSE '-' END", 'joins' => ['d'], 'i18n' => 'table.rx_otc', 'group' => 'product'],
        ];
    }

    /** Public metadata for the UI. */
    public static function meta(): array
    {
        $dims = [];
        foreach (self::dimensions() as $key => $dim) {
            $dims[] = ['key' => $key, 'i18n' => $dim['i18n'], 'group' => $dim['group'], 'cubes' => self::cubesFor($dim['needs'])];
        }
        return [
            'dimensions' => $dims,
            'measures' => array_keys(self::MEASURES),
        ];
    }

    /** Which sources can serve the given set of base columns (cheapest first). */
    private static function cubesFor(array $needs): array
    {
        $out = [];
        foreach ([self::CUBE_L2, self::CUBE_L1, self::CUBE_FACT] as $cube) {
            if (!array_diff($needs, self::CUBE_COLUMNS[$cube])) {
                $out[] = $cube;
            }
        }
        return $out;
    }

    /**
     * Run an aggregate query.
     *
     * @param array $p [
     *   'dataType' => 1|2, 'fromYm' => 202401, 'toYm' => 202412,
     *   'dims' => ['region', 'month'], 'measures' => ['qty','usd'],
     *   'filters' => ['region' => [1,2], 'drug' => [..]],   // dimension key => list of ids
     *   'dtIds' => [..] | null,                               // drug type restriction (user access)
     *   'isActive' => 1, 'isDeleted' => 0,
     *   'orderBy' => 'usd', 'orderDir' => 'desc', 'limit' => 50, 'offset' => 0,
     *   'allowFact' => true,
     * ]
     * @return array ['rows' => [...], 'cube' => 'l2', 'elapsed_ms' => 12, 'truncated' => false, 'limit' => 50]
     */
    public static function run(array $p): array
    {
        $registry = self::dimensions();
        $dims = array_values(array_unique($p['dims'] ?? []));
        $measures = array_values(array_unique($p['measures'] ?? ['qty', 'usd']));
        $filters = array_filter($p['filters'] ?? [], fn ($v) => is_array($v) && count($v) > 0);

        foreach ($dims as $d) {
            if (!isset($registry[$d])) throw new InvalidArgumentException("Unknown dimension: $d");
        }
        foreach (array_keys($filters) as $d) {
            if (!isset($registry[$d])) throw new InvalidArgumentException("Unknown filter dimension: $d");
        }
        foreach ($measures as $m) {
            if (!isset(self::MEASURES[$m])) throw new InvalidArgumentException("Unknown measure: $m");
        }

        // Required base columns = dims + filters (+ dt when restricted)
        $needs = [];
        foreach (array_merge($dims, array_keys($filters)) as $d) {
            $needs = array_merge($needs, $registry[$d]['needs']);
        }
        if (!empty($p['dtIds'])) $needs[] = 'dt_id';
        $needs = array_values(array_unique($needs));

        $candidates = self::cubesFor($needs);
        if (!($p['allowFact'] ?? true)) {
            $candidates = array_values(array_diff($candidates, [self::CUBE_FACT]));
        }
        if (empty($candidates)) {
            throw new InvalidArgumentException('This combination of dimensions is not available in the cubes');
        }
        $cube = $candidates[0];

        [$fromSql, $joins, $where, $bindings] = self::baseSource($cube, $p);

        // dt restriction
        $dtJoinNeeded = false;
        if (!empty($p['dtIds'])) {
            $ids = array_values(array_map('intval', $p['dtIds']));
            if ($cube === self::CUBE_L2) {
                $where[] = 'c.dt_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            } else {
                $dtJoinNeeded = true;
                $where[] = 'd.dt_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')';
            }
            $bindings = array_merge($bindings, $ids);
        }

        $select = [];
        $groupBy = [];
        $joinNames = [];
        if ($dtJoinNeeded) $joinNames[] = 'd';
        foreach ($dims as $d) {
            $def = $registry[$d];
            $keyExpr = $def['key'];
            if ($keyExpr === 'DT_KEY') {
                $keyExpr = $cube === self::CUBE_L2 ? 'c.dt_id' : 'd.dt_id';
                if ($cube !== self::CUBE_L2) $joinNames[] = 'd';
            }
            $select[] = "$keyExpr AS `{$d}_id`";
            $select[] = "MIN({$def['label']}) AS `{$d}_name`";
            $groupBy[] = $keyExpr;
            $joinNames = array_merge($joinNames, $def['joins']);
        }
        foreach ($measures as $m) {
            $col = self::MEASURES[$m];
            $select[] = "SUM(c.$col) AS `$m`";
        }

        // Filters on dimensions (lists of ids or key values). Only the joins needed to
        // evaluate the key expression are added (drugs for drug attributes, manufacturers for country).
        foreach ($filters as $d => $ids) {
            $def = $registry[$d];
            $keyExpr = $def['key'];
            if ($keyExpr === 'DT_KEY') {
                $keyExpr = $cube === self::CUBE_L2 ? 'c.dt_id' : 'd.dt_id';
                if ($cube !== self::CUBE_L2) $joinNames[] = 'd';
            }
            if (str_contains($keyExpr, 'd.')) $joinNames[] = 'd';
            if (str_contains($keyExpr, 'm.')) $joinNames[] = 'm';
            $vals = array_values(array_map(fn ($v) => is_numeric($v) ? (int) $v : (string) $v, $ids));
            $where[] = "$keyExpr IN (" . implode(',', array_fill(0, count($vals), '?')) . ')';
            $bindings = array_merge($bindings, $vals);
        }

        $joinSql = self::joinSql($cube, array_values(array_unique($joinNames)), $p);

        $orderBy = $p['orderBy'] ?? ($measures[0] ?? 'usd');
        $orderDir = strtolower($p['orderDir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        if (in_array($orderBy, $measures, true)) {
            $orderSql = "`$orderBy` $orderDir";
        } elseif (in_array($orderBy, $dims, true)) {
            $orderSql = "`{$orderBy}_id` $orderDir";
        } else {
            $orderSql = '`' . ($measures[0] ?? 'usd') . "` $orderDir";
        }

        $limit = max(1, min((int)($p['limit'] ?? 1000), 50000));
        $offset = max(0, (int)($p['offset'] ?? 0));

        $sql = 'SELECT ' . ($cube === self::CUBE_FACT ? '/*+ MAX_EXECUTION_TIME(240000) */ ' : '')
            . implode(', ', $select)
            . " FROM $fromSql $joinSql"
            . ' WHERE ' . implode(' AND ', $where)
            . ($groupBy ? ' GROUP BY ' . implode(', ', $groupBy) : '')
            . " ORDER BY $orderSql"
            . ' LIMIT ' . ($limit + 1) . " OFFSET $offset";

        $t0 = microtime(true);
        $rows = DB::select($sql, $bindings);
        $elapsed = (int) round((microtime(true) - $t0) * 1000);

        $truncated = count($rows) > $limit;
        if ($truncated) array_pop($rows);

        return [
            'rows' => array_map(fn ($r) => (array) $r, $rows),
            'cube' => $cube,
            'elapsed_ms' => $elapsed,
            'truncated' => $truncated,
            'limit' => $limit,
        ];
    }

    /** FROM clause, base WHERE and bindings for the chosen source. */
    private static function baseSource(string $cube, array $p): array
    {
        $dataType = (int) ($p['dataType'] ?? 1);
        $fromYm = (int) $p['fromYm'];
        $toYm = (int) $p['toYm'];
        $isActive = (int) ($p['isActive'] ?? 1);
        $isDeleted = (int) ($p['isDeleted'] ?? 0);

        if ($cube === self::CUBE_FACT) {
            // Fact fallback: expose the same column names as the cubes through a derived alias.
            $partyCol = $dataType === 2 ? 'counterparty_id' : 'sc_id';
            $from = "(SELECT data_type, YEAR(mode_40_date) * 100 + MONTH(mode_40_date) AS ym, IFNULL(region_id, 0) AS region_id,
                        IFNULL(district_id, 0) AS district_id, IFNULL(m40d_id, 0) AS m40d_id, IFNULL($partyCol, 0) AS party_id,
                        IFNULL(mf_id, 0) AS mf_id, drug_id, quantity AS qty, sum_price_usd AS sum_usd, sum_price_uzs AS sum_uzs,
                        sum_price_eur AS sum_eur, sum_price_rub AS sum_rub
                      FROM drug_reports
                      WHERE data_type = ? AND is_active = ? AND is_deleted = ?
                        AND mode_40_date >= STR_TO_DATE(CONCAT(?, '01'), '%Y%m%d')
                        AND mode_40_date < DATE_ADD(STR_TO_DATE(CONCAT(?, '01'), '%Y%m%d'), INTERVAL 1 MONTH)) c";
            return [$from, [], ['1 = 1'], [$dataType, $isActive, $isDeleted, $fromYm, $toYm]];
        }

        $table = $cube === self::CUBE_L2 ? 'dr_cube_l2' : 'dr_cube_l1';
        $where = ['c.data_type = ?', 'c.ym BETWEEN ? AND ?', 'c.is_active = ?', 'c.is_deleted = ?'];
        return ["$table c", [], $where, [$dataType, $fromYm, $toYm, $isActive, $isDeleted]];
    }

    /** JOIN clauses by alias, in dependency order. */
    private static function joinSql(string $cube, array $names, array $p): string
    {
        $dataType = (int) ($p['dataType'] ?? 1);
        $defs = [
            'd'     => 'LEFT JOIN drugs d ON d.id = c.drug_id',
            'm'     => 'LEFT JOIN manufacturers m ON m.id = c.mf_id',
            'co'    => 'LEFT JOIN countries co ON co.id = m.country_id',
            'r'     => 'LEFT JOIN regions r ON r.id = c.region_id',
            'ds'    => 'LEFT JOIN districts ds ON ds.id = c.district_id',
            'dist'  => 'LEFT JOIN distributors dist ON dist.id = c.m40d_id',
            'party' => $dataType === 2
                ? 'LEFT JOIN contrahens party ON party.id = c.party_id'
                : 'LEFT JOIN companies party ON party.id = c.party_id',
            'inn'   => 'LEFT JOIN drug_inns inn ON inn.id = d.di_id',
            'df'    => 'LEFT JOIN drug_forms df ON df.id = d.df_id',
            'dfg'   => 'LEFT JOIN drug_farm_groups dfg ON dfg.id = d.dfg_id',
            'dtg'   => 'LEFT JOIN drug_ts_groups dtg ON dtg.id = d.dtg_id',
            'tm'    => 'LEFT JOIN trademarks tm ON tm.id = d.trademark_id',
            'dt'    => $cube === self::CUBE_L2
                ? 'LEFT JOIN drug_types dt ON dt.id = c.dt_id'
                : 'LEFT JOIN drug_types dt ON dt.id = d.dt_id',
        ];
        // dependency: anything that uses d/m must come after them
        $order = ['d', 'm', 'co', 'r', 'ds', 'dist', 'party', 'inn', 'df', 'dfg', 'dtg', 'tm', 'dt'];
        $need = array_flip($names);
        foreach ($names as $n) {
            if (in_array($n, ['inn', 'df', 'dfg', 'dtg', 'tm'], true)) $need['d'] = true;
            if ($n === 'co') $need['m'] = true;
            if ($n === 'dt' && $cube !== self::CUBE_L2) $need['d'] = true;
        }
        $sql = [];
        foreach ($order as $n) {
            if (isset($need[$n])) $sql[] = $defs[$n];
        }
        return implode(' ', $sql);
    }

    /** 'YYYY-MM' -> int YYYYMM (validated). */
    public static function ym(?string $s, int $default): int
    {
        if ($s && preg_match('/^(\d{4})-(\d{2})$/', $s, $m)) {
            $y = (int) $m[1];
            $mo = (int) $m[2];
            if ($y >= 2000 && $y <= 2100 && $mo >= 1 && $mo <= 12) return $y * 100 + $mo;
        }
        return $default;
    }
}
