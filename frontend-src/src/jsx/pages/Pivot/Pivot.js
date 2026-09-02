import React, { useEffect, useMemo, useRef, useState } from "react";
import { connect } from "react-redux";
import { TR } from "../../../utils/helpers";
import { showToast } from "../../../utils";
import API from "../../../services/AnalyticsService";
import AnalyticsFilter, { emptyFilter, filterToRequest, rangeForType } from "../../components/Analytics/AnalyticsFilter";
import DataTypeSwitch from "../../components/DataTypeSwitch";
import Loading from "../../components/Loading";
import PivotGrid, { pivotToSheet } from "./PivotGrid";
import { CURRENCIES, dimTitle, exportToExcel, periodLabel } from "../../../utils/analytics";
import "../../components/Analytics/analytics.css";

const GROUPS = ["period", "geo", "party", "product"];
const ALL_MEASURES = ["usd", "uzs", "eur", "rub", "qty", "avg"];
const MEASURE_LABEL = { usd: "USD", uzs: "UZS", eur: "EUR", rub: "RUB" };

const Pivot = ({ lang }) => {
    const [meta, setMeta] = useState(null);
    const [filter, setFilter] = useState(null);
    const [toggle, setToggle] = useState(false);
    const [rows, setRows] = useState(["region"]);
    const [cols, setCols] = useState(["quarter"]);
    const [measures, setMeasures] = useState(["usd", "qty"]);
    const [avgCurrency, setAvgCurrency] = useState("usd");
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [heavy, setHeavy] = useState(false);
    const [heavyConfirmed, setHeavyConfirmed] = useState(false);
    const [draggingKey, setDraggingKey] = useState(null);
    const [dragOverZone, setDragOverZone] = useState(null); // 'rows' | 'cols' | 'fields' | null
    const dragData = useRef(null); // { key, from: 'rows'|'cols'|'fields' } — the drag payload (dataTransfer.getData is unreliable mid-drag in some browsers)

    const t = (k) => TR(lang, k);
    const title = (k) => dimTitle(TR, lang, meta, k);

    useEffect(() => {
        API.meta(2).then((res) => {
            const m = res.data.data;
            setMeta(m);
            const dr = rangeForType(m, 2);
            const f = emptyFilter(dr, 2);
            f.periods = [{ from: dr && dr.to ? dr.to.slice(0, 4) + "-01" : f.periods[0].from, to: dr && dr.to }];
            setFilter(f);
        }).catch((err) => showToast("error", err?.response?.data?.message || "Analytics is unavailable"));
    }, []);

    const dims = (meta && meta.dimensions) || [];
    const used = (k) => rows.includes(k) || cols.includes(k);
    const addTo = (zone, k) => {
        if (used(k)) return;
        if (zone === "rows") setRows([...rows, k]); else setCols([...cols, k]);
        setHeavyConfirmed(false);
    };
    const remove = (zone, k) => { (zone === "rows" ? setRows : setCols)((zone === "rows" ? rows : cols).filter((x) => x !== k)); setHeavyConfirmed(false); };
    const move = (zone, k, dir) => {
        const list = zone === "rows" ? [...rows] : [...cols];
        const i = list.indexOf(k); const j = i + dir;
        if (j < 0 || j >= list.length) return;
        [list[i], list[j]] = [list[j], list[i]];
        (zone === "rows" ? setRows : setCols)(list);
    };
    const swap = () => { setRows(cols); setCols(rows); };
    const toggleMeasure = (m) => setMeasures(measures.includes(m) ? measures.filter((x) => x !== m) : [...measures, m]);

    /** Quick Приход/Продажа switch from the report header — same reset the old in-panel radio did. */
    const setDataType = (t) => {
        setFilter({ ...filter, dataType: t, region: null, district: null, extra: filter.extra.filter((e) => e.dim !== "party") });
        setResult(null);
        setHeavyConfirmed(false);
    };

    // ---- drag & drop: fields list -> rows/cols, and reordering/moving within/between them ----
    const listOf = (zone) => (zone === "rows" ? rows : cols);
    const setListOf = (zone) => (zone === "rows" ? setRows : setCols);
    const insertAt = (list, key, index) => {
        const rest = list.filter((k) => k !== key);
        const i = Math.max(0, Math.min(index, rest.length));
        return [...rest.slice(0, i), key, ...rest.slice(i)];
    };
    const dropIndexAt = (e, zoneEl) => {
        const chips = [...zoneEl.querySelectorAll(".pv-chip")];
        for (let i = 0; i < chips.length; i++) {
            const r = chips[i].getBoundingClientRect();
            if (e.clientX < r.left + r.width / 2) return i;
        }
        return chips.length;
    };
    const onFieldDragStart = (e, key) => {
        dragData.current = { key, from: "fields" };
        e.dataTransfer.effectAllowed = "copy";
        e.dataTransfer.setData("text/plain", key);
        setDraggingKey(key);
    };
    const onChipDragStart = (e, zone, key) => {
        dragData.current = { key, from: zone };
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", key);
        setDraggingKey(key);
    };
    const onDragEnd = () => { dragData.current = null; setDraggingKey(null); setDragOverZone(null); };
    const onZoneDragOver = (e, zone) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = dragData.current && dragData.current.from === "fields" ? "copy" : "move";
        setDragOverZone(zone);
    };
    const onZoneDragLeave = (e, zone) => {
        if (e.currentTarget.contains(e.relatedTarget)) return;
        setDragOverZone((z) => (z === zone ? null : z));
    };
    const onZoneDrop = (e, zone) => {
        e.preventDefault();
        const drag = dragData.current;
        setDragOverZone(null);
        if (!drag) return;
        if (drag.from === "fields" && used(drag.key)) { onDragEnd(); return; }
        const index = dropIndexAt(e, e.currentTarget);
        if (drag.from === zone || drag.from === "fields") {
            setListOf(zone)(insertAt(listOf(zone), drag.key, index));
        } else {
            // moving between rows <-> cols
            setListOf(drag.from)(listOf(drag.from).filter((k) => k !== drag.key));
            setListOf(zone)(insertAt(listOf(zone), drag.key, index));
        }
        if (drag.from === "fields") setHeavyConfirmed(false);
        onDragEnd();
    };
    const onFieldsDragOver = (e) => { if (dragData.current && dragData.current.from !== "fields") { e.preventDefault(); setDragOverZone("fields"); } };
    const onFieldsDragLeave = (e) => { if (e.currentTarget.contains(e.relatedTarget)) return; setDragOverZone((z) => (z === "fields" ? null : z)); };
    const onFieldsDrop = (e) => {
        e.preventDefault();
        setDragOverZone(null);
        const drag = dragData.current;
        if (drag && drag.from !== "fields") { setListOf(drag.from)(listOf(drag.from).filter((k) => k !== drag.key)); setHeavyConfirmed(false); }
        onDragEnd();
    };

    // Plan: which source will be used (warn before a slow fact query)
    useEffect(() => {
        if (!filter || (!rows.length && !cols.length)) { setHeavy(false); return; }
        API.plan({ rows, cols, filters: filterToRequest(filter).filters }).then((res) => setHeavy(!!res.data.data.heavy)).catch(() => setHeavy(false));
    }, [rows, cols, filter]);

    const build = (force = false) => {
        if (!filter) return;
        if (!rows.length && !cols.length) { showToast("error", t("analytics.emptyPivot")); return; }
        if (heavy && !force && !heavyConfirmed) return;
        setLoading(true);
        const req = filterToRequest(filter);
        const apiMeasures = Array.from(new Set(measures.flatMap((m) => (m === "avg" ? [avgCurrency, "qty"] : [m]))));
        API.pivot({ dataType: req.dataType, from: req.periods[0].from, to: req.periods[0].to, dtID: req.dtID, filters: req.filters, rows, cols, measures: apiMeasures.length ? apiMeasures : ["usd"], allowFact: heavy })
            .then((res) => setResult({ ...res.data.data, rowsDims: rows, colsDims: cols, measures: measures.length ? measures : ["usd"] }))
            .catch((err) => showToast("error", err?.response?.data?.message || err.message))
            .finally(() => setLoading(false));
    };

    const doExport = () => {
        if (!result) return;
        const sheet = pivotToSheet(result.records, result.rowsDims, result.colsDims, result.measures, title, t, avgCurrency);
        exportToExcel(`almir_pivot_${result.from}_${result.to}`, [{ name: "Pivot", ...sheet }]);
    };

    const cubeLabel = (c) => t(c === "l1" ? "analytics.cubeL1" : c === "l2" ? "analytics.cubeL2" : "analytics.cubeFact");
    const grouped = useMemo(() => GROUPS.map((g) => ({ g, items: dims.filter((d) => d.group === g && (filter?.dataType === 2 || d.key !== "district")) })), [dims, filter]);

    if (!filter) return <div className="mt-5"><Loading /></div>;

    const Zone = ({ zone, list, label }) => (
        <>
            <div className="pv-zone-title"><span>{label}</span>{list.length ? <button className="btn btn-link btn-sm p-0" onClick={() => (zone === "rows" ? setRows([]) : setCols([]))}>{t("analytics.reset")}</button> : null}</div>
            <div
                className={`pv-zone ${dragOverZone === zone ? "drag-over" : ""}`}
                onDragOver={(e) => onZoneDragOver(e, zone)}
                onDragLeave={(e) => onZoneDragLeave(e, zone)}
                onDrop={(e) => onZoneDrop(e, zone)}
            >
                {!list.length ? <span className="ph">{t("analytics.dropHereHint")}</span> : list.map((k) => (
                    <span
                        className={`pv-chip ${draggingKey === k ? "dragging" : ""}`}
                        key={k}
                        draggable
                        onDragStart={(e) => onChipDragStart(e, zone, k)}
                        onDragEnd={onDragEnd}
                    >
                        <span className="grip" aria-hidden="true">⠿</span>
                        {title(k)}
                        <button title="←" onClick={() => move(zone, k, -1)}>‹</button>
                        <button title="→" onClick={() => move(zone, k, 1)}>›</button>
                        <button title="×" onClick={() => remove(zone, k)}>×</button>
                    </span>
                ))}
            </div>
        </>
    );

    return (
        <div className="an-page">
            <div className="an-head">
                <div>
                    <div className="d-flex align-items-center gap-2 flex-wrap">
                        <h2 className="m-0">{t("analytics.pivotTitle")}</h2>
                        <DataTypeSwitch value={filter.dataType} onChange={setDataType} disabled={loading} />
                    </div>
                    <div className="an-sub">
                        {t("analytics.pivotSubtitle")} ·
                        <span className="an-chip ms-2 me-2">{periodLabel(filter.periods[0], lang)}</span>
                        {filter.region ? <span className="an-chip me-2">{filter.region.label}{filter.district ? ` / ${filter.district.label}` : ""}</span> : null}
                    </div>
                </div>
                <div className="an-toolbar">
                    <button className="btn btn-outline-primary btn-sm" onClick={() => setToggle(true)}><i className="fas fa-sliders-h me-1" />{t("analytics.filters")}</button>
                    <button className="btn btn-outline-success btn-sm" onClick={doExport} disabled={!result}><i className="fas fa-file-excel me-1" />{t("analytics.exportExcel")}</button>
                    <button className="btn btn-primary btn-sm" onClick={() => build(false)} disabled={loading || (heavy && !heavyConfirmed)}><i className="fas fa-play me-1" />{loading ? t("analytics.building") : t("analytics.build")}</button>
                </div>
            </div>

            <div className="pv-layout">
                <div className="an-card">
                    <div className="an-card-head"><h4>{t("analytics.availableFields")}</h4></div>
                    <div
                        className={`an-card-body pv-fields ${dragOverZone === "fields" ? "drag-over" : ""}`}
                        onDragOver={onFieldsDragOver}
                        onDragLeave={onFieldsDragLeave}
                        onDrop={onFieldsDrop}
                    >
                        <div className="an-note mb-2">{t("analytics.dropHint")}</div>
                        {grouped.map(({ g, items }) => items.length ? (
                            <div className="group" key={g}>
                                <div className="group-title">{t(`analytics.group_${g}`)}</div>
                                {items.map((d) => (
                                    <div
                                        key={d.key}
                                        className={`pv-field ${used(d.key) ? "used" : ""}`}
                                        draggable={!used(d.key)}
                                        onDragStart={(e) => onFieldDragStart(e, d.key)}
                                        onDragEnd={onDragEnd}
                                        onClick={(e) => addTo(e.shiftKey ? "cols" : "rows", d.key)}
                                    >
                                        <span>{title(d.key)}</span>
                                        {!used(d.key) ? (
                                            <span className="acts">
                                                <button onClick={(e) => { e.stopPropagation(); addTo("rows", d.key); }}>{t("analytics.rows")}</button>
                                                <button onClick={(e) => { e.stopPropagation(); addTo("cols", d.key); }}>{t("analytics.cols")}</button>
                                            </span>
                                        ) : null}
                                    </div>
                                ))}
                            </div>
                        ) : null)}
                    </div>
                </div>

                <div>
                    <div className="an-card mb-3">
                        <div className="an-card-body">
                            <div className="row">
                                <div className="col-lg-5"><Zone zone="rows" list={rows} label={t("analytics.rows")} /></div>
                                <div className="col-lg-1 d-flex align-items-end justify-content-center pb-2">
                                    <button className="btn btn-outline-secondary btn-sm" title="⇄" onClick={swap}>⇄</button>
                                </div>
                                <div className="col-lg-6"><Zone zone="cols" list={cols} label={t("analytics.cols")} /></div>
                            </div>
                            <div className="pv-zone-title mt-2"><span>{t("analytics.measures")}</span></div>
                            <div className="d-flex flex-wrap gap-2 align-items-center">
                                {ALL_MEASURES.map((m) => (
                                    <label key={m} className={`pv-chip measure ${measures.includes(m) ? "" : "opacity-50"}`} style={{ cursor: "pointer" }}>
                                        <input type="checkbox" className="me-1" checked={measures.includes(m)} onChange={() => toggleMeasure(m)} />
                                        {m === "qty" ? t("analytics.qty") : m === "avg" ? t("analytics.avgPrice") : MEASURE_LABEL[m]}
                                    </label>
                                ))}
                                {measures.includes("avg") ? (
                                    <select className="form-select form-select-sm" style={{ width: "auto" }} value={avgCurrency} onChange={(e) => setAvgCurrency(e.target.value)}>
                                        {CURRENCIES.map((c) => <option key={c.value} value={c.value}>{t("analytics.avgPrice")} {c.label}</option>)}
                                    </select>
                                ) : null}
                            </div>
                            {heavy ? (
                                <div className="pv-warn mt-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                                    <span><i className="fas fa-triangle-exclamation me-2" />{t("analytics.heavyWarning")}</span>
                                    {!heavyConfirmed ? <button className="btn btn-warning btn-sm" onClick={() => { setHeavyConfirmed(true); build(true); }}>{t("analytics.heavyContinue")}</button> : null}
                                </div>
                            ) : null}
                        </div>
                    </div>

                    <div className="an-card">
                        <div className="an-card-body">
                            {loading ? <div className="my-5"><Loading /></div> : !result ? <div className="an-empty">{t("analytics.emptyPivot")}</div> : !result.records.length ? <div className="an-empty">{t("analytics.noData")}</div> : (
                                <>
                                    {result.truncated ? <div className="pv-warn mb-2">{t("analytics.truncated").replace("{n}", result.limit)}</div> : null}
                                    <PivotGrid records={result.records} rowsDims={result.rowsDims} colsDims={result.colsDims} measures={result.measures} dimTitle={title} t={t} avgCurrency={avgCurrency} />
                                    <div className="pv-meta">
                                        <span>{t("analytics.cube")}: {cubeLabel(result.cube)}</span>
                                        <span>{t("analytics.elapsed")}: {(result.elapsed_ms / 1000).toFixed(2)} s</span>
                                        <span>{result.records.length} rec.</span>
                                        {meta && meta.refreshed_at ? <span>{t("analytics.refreshedAt")}: {meta.refreshed_at}</span> : null}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <AnalyticsFilter toggle={toggle} setToggle={setToggle} value={filter} onApply={(f) => { setFilter(f); setResult(null); setHeavyConfirmed(false); }} range={meta ? meta.range : null} meta={meta} maxPeriods={1} />
        </div>
    );
};

const mapStateToProps = (state) => ({ lang: state.language.lang });
export default connect(mapStateToProps)(Pivot);
