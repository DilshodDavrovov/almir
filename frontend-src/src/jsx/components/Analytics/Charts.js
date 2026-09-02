import React from "react";
import Highcharts from "highcharts/highmaps";
import HighchartsReact from "highcharts-react-official";
import { CHART_COLORS, compactNumber, fmt, ymLabel } from "../../../utils/analytics";

Highcharts.setOptions({
    lang: { thousandsSep: " ", decimalPoint: "." },
    colors: CHART_COLORS,
    chart: { style: { fontFamily: "inherit" }, backgroundColor: "transparent" },
    credits: { enabled: false },
    title: { text: null },
});

const baseTooltip = (unit) => ({
    shared: true,
    useHTML: true,
    borderRadius: 10,
    formatter: function () {
        const pts = this.points || [this.point ? this : null].filter(Boolean);
        const head = `<div style="font-weight:600;margin-bottom:4px">${this.x !== undefined ? this.x : (this.key || "")}</div>`;
        const rows = pts.map((p) => `<div><span style="color:${p.color}">●</span> ${p.series.name}: <b>${fmt(p.y, 2)}</b>${unit ? " " + unit : ""}</div>`).join("");
        return head + rows;
    },
});

/** Monthly dynamics: one series per period (aligned by month index). */
export function DynamicsChart({ periods, metric, currency, lang, type = "column", height = 320 }) {
    const unit = metric === "qty" ? "" : currency.toUpperCase();
    const maxLen = Math.max(0, ...periods.map((p) => p.months.length));
    const categories = periods.length === 1
        ? periods[0].months.map((m) => ymLabel(m.label, lang))
        : Array.from({ length: maxLen }, (_, i) => periods.map((p) => (p.months[i] ? ymLabel(p.months[i].label, lang) : "")).filter(Boolean).join(" / "));
    const series = periods.map((p, i) => ({
        name: periods.length > 1 ? `${i + 1}: ${ymLabel(p.from, lang)} – ${ymLabel(p.to, lang)}` : (metric === "qty" ? "Qty" : unit),
        type,
        data: (periods.length === 1 ? p.months : Array.from({ length: maxLen }, (_, k) => p.months[k])).map((m) => (m ? Number(metric === "qty" ? m.qty : m[currency]) : null)),
        color: CHART_COLORS[i],
        marker: { enabled: type !== "column", radius: 3 },
    }));
    const options = {
        chart: { type, height, spacing: [10, 10, 10, 10] },
        xAxis: { categories, crosshair: true, labels: { style: { fontSize: "11px" } } },
        yAxis: { title: { text: null }, labels: { formatter: function () { return compactNumber(this.value, lang); } }, gridLineColor: "#f0f2f7" },
        legend: { enabled: periods.length > 1, align: "left", verticalAlign: "top", itemStyle: { fontWeight: 500, fontSize: "12px" } },
        tooltip: baseTooltip(unit),
        plotOptions: {
            column: { borderRadius: 4, groupPadding: 0.12, pointPadding: 0.05, borderWidth: 0 },
            series: { animation: { duration: 300 } },
        },
        series,
    };
    return <HighchartsReact highcharts={Highcharts} options={options} />;
}

/** Top-N horizontal bars or pie. rows: [{id,name,usd,qty,share_usd,share_qty}] */
export function TopChart({ rows, metric, currency, lang, type = "bar", height = 360, onClick }) {
    const key = metric === "qty" ? "qty" : currency;
    const unit = metric === "qty" ? "" : currency.toUpperCase();
    const data = rows.map((r, i) => ({ name: r.name, y: Number(r[key]) || 0, id: r.id, color: CHART_COLORS[i % CHART_COLORS.length], share: metric === "qty" ? r.share_qty : r.share_usd }));
    const options = type === "pie"
        ? {
            chart: { type: "pie", height },
            tooltip: { pointFormat: `<b>{point.y:,.2f}</b> ${unit} ({point.percentage:.1f}%)` },
            plotOptions: { pie: { innerSize: "55%", dataLabels: { enabled: true, format: "{point.name}: {point.percentage:.1f}%", style: { fontSize: "11px", fontWeight: 500 } }, point: { events: { click: function () { onClick && onClick(this.options); } } } } },
            series: [{ name: unit || "Qty", data }],
        }
        : {
            chart: { type: "bar", height: Math.max(height, 34 * data.length + 60) },
            xAxis: { type: "category", labels: { style: { fontSize: "12px" } } },
            yAxis: { title: { text: null }, labels: { formatter: function () { return compactNumber(this.value, lang); } }, gridLineColor: "#f0f2f7" },
            legend: { enabled: false },
            tooltip: { useHTML: true, pointFormatter: function () { return `<b>${fmt(this.y, 2)}</b> ${unit}<br/><span style="color:#888">${this.share != null ? this.share + "%" : ""}</span>`; } },
            plotOptions: { bar: { borderRadius: 4, borderWidth: 0, pointPadding: 0.08, groupPadding: 0.05, dataLabels: { enabled: true, formatter: function () { return compactNumber(this.y, lang); }, style: { fontSize: "11px", fontWeight: 500, textOutline: "none" } }, point: { events: { click: function () { onClick && onClick(this.options); } } }, cursor: onClick ? "pointer" : "default" } },
            series: [{ name: unit || "Qty", data, colorByPoint: true }],
        };
    return <HighchartsReact highcharts={Highcharts} options={options} />;
}

export { Highcharts, HighchartsReact };
