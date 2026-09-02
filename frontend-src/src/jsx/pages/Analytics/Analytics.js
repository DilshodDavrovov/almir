import React, { useEffect, useMemo, useState } from "react";
import { connect } from "react-redux";
import { Modal } from "react-bootstrap";
import { TR } from "../../../utils/helpers";
import { showToast } from "../../../utils";
import API from "../../../services/AnalyticsService";
import AnalyticsFilter, { emptyFilter, filterToRequest, rangeForType } from "../../components/Analytics/AnalyticsFilter";
import DataTypeSwitch from "../../components/DataTypeSwitch";
import { DynamicsChart, TopChart } from "../../components/Analytics/Charts";
import GeoMap from "../../components/Analytics/GeoMap";
import DetailsTable from "../../components/Analytics/DetailsTable";
import Loading from "../../components/Loading";
import { CURRENCIES, change, compactNumber, dimTitle, exportToExcel, fmt, monthsBetween, pct, periodLabel, ymLabel } from "../../../utils/analytics";
import "../../components/Analytics/analytics.css";

const TOP_DIMS = ["distributor", "party", "manufacturer", "country", "drug", "inn", "form", "farm_group", "ts_group", "trademark", "drug_type", "region"];

/** dims that can be combined with `dim` inside the cubes (no fact-table fallback) */
function compatibleDims(meta, dim, candidates) {
    if (!meta) return candidates;
    const cubesOf = (k) => ((meta.dimensions || []).find((d) => d.key === k) || {}).cubes || [];
    const a = cubesOf(dim).filter((c) => c !== "fact");
    return candidates.filter((k) => k !== dim && cubesOf(k).some((c) => c !== "fact" && a.includes(c)));
}

const Analytics = ({ lang }) => {
    const [meta, setMeta] = useState(null);
    const [filter, setFilter] = useState(null);
    const [toggle, setToggle] = useState(false);
    const [loading, setLoading] = useState(true);
    const [summary, setSummary] = useState([]);
    const [top, setTop] = useState([]);
    const [topDim, setTopDim] = useState("distributor");
    const [topLimit, setTopLimit] = useState(10);
    const [topType, setTopType] = useState("bar");
    const [topPeriod, setTopPeriod] = useState(0);
    const [topLoading, setTopLoading] = useState(false);
    const [showTopTable, setShowTopTable] = useState(true);
    const [dynType, setDynType] = useState("column");
    const [geo, setGeo] = useState([]);
    const [geoPeriod, setGeoPeriod] = useState(0);
    const [selectedRegion, setSelectedRegion] = useState(null);
    const [districts, setDistricts] = useState([]);
    const [leaders, setLeaders] = useState([]);
    const [leaderDim, setLeaderDim] = useState("distributor");
    const [geoLoading, setGeoLoading] = useState(false);
    const [currency, setCurrency] = useState("usd");
    const [metric, setMetric] = useState("sum");
    const [drill, setDrill] = useState({ open: false, item: null, dim: "drug", rows: [], loading: false });

    const t = (k) => TR(lang, k);
    const labels = { name: t("analytics.name"), sum: t("analytics.sum"), qty: t("analytics.qty"), share: t("analytics.share"), total: t("analytics.total") };
    const dataType = filter ? filter.dataType : 2;

    // ---- loading -------------------------------------------------------------
    useEffect(() => {
        API.meta(2).then((res) => {
            const m = res.data.data;
            setMeta(m);
            const f = emptyFilter(rangeForType(m, 2), 2);
            setFilter(f);
            loadAll(f, m);
        }).catch((err) => { setLoading(false); showToast("error", err?.response?.data?.message || "Analytics is unavailable"); });
    }, []);

    const loadAll = (f, m) => {
        setLoading(true);
        setSelectedRegion(null); setDistricts([]); setLeaders([]);
        const req = filterToRequest(f);
        Promise.all([
            API.summary(req),
            API.top({ ...req, dim: topDim, limit: topLimit }),
            f.dataType === 2 ? API.geo({ ...req, level: "region" }) : Promise.resolve({ data: { data: [] } }),
        ]).then(([s, tp, g]) => {
            setSummary(s.data.data || []);
            setTop(tp.data.data || []);
            setGeo(g.data.data || []);
            setTopPeriod(0); setGeoPeriod(0);
        }).catch((err) => showToast("error", err?.response?.data?.message || err.message))
            .finally(() => setLoading(false));
    };

    const applyFilter = (f) => { setFilter(f); loadAll(f, meta); };

    /** Quick Приход/Продажа switch from the report header — same reset the old in-panel radio did. */
    const setDataType = (t) => {
        if (!filter) return;
        applyFilter({ ...filter, dataType: t, region: null, district: null, extra: filter.extra.filter((e) => e.dim !== "party") });
    };

    const reloadTop = (dim, limit) => {
        if (!filter) return;
        setTopLoading(true);
        API.top({ ...filterToRequest(filter), dim, limit }).then((res) => setTop(res.data.data || []))
            .catch((err) => showToast("error", err?.response?.data?.message || err.message))
            .finally(() => setTopLoading(false));
    };

    const selectRegion = (id) => {
        setSelectedRegion(id);
        setDistricts([]); setLeaders([]);
        if (!id || !filter) return;
        loadRegionDetails(id, leaderDim);
    };
    const loadRegionDetails = (id, dim) => {
        setGeoLoading(true);
        const req = filterToRequest(filter);
        req.filters = { ...req.filters, region: [id] };
        delete req.filters.district;
        Promise.all([
            // geo() needs the region as a top-level regionID (it builds its own filter for the district level)
            API.geo({ ...req, level: "district", regionID: id }),
            API.top({ ...req, dim, limit: 10 }),
        ]).then(([d, l]) => { setDistricts(d.data.data || []); setLeaders(l.data.data || []); })
            .catch((err) => showToast("error", err?.response?.data?.message || err.message))
            .finally(() => setGeoLoading(false));
    };

    const openDrill = (item) => {
        const dims = compatibleDims(meta, topDim, TOP_DIMS.filter((d) => d !== "region"));
        const dim = dims.includes(drill.dim) ? drill.dim : dims[0];
        setDrill({ open: true, item, dim, rows: [], loading: true, dims });
        loadDrill(item, dim);
    };
    const loadDrill = (item, dim) => {
        const req = filterToRequest(filter);
        req.periods = [req.periods[topPeriod] || req.periods[0]];
        req.filters = { ...req.filters, [topDim]: [item.id] };
        setDrill((d) => ({ ...d, dim, loading: true }));
        API.top({ ...req, dim, limit: 15 }).then((res) => setDrill((d) => ({ ...d, rows: (res.data.data[0] || {}).rows || [], loading: false })))
            .catch((err) => { showToast("error", err?.response?.data?.message || err.message); setDrill((d) => ({ ...d, loading: false })); });
    };

    // ---- derived -------------------------------------------------------------
    const p1 = summary[0];
    const p2 = summary[1];
    const cur = CURRENCIES.find((c) => c.value === currency) || CURRENCIES[0];
    const kpi = useMemo(() => {
        if (!p1) return null;
        const v = (p) => (metric === "qty" ? p.total.qty : p.total[currency]);
        const avg = (p) => (p.total.qty ? p.total[currency] / p.total.qty : 0);
        return {
            value: v(p1), valueDelta: p2 ? change(v(p1), v(p2)) : null,
            qty: p1.total.qty, qtyDelta: p2 ? change(p1.total.qty, p2.total.qty) : null,
            avg: avg(p1), avgDelta: p2 ? change(avg(p1), avg(p2)) : null,
            months: monthsBetween(p1.from, p1.to),
            sum: p1.total[currency], sumDelta: p2 ? change(p1.total[currency], p2.total[currency]) : null,
        };
    }, [summary, currency, metric]);

    const topRows = (top[topPeriod] || top[0] || {}).rows || [];
    const topTotal = (top[topPeriod] || top[0] || {}).total;
    const geoRows = (geo[geoPeriod] || geo[0] || {}).rows || [];
    const geoTotal = (geo[geoPeriod] || geo[0] || {}).total;
    const districtRows = (districts[geoPeriod] || districts[0] || {}).rows || [];
    const leaderRows = (leaders[geoPeriod] || leaders[0] || {}).rows || [];
    const selectedRegionRow = geoRows.find((r) => r.id === selectedRegion);

    const Delta = ({ v }) => (v == null ? null : <span className={`delta ${v >= 0 ? "up" : "down"}`}>{v >= 0 ? "▲" : "▼"} {Math.abs(v).toFixed(1)}%</span>);

    const doExport = () => {
        const sheets = [];
        sheets.push({
            name: "KPI",
            columns: [{ header: t("analytics.period"), key: "p", width: 30 }, { header: "USD", key: "usd", numFmt: "#,##0.00" }, { header: "UZS", key: "uzs", numFmt: "#,##0" }, { header: "EUR", key: "eur", numFmt: "#,##0.00" }, { header: "RUB", key: "rub", numFmt: "#,##0.00" }, { header: t("analytics.qty"), key: "qty", numFmt: "#,##0" }],
            rows: summary.map((p) => ({ p: periodLabel(p, lang), ...p.total })),
        });
        summary.forEach((p, i) => sheets.push({
            name: `${t("analytics.dynamics")} ${i + 1}`.slice(0, 31),
            columns: [{ header: t("analytics.month"), key: "m", width: 16 }, { header: "USD", key: "usd", numFmt: "#,##0.00" }, { header: "UZS", key: "uzs", numFmt: "#,##0" }, { header: "EUR", key: "eur", numFmt: "#,##0.00" }, { header: "RUB", key: "rub", numFmt: "#,##0.00" }, { header: t("analytics.qty"), key: "qty", numFmt: "#,##0" }],
            rows: p.months.map((m) => ({ m: ymLabel(m.label, lang), usd: m.usd, uzs: m.uzs, eur: m.eur, rub: m.rub, qty: m.qty })),
        }));
        const rankCols = [{ header: "#", key: "n", width: 6 }, { header: t("analytics.name"), key: "name", width: 40 }, { header: "USD", key: "usd", numFmt: "#,##0.00" }, { header: "UZS", key: "uzs", numFmt: "#,##0" }, { header: "EUR", key: "eur", numFmt: "#,##0.00" }, { header: "RUB", key: "rub", numFmt: "#,##0.00" }, { header: t("analytics.qty"), key: "qty", numFmt: "#,##0" }, { header: `${t("analytics.share")} USD %`, key: "share_usd" }, { header: `${t("analytics.share")} ${t("analytics.qty")} %`, key: "share_qty" }];
        const rank = (rows) => rows.map((r, i) => ({ n: i + 1, ...r }));
        sheets.push({ name: `${t("analytics.top")} ${dimTitle(TR, lang, meta, topDim)}`.slice(0, 31), columns: rankCols, rows: rank(topRows) });
        if (geoRows.length) sheets.push({ name: t("products.region").slice(0, 31), columns: rankCols, rows: rank(geoRows) });
        if (districtRows.length) sheets.push({ name: `${t("analytics.districts")} ${selectedRegionRow ? selectedRegionRow.name : ""}`.slice(0, 31), columns: rankCols, rows: rank(districtRows) });
        exportToExcel(`almir_analytics_${p1 ? p1.from + "_" + p1.to : ""}`, sheets);
    };

    if (!filter) return <div className="mt-5"><Loading /></div>;

    return (
        <div className="an-page">
            <div className="an-head">
                <div>
                    <div className="d-flex align-items-center gap-2 flex-wrap">
                        <h2 className="m-0">{t("analytics.title")}</h2>
                        <DataTypeSwitch value={dataType} onChange={setDataType} disabled={loading} />
                    </div>
                    <div className="an-sub">
                        {summary.map((p, i) => <span className="an-chip me-2" key={i}><span className="dot" style={{ background: i ? "#13b497" : "#4f6bed" }} />{periodLabel(p, lang)}</span>)}
                        {filter.region ? <span className="an-chip me-2">{filter.region.label}{filter.district ? ` / ${filter.district.label}` : ""}</span> : null}
                        {meta && meta.refreshed_at ? <span className="an-note">{t("analytics.refreshedAt")}: {meta.refreshed_at}</span> : null}
                    </div>
                </div>
                <div className="an-toolbar">
                    <select className="form-select form-select-sm" value={metric} onChange={(e) => setMetric(e.target.value)}>
                        <option value="sum">{t("analytics.sum")}</option>
                        <option value="qty">{t("analytics.qty")}</option>
                    </select>
                    <select className="form-select form-select-sm" value={currency} onChange={(e) => setCurrency(e.target.value)} disabled={metric === "qty"}>
                        {CURRENCIES.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
                    </select>
                    <button className="btn btn-outline-primary btn-sm" onClick={() => setToggle(true)}><i className="fas fa-sliders-h me-1" />{t("analytics.filters")}</button>
                    <button className="btn btn-outline-success btn-sm" onClick={doExport} disabled={loading || !p1}><i className="fas fa-file-excel me-1" />{t("analytics.exportExcel")}</button>
                </div>
            </div>

            {loading ? <div className="mt-5"><Loading /></div> : !p1 ? <div className="an-card"><div className="an-empty">{t("analytics.noData")}</div></div> : (
                <>
                    {/* KPI */}
                    <div className="an-kpi">
                        <div className="kpi">
                            <div className="t">{t("analytics.sum")} · {cur.label}</div>
                            <div className="v">{compactNumber(kpi.sum, lang)} <Delta v={kpi.sumDelta} /></div>
                            <div className="s">{fmt(kpi.sum, 2)} {cur.sign}</div>
                        </div>
                        <div className="kpi alt">
                            <div className="t">{t("analytics.qty")}</div>
                            <div className="v">{compactNumber(kpi.qty, lang)} <Delta v={kpi.qtyDelta} /></div>
                            <div className="s">{fmt(kpi.qty, 0)}</div>
                        </div>
                        <div className="kpi gray">
                            <div className="t">{t("analytics.avgPrice")} · {cur.label}</div>
                            <div className="v">{fmt(kpi.avg, 2)} <Delta v={kpi.avgDelta} /></div>
                            <div className="s">{p2 ? t("analytics.vsPrevious") : `${kpi.months} ${t("analytics.months").toLowerCase()}`}</div>
                        </div>
                        <div className="kpi gray">
                            <div className="t">{t("analytics.period")}</div>
                            <div className="v" style={{ fontSize: 16 }}>{periodLabel(p1, lang)}</div>
                            <div className="s">{p2 ? `${t("analytics.vsPrevious")}: ${periodLabel(p2, lang)}` : `${kpi.months} ${t("analytics.months").toLowerCase()}`}</div>
                        </div>
                    </div>

                    {/* Dynamics */}
                    <div className="an-card mb-3">
                        <div className="an-card-head">
                            <h4>{t("analytics.dynamics")}</h4>
                            <div className="an-toolbar">
                                <select className="form-select form-select-sm" value={dynType} onChange={(e) => setDynType(e.target.value)}>
                                    <option value="column">{t("analytics.column")}</option>
                                    <option value="line">{t("analytics.line")}</option>
                                </select>
                            </div>
                        </div>
                        <div className="an-card-body">
                            <DynamicsChart periods={summary} metric={metric} currency={currency} lang={lang} type={dynType} />
                        </div>
                    </div>

                    {/* Top */}
                    <div className={`an-card mb-3 ${topLoading ? "an-loading" : ""}`}>
                        <div className="an-card-head">
                            <h4>{t("analytics.topBy")}: {dimTitle(TR, lang, meta, topDim)}</h4>
                            <div className="an-toolbar">
                                {summary.length > 1 ? (
                                    <select className="form-select form-select-sm" value={topPeriod} onChange={(e) => setTopPeriod(Number(e.target.value))}>
                                        {summary.map((p, i) => <option key={i} value={i}>{i + 1}: {periodLabel(p, lang)}</option>)}
                                    </select>
                                ) : null}
                                <select className="form-select form-select-sm" value={topDim} onChange={(e) => { setTopDim(e.target.value); reloadTop(e.target.value, topLimit); }}>
                                    {TOP_DIMS.filter((d) => dataType === 2 || d !== "region").map((d) => <option key={d} value={d}>{dimTitle(TR, lang, meta, d)}</option>)}
                                </select>
                                <select className="form-select form-select-sm" value={topLimit} onChange={(e) => { setTopLimit(Number(e.target.value)); reloadTop(topDim, Number(e.target.value)); }}>
                                    {[5, 10, 15, 20, 30, 50].map((n) => <option key={n} value={n}>{t("analytics.top")} {n}</option>)}
                                </select>
                                <select className="form-select form-select-sm" value={topType} onChange={(e) => setTopType(e.target.value)}>
                                    <option value="bar">{t("analytics.bar")}</option>
                                    <option value="pie">{t("analytics.pie")}</option>
                                </select>
                                <button className="btn btn-outline-secondary btn-sm" onClick={() => setShowTopTable(!showTopTable)}>{t(showTopTable ? "analytics.hideDetails" : "analytics.details")}</button>
                            </div>
                        </div>
                        <div className="an-card-body">
                            {topRows.length ? (
                                <div className="row">
                                    <div className={showTopTable ? "col-xl-6" : "col-12"}>
                                        <TopChart rows={topRows} metric={metric} currency={currency} lang={lang} type={topType} onClick={(o) => openDrill({ id: o.id, name: o.name })} />
                                        <div className="an-note mt-1"><i className="fas fa-hand-pointer me-1" />{t("analytics.drill")}: {dimTitle(TR, lang, meta, topDim)} → {t("analytics.drillBy").toLowerCase()}</div>
                                    </div>
                                    {showTopTable ? (
                                        <div className="col-xl-6">
                                            <DetailsTable rows={topRows} metric={metric} currency={currency} labels={labels} total={topTotal} onRowClick={(r) => openDrill(r)} />
                                        </div>
                                    ) : null}
                                </div>
                            ) : <div className="an-empty">{t("analytics.noData")}</div>}
                        </div>
                    </div>

                    {/* Geo */}
                    <div className={`an-card mb-3 ${geoLoading ? "an-loading" : ""}`}>
                        <div className="an-card-head">
                            <h4>{t("analytics.geoTitle")}</h4>
                            <div className="an-toolbar">
                                {summary.length > 1 && dataType === 2 ? (
                                    <select className="form-select form-select-sm" value={geoPeriod} onChange={(e) => setGeoPeriod(Number(e.target.value))}>
                                        {summary.map((p, i) => <option key={i} value={i}>{i + 1}: {periodLabel(p, lang)}</option>)}
                                    </select>
                                ) : null}
                                <span className="an-note">{t("analytics.geoHint")}</span>
                            </div>
                        </div>
                        <div className="an-card-body">
                            {dataType !== 2 ? <div className="an-empty">{t("analytics.geoNoData")}</div> : !geoRows.length ? <div className="an-empty">{t("analytics.noData")}</div> : (
                                <div className="an-geo-grid">
                                    <div className="an-map">
                                        <GeoMap rows={geoRows} metric={metric} currency={currency} lang={lang} selectedId={selectedRegion} onSelect={selectRegion} />
                                    </div>
                                    <div>
                                        {!selectedRegion ? (
                                            <>
                                                <h6 className="mb-2">{t("analytics.allRegions")}</h6>
                                                <DetailsTable rows={geoRows} metric={metric} currency={currency} labels={labels} total={geoTotal ? { ...geoTotal, [currency]: geoRows.reduce((s, r) => s + Number(r[currency] || 0), 0) } : null} onRowClick={(r) => selectRegion(r.id)} activeId={selectedRegion} maxHeight={460} />
                                            </>
                                        ) : (
                                            <>
                                                <div className="an-region-crumb mb-2">
                                                    <button className="btn btn-link" onClick={() => selectRegion(null)}><i className="fas fa-arrow-left me-1" />{t("analytics.backToRegions")}</button>
                                                    <strong>{selectedRegionRow ? selectedRegionRow.name : ""}</strong>
                                                    {selectedRegionRow ? <span className="an-chip">{pct(metric === "qty" ? selectedRegionRow.share_qty : selectedRegionRow.share_usd)}</span> : null}
                                                </div>
                                                <h6 className="mb-1">{t("analytics.districts")}</h6>
                                                <DetailsTable rows={districtRows} metric={metric} currency={currency} labels={labels} maxHeight={230} />
                                                <div className="d-flex align-items-center justify-content-between mt-3 mb-1">
                                                    <h6 className="m-0">{t("analytics.regionLeaders")}</h6>
                                                    <select className="form-select form-select-sm" style={{ width: "auto" }} value={leaderDim} onChange={(e) => { setLeaderDim(e.target.value); loadRegionDetails(selectedRegion, e.target.value); }}>
                                                        {["distributor", "party", "manufacturer", "drug", "inn", "trademark", "drug_type"].map((d) => <option key={d} value={d}>{dimTitle(TR, lang, meta, d)}</option>)}
                                                    </select>
                                                </div>
                                                <DetailsTable rows={leaderRows} metric={metric} currency={currency} labels={labels} maxHeight={230} />
                                            </>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </>
            )}

            <AnalyticsFilter toggle={toggle} setToggle={setToggle} value={filter} onApply={applyFilter} range={meta ? meta.range : null} meta={meta} maxPeriods={4} />

            <Modal show={drill.open} onHide={() => setDrill({ ...drill, open: false })} size="lg" centered>
                <Modal.Header closeButton>
                    <Modal.Title style={{ fontSize: 16 }}>{t("analytics.drill")}: {drill.item ? drill.item.name : ""}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <div className="d-flex align-items-center gap-2 mb-2">
                        <span className="an-note">{t("analytics.drillBy")}:</span>
                        <select className="form-select form-select-sm" style={{ width: "auto" }} value={drill.dim} onChange={(e) => loadDrill(drill.item, e.target.value)}>
                            {(drill.dims || []).map((d) => <option key={d} value={d}>{dimTitle(TR, lang, meta, d)}</option>)}
                        </select>
                        {top[topPeriod] ? <span className="an-chip">{periodLabel(top[topPeriod], lang)}</span> : null}
                    </div>
                    {drill.loading ? <Loading /> : drill.rows.length ? (
                        <div className="row">
                            <div className="col-lg-6"><TopChart rows={drill.rows} metric={metric} currency={currency} lang={lang} type="bar" height={300} /></div>
                            <div className="col-lg-6"><DetailsTable rows={drill.rows} metric={metric} currency={currency} labels={labels} maxHeight={380} /></div>
                        </div>
                    ) : <div className="an-empty">{t("analytics.noData")}</div>}
                </Modal.Body>
            </Modal>
        </div>
    );
};

const mapStateToProps = (state) => ({ lang: state.language.lang });
export default connect(mapStateToProps)(Analytics);
