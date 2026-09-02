import React, { useMemo } from "react";
import mapDataUzbekistan from "@highcharts/map-collection/countries/uz/uz-all.geo.json";
import { Highcharts, HighchartsReact } from "./Charts";
import { compactNumber, fmt } from "../../../utils/analytics";

/** hc-key of the Highcharts map <-> SOATO code stored in regions.soato_id */
export const HC_KEY_BY_SOATO = {
    1730: "uz-fa", 1726: "uz-tk", 1703: "uz-an", 1714: "uz-ng", 1708: "uz-ji", 1724: "uz-si", 1727: "uz-ta",
    1706: "uz-bu", 1733: "uz-kh", 1735: "uz-qr", 1712: "uz-nw", 1718: "uz-sa", 1710: "uz-qa", 1722: "uz-su",
};

/**
 * Choropleth of Uzbekistan regions.
 * rows: [{id, name, soato_id, usd, qty, share_usd, share_qty}] ; metric 'sum'|'qty' ; currency 'usd'|...
 */
export default function GeoMap({ rows, metric, currency, lang, selectedId, onSelect, height = 460 }) {
    const key = metric === "qty" ? "qty" : currency;
    const unit = metric === "qty" ? "" : currency.toUpperCase();

    const data = useMemo(() => rows
        .filter((r) => r.soato_id && HC_KEY_BY_SOATO[r.soato_id])
        .map((r) => ({
            "hc-key": HC_KEY_BY_SOATO[r.soato_id],
            value: Number(r[key]) || 0,
            name: r.name,
            id: r.id,
            share: metric === "qty" ? r.share_qty : r.share_usd,
            borderColor: r.id === selectedId ? "#1f2d50" : "#ffffff",
            borderWidth: r.id === selectedId ? 2.5 : 0.8,
        })), [rows, key, selectedId, metric]);

    const options = {
        chart: { map: mapDataUzbekistan, height, spacing: [0, 0, 0, 0] },
        mapNavigation: { enabled: true, enableMouseWheelZoom: false, buttonOptions: { verticalAlign: "bottom" } },
        legend: { enabled: true, layout: "horizontal", align: "center", verticalAlign: "bottom", symbolWidth: 260, itemStyle: { fontSize: "11px" } },
        colorAxis: {
            min: 0,
            minColor: "#e4ecff",
            maxColor: "#2f4bd6",
            labels: { formatter: function () { return compactNumber(this.value, lang); } },
        },
        tooltip: {
            useHTML: true,
            pointFormatter: function () {
                return `<div style="font-weight:600">${this.name}</div><div>${fmt(this.value, 2)} ${unit}</div><div style="color:#888">${this.share != null ? this.share + "%" : ""}</div>`;
            },
            headerFormat: "",
        },
        series: [{
            data,
            joinBy: "hc-key",
            allAreas: true,
            nullColor: "#f3f4f6",
            borderColor: "#ffffff",
            borderWidth: 0.8,
            states: { hover: { brightness: 0.1, borderColor: "#1f2d50" }, select: { color: undefined } },
            dataLabels: { enabled: true, format: "{point.name}", style: { fontSize: "10px", fontWeight: 500, textOutline: "2px #ffffffcc", color: "#1f2d50" } },
            point: { events: { click: function () { onSelect && onSelect(this.options.id === selectedId ? null : this.options.id); } } },
            cursor: "pointer",
        }],
    };
    return <HighchartsReact highcharts={Highcharts} options={options} constructorType="mapChart" />;
}
