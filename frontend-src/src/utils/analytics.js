import * as ExcelJs from "exceljs";
import { saveAs } from "file-saver";
import { NumberToStr } from "./index";

export const CURRENCIES = [
    { value: "usd", label: "USD", sign: "$" },
    { value: "uzs", label: "UZS", sign: "сум" },
    { value: "eur", label: "EUR", sign: "€" },
    { value: "rub", label: "RUB", sign: "₽" },
];

export const CHART_COLORS = [
    "#4f6bed", "#13b497", "#ff8a4c", "#a259ff", "#ffc107", "#dc3545", "#20c997", "#17a2b8",
    "#94618e", "#6eadf1", "#4cb32b", "#ff6f61", "#00bcd4", "#8d6e63", "#5c6bc0", "#26a69a",
];

/** 'YYYY-MM' -> Date (1st of month) */
export function ymToDate(s) {
    if (!s) return null;
    const [y, m] = s.split("-").map(Number);
    return new Date(y, m - 1, 1);
}

/** Date -> 'YYYY-MM' */
export function dateToYm(d) {
    if (!d) return null;
    const y = d.getFullYear();
    const m = d.getMonth() + 1;
    return `${y}-${m < 10 ? "0" + m : m}`;
}

/** Shift 'YYYY-MM' by n months */
export function addMonths(s, n) {
    const d = ymToDate(s);
    d.setMonth(d.getMonth() + n);
    return dateToYm(d);
}

/** Number of months in [from, to] */
export function monthsBetween(from, to) {
    const a = ymToDate(from), b = ymToDate(to);
    return (b.getFullYear() - a.getFullYear()) * 12 + (b.getMonth() - a.getMonth()) + 1;
}

const MONTHS = {
    рус: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
    eng: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    ўзб: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
};

const MONTHS_FULL = {
    рус: ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
    eng: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
    ўзб: ["Январь", "Феврал", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
};

/** [{value:1,label:'Январь'}, ...] for a month <select>. */
export function monthOptions(lang) {
    const names = MONTHS_FULL[lang] || MONTHS_FULL["рус"];
    return names.map((label, i) => ({ value: i + 1, label }));
}

/** Descending list of years covering [fromYear, toYear], clamped to a sane span if the range is missing/degenerate. */
export function yearOptions(fromYm, toYm) {
    const now = new Date().getFullYear();
    let a = fromYm ? Math.floor(Number(String(fromYm).slice(0, 4))) : now - 6;
    let b = toYm ? Math.floor(Number(String(toYm).slice(0, 4))) : now;
    if (!isFinite(a)) a = now - 6;
    if (!isFinite(b)) b = now;
    if (a > b) [a, b] = [b, a];
    const out = [];
    for (let y = b; y >= a; y--) out.push(y);
    return out;
}

/** 'YYYY-MM' -> 'Янв 2024' */
export function ymLabel(s, lang) {
    if (!s) return "";
    const [y, m] = String(s).split("-").map(Number);
    const names = MONTHS[lang] || MONTHS["рус"];
    return `${names[(m || 1) - 1]} ${y}`;
}

/** Period {from,to} -> 'Янв 2024 – Дек 2024' */
export function periodLabel(p, lang) {
    if (!p) return "";
    if (p.from === p.to) return ymLabel(p.from, lang);
    return `${ymLabel(p.from, lang)} – ${ymLabel(p.to, lang)}`;
}

/** Compact number for axes / cards: 1.2 млн */
export function compactNumber(n, lang) {
    const v = Number(n) || 0;
    const abs = Math.abs(v);
    const units = lang === "eng" ? ["", "K", "M", "B", "T"] : ["", "тыс.", "млн", "млрд", "трлн"];
    let i = 0;
    let x = abs;
    while (x >= 1000 && i < units.length - 1) { x /= 1000; i++; }
    const s = x >= 100 ? x.toFixed(0) : x >= 10 ? x.toFixed(1) : x.toFixed(2);
    return `${v < 0 ? "-" : ""}${s}${units[i] ? " " + units[i] : ""}`;
}

/** Full formatted number with thousands separators and up to 2 decimals */
export function fmt(n, decimals = 2) {
    const v = Number(n);
    if (!isFinite(v)) return "0";
    const r = Math.round(v * Math.pow(10, decimals)) / Math.pow(10, decimals);
    const out = NumberToStr(r);
    return typeof out === "number" ? String(out) : out;
}

export function pct(n) {
    const v = Number(n);
    if (!isFinite(v)) return "0%";
    return `${v.toFixed(v >= 100 ? 0 : 1)}%`;
}

/** Percent change between two values */
export function change(cur, prev) {
    const a = Number(cur) || 0, b = Number(prev) || 0;
    if (!b) return null;
    return ((a - b) / Math.abs(b)) * 100;
}

/**
 * Export sheets to an .xlsx file.
 * sheets: [{ name, columns: [{ header, key, width, numFmt }], rows: [{...}] }]
 */
export async function exportToExcel(fileName, sheets) {
    const wb = new ExcelJs.Workbook();
    wb.creator = "ALMIR STATISTICS";
    wb.created = new Date();
    sheets.forEach((sheet) => {
        const ws = wb.addWorksheet((sheet.name || "Sheet").slice(0, 31));
        ws.columns = sheet.columns.map((c) => ({ header: c.header, key: c.key, width: c.width || 18 }));
        sheet.rows.forEach((r) => ws.addRow(r));
        const header = ws.getRow(1);
        header.font = { bold: true };
        header.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFE9EEF9" } };
        header.alignment = { vertical: "middle", wrapText: true };
        sheet.columns.forEach((c, i) => {
            if (c.numFmt) ws.getColumn(i + 1).numFmt = c.numFmt;
        });
        ws.views = [{ state: "frozen", ySplit: 1 }];
    });
    const buf = await wb.xlsx.writeBuffer();
    saveAs(new Blob([buf], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" }), `${fileName}.xlsx`);
}

/** Dimension key -> translation key (fallbacks for the labels the API returns) */
export function dimTitle(TR, lang, meta, key) {
    const dim = (meta && meta.dimensions || []).find((d) => d.key === key);
    if (dim && dim.i18n) {
        const t = TR(lang, dim.i18n);
        if (t) return t;
    }
    const fallback = {
        year: "analytics.dim_year", quarter: "analytics.dim_quarter", month: "analytics.dim_month",
        region: "products.region", district: "products.district", distributor: "products.dist",
        party: "analytics.dim_party", manufacturer: "products.mf", country: "products.mfc", drug: "products.med",
        inn: "products.mnn", form: "products.df", farm_group: "products.dfg", ts_group: "products.tpg",
        trademark: "products.td", drug_type: "products.dt", rx_otc: "table.rx_otc",
    };
    return TR(lang, fallback[key] || key) || key;
}

/** Group dimension records for pivot: returns { rowKeys, colKeys, cells, rowTotals, colTotals, grand } */
export function buildPivot(records, rowsDims, colsDims, measures) {
    const rowKeys = new Map(); // key -> { key, labels:[], ids:[] }
    const colKeys = new Map();
    const cells = new Map();   // rowKey|colKey -> { measure: value }
    const rowTotals = new Map();
    const colTotals = new Map();
    const grand = {};
    measures.forEach((m) => (grand[m] = 0));

    const keyOf = (rec, dims) => dims.map((d) => String(rec[`${d}_id`] ?? "")).join("");
    const labelsOf = (rec, dims) => dims.map((d) => rec[`${d}_name`] ?? rec[`${d}_id`] ?? "—");

    records.forEach((rec) => {
        const rk = keyOf(rec, rowsDims);
        const ck = keyOf(rec, colsDims);
        if (!rowKeys.has(rk)) rowKeys.set(rk, { key: rk, labels: labelsOf(rec, rowsDims), ids: rowsDims.map((d) => rec[`${d}_id`]) });
        if (!colKeys.has(ck)) colKeys.set(ck, { key: ck, labels: labelsOf(rec, colsDims), ids: colsDims.map((d) => rec[`${d}_id`]) });
        const id = `${rk}${ck}`;
        const cell = cells.get(id) || {};
        const rt = rowTotals.get(rk) || {};
        const ct = colTotals.get(ck) || {};
        measures.forEach((m) => {
            const v = Number(rec[m]) || 0;
            cell[m] = (cell[m] || 0) + v;
            rt[m] = (rt[m] || 0) + v;
            ct[m] = (ct[m] || 0) + v;
            grand[m] += v;
        });
        cells.set(id, cell);
        rowTotals.set(rk, rt);
        colTotals.set(ck, ct);
    });

    // sort column keys naturally (periods ascending, others by total desc)
    const cols = Array.from(colKeys.values());
    const periodDims = ["year", "quarter", "month"];
    if (colsDims.length && colsDims.every((d) => periodDims.includes(d))) {
        cols.sort((a, b) => a.labels.join().localeCompare(b.labels.join()));
    } else {
        const m0 = measures[0];
        cols.sort((a, b) => ((colTotals.get(b.key) || {})[m0] || 0) - ((colTotals.get(a.key) || {})[m0] || 0));
    }
    const rows = Array.from(rowKeys.values());
    if (rowsDims.length && rowsDims.every((d) => periodDims.includes(d))) {
        rows.sort((a, b) => a.labels.join().localeCompare(b.labels.join()));
    } else {
        const m0 = measures[0];
        rows.sort((a, b) => ((rowTotals.get(b.key) || {})[m0] || 0) - ((rowTotals.get(a.key) || {})[m0] || 0));
    }
    return { rows, cols, cells, rowTotals, colTotals, grand };
}
