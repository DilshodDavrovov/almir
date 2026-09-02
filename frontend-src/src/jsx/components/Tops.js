import TopsChart from './TopsChart';

function Tops(props) {
    const {arr, title} = props;
    return (
        <div className="col-md-6 row ps-5 mt-4">
            <div className="col-md-8">
                <h4 className="fw-bold black-font">
                    {title}
                </h4>
                <div className="mt-4">
                    {
                        arr.map((key, index) => {
                            return <div className='d-flex justify-content-between' key = {index}>
                                <span
                                    title={key.name}
                                    className='home-cut-text black-font'
                                >
                                    {key.name}
                                </span>
                                <span className="black-font text-nowrap">{key.perc} %</span>
                            </div>
                        })
                    }
                </div>
            </div>
            <div className="col-md-4">
                <TopsChart
                    arr = {arr}
                />
            </div>
        </div>
    );
}
export default Tops;
