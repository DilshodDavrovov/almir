import { Link } from "react-router-dom";

const fmt = (n) => (n === null || n === undefined || n === "" ? "—" : Number(n).toLocaleString("ru-RU"));

/** Dashboard counter tile: icon, value, label. Links to the matching comparative analysis. */
function CountCard({ title, count, icon, tone, to }) {
    const body = (
        <>
            <span className="ic"><i className={`fas ${icon}`} aria-hidden="true" /></span>
            <span className="v">{fmt(count)}</span>
            <span className="t">{title}</span>
        </>
    );
    return to
        ? <Link to={to} className={`hm-count tone-${tone}`}>{body}</Link>
        : <div className={`hm-count tone-${tone}`}>{body}</div>;
}

export default CountCard;
