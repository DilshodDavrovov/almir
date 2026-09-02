import React, { useMemo, useState } from "react";
import { buildPivot, fmt } from "../../../utils/analytics";

const MEASURE_LABEL = { usd: "USD", uzs: "UZS", eur: "EUR", rub: "RUB", qty: "Qty", avg: "Avg" };

/**
 * Renders a pivot matrix from flat aggregated records.
 * props: records, rowsDims, colsDims, measures, dimTitle(key), t(key), avgCurrency ('usd'..)
 * Measure 'avg' is computed client-side as sum(currency)/qty.
 */
export default function PivotGrid({ records, rowsDims, colsDims, measures, dimTitle, t, avgCurrency = "usd" }) {
    const [sort, setSort] = useState({ col: null, measure: null, dir: "desc" });
    const baseMeasures = useMemo(() => {
        const set = new Set(measures.filter((m) => m !== "avg"));
        if (measures.includes("avg")) { set.add(avgCurrency); set.add("qty"); }
        return Array.from(set);
    }, [measures, avgCurrency]);

    const pv = useMemo(() => buildPivot(records, rowsDims, colsDims, baseMeasures), [records, rowsDims, colsDims, baseMeasures]);

    const valueOf = (cell, m) => {
        if (!cell) return null;
        if (m === "avg") return cell.qty ? (cell[avgCurrency] || 0) / cell.qty : null;
        return cell[m];
    };

    const rows = useMemo(() => {
        const list = [...pv.rows];
        if (sort.measure) {
            const get = (r) => {
                const cell = sort.col === "__total__" ? pv.rowTotals.get(r.key) : pv.cells.get(`${r.key}${sort.col}`);
                return valueOf(cell, sort.measure) || 0;
            };
            list.sort((a, b) => (sort.dir === "desc" ? get(b) - get(a) : get(a) - get(b)));
        }
        return list;
    }, [pv, sort]);

    const cols = colsDims.length ? pv.cols : [{ key: "", labels: [t("analytics.total")], ids: [] }];
    const showTotalCol = colsDims.length > 0;
    const toggleSort = (col, m) => setSort((s) => (s.col === col && s.measure === m ? { ...s, dir: s.dir === "desc" ? "asc" : "desc" } : { col, measure: m, dir: "desc" }));
    const arrow = (col, m) => (sort.col === col && sort.measure === m ? (sort.dir === "desc" ? " ↓" : " ↑") : "");

    const cellTd = (cell, m, key, extraClass = "") => {
        const v = valueOf(cell, m);
        return <td key={key} className={`num ${v ? "" : "z"} ${extraClass}`}>{v == null ? "·" : fmt(v, m === "qty" ? 0 : 2)}</td>;
    };

    return (
        <div className="pv-grid-wrap">
            <table className="pv-grid">
                <thead>
                    <tr>
                        {rowsDims.map((d) => <th key={d} className="rh" rowSpan={2}>{dimTitle(d)}</th>)}
                        {!rowsDims.length ? <th className="rh" rowSpan={2}></th> : null}
                        {cols.map((c) => <th key={c.key} colSpan={measures.length}>{c.labels.join(" · ")}</th>)}
                        {showTotalCol ? <th colSpan={measures.length} className="total">{t("analytics.rowTotal")}</th> : null}
                    </tr>
                    <tr>
                        {cols.map((c) => measures.map((m) => (
                            <th key={`${c.key}-${m}`} className="m sortable" onClick={() => toggleSort(c.key, m)}>{MEASURE_LABEL[m] || m}{arrow(c.key, m)}</th>
                        )))}
                        {showTotalCol ? measures.map((m) => <th key={`t-${m}`} className="m sortable total" onClick={() => toggleSort("__total__", m)}>{MEASURE_LABEL[m] || m}{arrow("__total__", m)}</th>) : null}
                    </tr>
                </thead>
                <tbody>
                    {rows.map((r) => (
                        <tr key={r.key || "_"}>
                            {rowsDims.map((d, i) => <td key={d} className="rh" title={r.labels[i]}>{r.labels[i]}</td>)}
                            {!rowsDims.length ? <td className="rh">{t("analytics.total")}</td> : null}
                            {cols.map((c) => measures.map((m) => cellTd(pv.cells.get(`${r.key}${c.key}`), m, `${c.key}-${m}`)))}
                            {showTotalCol ? measures.map((m) => cellTd(pv.rowTotals.get(r.key), m, `t-${m}`, "total")) : null}
                        </tr>
                    ))}
                    <tr className="total">
                        <td className="rh" colSpan={Math.max(1, rowsDims.length)}>{t("analytics.grandTotal")}</td>
                        {cols.map((c) => measures.map((m) => cellTd(colsDims.length ? pv.colTotals.get(c.key) : pv.grand, m, `ct-${c.key}-${m}`)))}
                        {showTotalCol ? measures.map((m) => cellTd(pv.grand, m, `g-${m}`, "total")) : null}
                    </tr>
                </tbody>
            </table>
        </div>
    );
}

/** Flatten the pivot into rows for Excel export. */
export function pivotToSheet(records, rowsDims, colsDims, measures, dimTitle, t, avgCurrency = "usd") {
    const set = new Set(measures.filter((m) => m !== "avg"));
    if (measures.includes("avg")) { set.add(avgCurrency); set.add("qty"); }
    const pv = buildPivot(records, rowsDims, colsDims, Array.from(set));
    const cols = colsDims.length ? pv.cols : [{ key: "", labels: [t("analytics.total")] }];
    const valueOf = (cell, m) => (!cell ? null : m === "avg" ? (cell.qty ? (cell[avgCurrency] || 0) / cell.qty : null) : cell[m]);
    const columns = [];
    rowsDims.forEach((d) => columns.push({ header: dimTitle(d), key: `r_${d}`, width: 32 }));
    if (!rowsDims.length) columns.push({ header: "", key: "r_", width: 20 });
    cols.forEach((c, ci) => measures.forEach((m) => columns.push({ header: `${c.labels.join(" · ")} — ${MEASURE_LABEL[m] || m}`, key: `c${ci}_${m}`, width: 18, numFmt: m === "qty" ? "#,##0" : "#,##0.00" })));
    if (colsDims.length) measures.forEach((m) => columns.push({ header: `${t("analytics.rowTotal")} — ${MEASURE_LABEL[m] || m}`, key: `tot_${m}`, width: 18, numFmt: m === "qty" ? "#,##0" : "#,##0.00" }));
    const rows = pv.rows.map((r) => {
        const o = {};
        rowsDims.forEach((d, i) => (o[`r_${d}`] = r.labels[i]));
        if (!rowsDims.length) o.r_ = t("analytics.total");
        cols.forEach((c, ci) => measures.forEach((m) => (o[`c${ci}_${m}`] = valueOf(pv.cells.get(`${r.key}${c.key}`), m))));
        if (colsDims.length) measures.forEach((m) => (o[`tot_${m}`] = valueOf(pv.rowTotals.get(r.key), m)));
        return o;
    });
    const total = {};
    rowsDims.forEach((d, i) => (total[`r_${d}`] = i === 0 ? t("analytics.grandTotal") : ""));
    if (!rowsDims.length) total.r_ = t("analytics.grandTotal");
    cols.forEach((c, ci) => measures.forEach((m) => (total[`c${ci}_${m}`] = valueOf(colsDims.length ? pv.colTotals.get(c.key) : pv.grand, m))));
    if (colsDims.length) measures.forEach((m) => (total[`tot_${m}`] = valueOf(pv.grand, m)));
    rows.push(total);
    return { columns, rows };
}
