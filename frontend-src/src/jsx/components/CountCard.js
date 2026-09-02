
function CountCard(props) {
    const {title, count, bg} = props;
    return <div className={`card invoice-card p-3`} style={{backgroundColor: bg}}>
        <span className="text-white fs-18">{title}</span>
        <h2 className="text-white invoice-num">{count}</h2>
    </div>
}

export default CountCard;