import React from "react";
import { fmt, pct } from "../../../utils/analytics";

/**
 * Compact ranking table with share bars.
 * rows: [{id, name, usd, uzs, eur, rub, qty, share_usd, share_qty}]
 * props: metric ('sum'|'qty'), currency, labels {name, sum, qty, share}, onRowClick, total, maxHeight, activeId
 */
export default function DetailsTable({ rows, metric, currency, labels, onRowClick, total, maxHeight = 420, activeId, showRank = true }) {
    const key = metric === "qty" ? "qty" : currency;
    const shareKey = metric === "qty" ? "share_qty" : "share_usd";
    const maxShare = Math.max(1, ...rows.map((r) => Number(r[shareKey]) || 0));
    return (
        <div className="an-scroll" style={{ maxHeight }}>
            <table className="an-table">
                <thead>
                    <tr>
                        <th>{labels.name}</th>
                        <th className="num">{labels.sum} {metric === "qty" ? "" : currency.toUpperCase()}</th>
                        <th className="num">{labels.qty}</th>
                        <th className="num" style={{ width: 130 }}>{labels.share}</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map((r, i) => (
                        <tr key={`${r.id}-${i}`} className={`${onRowClick ? "clickable" : ""} ${activeId === r.id ? "table-active" : ""}`} onClick={() => onRowClick && onRowClick(r)}>
                            <td>{showRank ? <span className="an-rank">{i + 1}</span> : null}{r.name}</td>
                            <td className="num">{fmt(r[currency], 2)}</td>
                            <td className="num">{fmt(r.qty, 0)}</td>
                            <td className="num">
                                {pct(r[shareKey])}
                                <span className="bar"><i style={{ width: `${Math.min(100, (Number(r[shareKey]) || 0) / maxShare * 100)}%` }} /></span>
                            </td>
                        </tr>
                    ))}
                    {total ? (
                        <tr className="total">
                            <td>{labels.total}</td>
                            <td className="num">{fmt(total[currency], 2)}</td>
                            <td className="num">{fmt(total.qty, 0)}</td>
                            <td className="num">100%</td>
                        </tr>
                    ) : null}
                </tbody>
            </table>
        </div>
    );
}
