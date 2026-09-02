import React, { useEffect, useState } from "react";
import { connect } from "react-redux";
import { TR } from "../../../utils/helpers";
import MultiSelect from "../MultiSelect";
import ServerSelect from "../React-Select-Server";
import Region from "../../../services/cruds/RegionService";
import District from "../../../services/cruds/DistrictService";
import Distributor from "../../../services/cruds/DistributorService";
import CounterParty from "../../../services/cruds/CounterPartyService";
import Company from "../../../services/cruds/CompanyService";
import Manufacturer from "../../../services/cruds/ManufacturerService";
import Drugs from "../../../services/cruds/DrugsService";
import Inn from "../../../services/cruds/InnService";
import DForm from "../../../services/cruds/DFormService";
import DFarmGroup from "../../../services/cruds/DFarmGroupService";
import TGroup from "../../../services/cruds/TGroupService";
import TradeMark from "../../../services/cruds/TradeMarkService";
import Country from "../../../services/cruds/CountryService";
import { addMonths, dimTitle, monthOptions, monthsBetween, yearOptions } from "../../../utils/analytics";
import "./analytics.css";

/** Month + year pair of native <select>s for a 'YYYY-MM' value — free year jumps with no picker-range quirks. */
const MonthYearSelect = ({ value, years, lang, onChange }) => {
    const [y, m] = value ? value.split("-").map(Number) : [years[0], 1];
    const pad = (n) => String(n).padStart(2, "0");
    return (
        <div className="d-flex gap-1">
            <select className="form-select form-select-sm" value={m} onChange={(e) => onChange(`${y}-${pad(e.target.value)}`)}>
                {monthOptions(lang).map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
            </select>
            <select className="form-select form-select-sm my-year-select" value={y} onChange={(e) => onChange(`${e.target.value}-${pad(m)}`)}>
                {(years.includes(y) ? years : [y, ...years]).map((yy) => <option key={yy} value={yy}>{yy}</option>)}
            </select>
        </div>
    );
};

/** Dimensions that can be used as extra filters and the reference services behind them. */
export const FILTER_DIMS = [
    { key: "distributor", service: () => Distributor },
    { key: "party", service: (dataType) => (dataType === 2 ? CounterParty : Company) },
    { key: "manufacturer", service: () => Manufacturer },
    { key: "country", service: () => Country },
    { key: "drug", service: () => Drugs },
    { key: "inn", service: () => Inn },
    { key: "form", service: () => DForm },
    { key: "farm_group", service: () => DFarmGroup },
    { key: "ts_group", service: () => TGroup },
    { key: "trademark", service: () => TradeMark },
];

/** `range` here is the range to default the initial period into (typically the given dataType's own
 * range, e.g. meta.range_by_type[2]) — NOT the combined year-picker bound, which can span far wider. */
export const emptyFilter = (range, dataType = 2) => ({
    dataType,
    periods: [{ from: range ? addMonths(range.to, -11) : null, to: range ? range.to : null }],
    dtID: [],
    region: null,
    district: null,
    extra: [],
});

/** meta.range_by_type[dataType], falling back to the combined range. */
export const rangeForType = (meta, dataType) => (meta && meta.range_by_type && meta.range_by_type[dataType]) || (meta && meta.range) || null;

/**
 * Right-side filter panel shared by Analytics and Pivot.
 * props: toggle, setToggle, value (filter state), onApply(filter), range {from,to}, meta,
 *        maxPeriods (1 = single period), title
 */
const AnalyticsFilter = (props) => {
    const { toggle, setToggle, value, onApply, range, meta, lang, productTypes, maxPeriods = 4 } = props;
    const [f, setF] = useState(value);
    const [regionOptions, setRegionOptions] = useState([]);
    const [districtOptions, setDistrictOptions] = useState([]);
    const [extraOptions, setExtraOptions] = useState({});
    const [loadingKey, setLoadingKey] = useState(null);
    const [timer, setTimer] = useState(null);

    useEffect(() => {
        if (toggle) setF(JSON.parse(JSON.stringify(value)));
    }, [toggle]);

    const years = yearOptions(range && range.from, range && range.to);

    /** Change one bound of one period, keeping from <= to by nudging the other bound along. */
    const setPeriod = (i, field, ym) => {
        const periods = f.periods.map((p, idx) => {
            if (idx !== i) return p;
            const next = { ...p, [field]: ym };
            if (next.from && next.to && next.from > next.to) {
                if (field === "from") next.to = ym; else next.from = ym;
            }
            return next;
        });
        setF({ ...f, periods });
    };
    const addPeriod = () => {
        if (f.periods.length >= maxPeriods) return;
        const last = f.periods[f.periods.length - 1];
        const len = last.from && last.to ? monthsBetween(last.from, last.to) : 12;
        const to = last.from ? addMonths(last.from, -1) : (range && range.to);
        const from = to ? addMonths(to, -(len - 1)) : (range && range.from);
        setF({ ...f, periods: [...f.periods, { from, to }] });
    };
    const removePeriod = (i) => setF({ ...f, periods: f.periods.filter((_, idx) => idx !== i) });

    /** Debounced server search for react-select (regions, districts, extra filters). */
    const filterDb = (arr_key, API, search, index, additional) => {
        clearTimeout(timer);
        const t = setTimeout(() => {
            setLoadingKey(arr_key);
            const req = API.name === "drug" ? API.search(search, { dtID: f.dtID }) : API.select(true, false, search, additional);
            req.then((res) => {
                const list = (res.data.data || []).map((k) => ({ value: k.id, label: k.full_name || k.name || "" }));
                if (arr_key === "region") setRegionOptions(list);
                else if (arr_key === "district") setDistrictOptions(list);
                else setExtraOptions((prev) => ({ ...prev, [arr_key]: list }));
            }).finally(() => setLoadingKey(null));
        }, 500);
        setTimer(t);
    };

    const addExtra = () => setF({ ...f, extra: [...f.extra, { dim: "", values: [] }] });
    const setExtra = (i, patch) => setF({ ...f, extra: f.extra.map((e, idx) => (idx === i ? { ...e, ...patch } : e)) });
    const removeExtra = (i) => setF({ ...f, extra: f.extra.filter((_, idx) => idx !== i) });

    const valid = f.periods.every((p) => p.from && p.to);

    return (
        <div className={`sidebar-right media-width an-filter ${toggle ? "show" : ""}`}>
            <div className="bg-overlay" onClick={() => setToggle(false)}></div>
            <div className="sidebar-right-inner media-width p-4">
                <h5 className="mb-1">{TR(lang, "analytics.filters")}</h5>
                <div className="an-note mb-2">{TR(lang, "analytics.monthsOnly")}</div>
                {meta && meta.restricted ? <div className="an-note mb-2 text-warning">{TR(lang, "analytics.restrictedHint")}</div> : null}

                <h6>{TR(lang, maxPeriods > 1 ? "analytics.periods" : "analytics.period")}</h6>
                {f.periods.map((p, i) => (
                    <div className="period-row mb-2" key={i}>
                        <div className="form-group">
                            <label>{i + 1}. {TR(lang, "analytics.from")}</label>
                            <MonthYearSelect value={p.from} years={years} lang={lang} onChange={(ym) => setPeriod(i, "from", ym)} />
                        </div>
                        <div className="form-group">
                            <label>{TR(lang, "analytics.to")}</label>
                            <MonthYearSelect value={p.to} years={years} lang={lang} onChange={(ym) => setPeriod(i, "to", ym)} />
                        </div>
                        <div className="pb-1">
                            {f.periods.length > 1 ? (
                                <button type="button" className="icon-btn-danger" title={TR(lang, "analytics.removePeriod")} aria-label={TR(lang, "analytics.removePeriod")} onClick={() => removePeriod(i)}>
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            ) : null}
                        </div>
                    </div>
                ))}
                {maxPeriods > 1 && f.periods.length < maxPeriods ? (
                    <button type="button" className="btn btn-link btn-sm p-0" onClick={addPeriod}>
                        <i className="fas fa-plus me-1" />{TR(lang, "analytics.addPeriod")}
                    </button>
                ) : null}

                <h6>{TR(lang, "products.dt")}</h6>
                <MultiSelect placeholder="products.dt" options={productTypes}
                    value={productTypes.filter((o) => f.dtID.includes(o.value))}
                    onChange={(e) => setF({ ...f, dtID: (e || []).map((o) => o.value) })} />

                {f.dataType === 2 ? (
                    <>
                        <h6>{TR(lang, "products.region")}</h6>
                        <ServerSelect id="an-region" API={Region} arr_key="region" options={regionOptions} isClearable
                            value={f.region ? [f.region] : null} isLoading={loadingKey === "region"} filterDb={filterDb}
                            additional={{ country_id: 19 }} placeholder={TR(lang, "products.region")}
                            onChange={(e) => setF({ ...f, region: e ? { value: e.value, label: e.label } : null, district: null })} />
                        {f.region ? (
                            <>
                                <h6>{TR(lang, "products.district")}</h6>
                                <ServerSelect id="an-district" API={District} arr_key="district" options={districtOptions} isClearable
                                    value={f.district ? [f.district] : null} isLoading={loadingKey === "district"} filterDb={filterDb}
                                    additional={{ regionID: f.region.value }} placeholder={TR(lang, "products.district")}
                                    onChange={(e) => setF({ ...f, district: e ? { value: e.value, label: e.label } : null })} />
                            </>
                        ) : null}
                    </>
                ) : null}

                <h6>{TR(lang, "analytics.extraFilter")}</h6>
                {f.extra.map((e, i) => {
                    const def = FILTER_DIMS.find((d) => d.key === e.dim);
                    const API = def ? def.service(f.dataType) : null;
                    return (
                        <div className="extra-row" key={i}>
                            <select className="form-select form-select-sm" value={e.dim} onChange={(ev) => setExtra(i, { dim: ev.target.value, values: [] })}>
                                <option value="">{TR(lang, "analytics.chooseField")}</option>
                                {FILTER_DIMS.map((d) => <option key={d.key} value={d.key}>{dimTitle(TR, lang, meta, d.key)}</option>)}
                            </select>
                            <div>
                                {API ? (
                                    <ServerSelect API={API} arr_key={`extra_${e.dim}`} isMulti options={[...(e.values || []), ...((extraOptions[`extra_${e.dim}`] || []).filter((o) => !(e.values || []).some((v) => v.value === o.value)))]}
                                        value={e.values} isLoading={loadingKey === `extra_${e.dim}`} filterDb={filterDb}
                                        placeholder={TR(lang, "analytics.chooseValues")}
                                        onChange={(vals) => setExtra(i, { values: (vals || []).map((v) => ({ value: v.value, label: v.label })) })} />
                                ) : null}
                            </div>
                            <button type="button" className="icon-btn-danger mt-1" title={TR(lang, "content.close")} aria-label={TR(lang, "content.close")} onClick={() => removeExtra(i)}>
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    );
                })}
                <button type="button" className="btn btn-link btn-sm p-0" onClick={addExtra}>
                    <i className="fas fa-plus me-1" />{TR(lang, "analytics.addFilter")}
                </button>

                <div className="an-filter-actions">
                    <button className="btn btn-outline-secondary btn-sm" onClick={() => setF(emptyFilter(rangeForType(meta, f.dataType), f.dataType))}>{TR(lang, "analytics.reset")}</button>
                    <div className="d-flex gap-2">
                        <button className="btn btn-danger btn-sm" onClick={() => setToggle(false)}>{TR(lang, "content.close")}</button>
                        <button className="btn btn-primary btn-sm" disabled={!valid} onClick={() => { onApply(f); setToggle(false); }}>{TR(lang, "analytics.apply")}</button>
                    </div>
                </div>
            </div>
        </div>
    );
};

const mapStateToProps = (state) => ({
    lang: state.language.lang,
    productTypes: state.main.productTypes || [],
});

export default connect(mapStateToProps)(AnalyticsFilter);

/** Filter state -> API payload */
export function filterToRequest(f) {
    const filters = {};
    if (f.dataType === 2) {
        if (f.district) filters.district = [f.district.value];
        else if (f.region) filters.region = [f.region.value];
    }
    (f.extra || []).forEach((e) => {
        if (e.dim && e.values && e.values.length) filters[e.dim] = e.values.map((v) => v.value);
    });
    return {
        dataType: f.dataType,
        periods: f.periods.map((p) => ({ from: p.from, to: p.to })),
        dtID: f.dtID || [],
        filters,
    };
}
