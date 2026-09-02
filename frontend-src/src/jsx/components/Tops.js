import TopsChart, { TOP_COLORS } from './TopsChart';

/** "Top-5 …" tile: donut + ranked list with shares. */
function Tops({ arr, title }) {
    return (
        <div className="hm-top">
            <h4>{title}</h4>
            {arr && arr.length ? (
                <div className="hm-top-body">
                    <div className="hm-donut"><TopsChart arr={arr} /></div>
                    <ul className="hm-top-list">
                        {arr.map((key, index) => (
                            <li key={index} title={key.name}>
                                <span className="sw" style={{ background: TOP_COLORS[index % TOP_COLORS.length] }} />
                                <span className="nm">{key.name}</span>
                                <span className="pc">{key.perc} %</span>
                            </li>
                        ))}
                    </ul>
                </div>
            ) : <div className="hm-top-empty">—</div>}
        </div>
    );
}
export default Tops;
