import React, { useState, useEffect } from 'react'
import { Modal } from 'react-bootstrap'
import { TR } from '../../../utils/helpers';
import moment from 'moment';
import SearchService from '../../../services/SearchService';
import PieChart from '../Charts/PieChart';
import DoubleBarChart from '../Charts/DoubleBarChart';
import AreaLineChart from '../Charts/AreaLineChart';
import Loading from '../Loading';
import { showToast } from '../../../utils';
const list = {
    'drug_name': 'drugID',
    'dist': 'distID',
    'sc': 'companyID',
    'mf': 'mfID',
    'df': 'dfID',
    'inn': 'innID',
    'dtg': 'dtgID',
    'dfg': 'dfgID',
    'trademark': 'trademarkID',
    'country': 'countryID',
    'region': 'regionID',
    'district': 'districtID',
}
const colors = ['#6610f2', '#5bcfc5', '#4d06a5', '#dc3545', '#fd7e14', '#ffc107', '#13b497', '#20c997', '#17a2b8', '#94618E', '#343a40', '#2a2a2a', '#6eadf1', '#4cb32b']
const returnObject = {
    "monthly": "totalCommonPerPriceByMonth",
    "quarterly": "totalCommonPerPriceByQuarter",
    "yearly": "totalCommonPerPrice",
}
export default function NewSearchChartModal(props) {
    const {
        API,
        date,
        show,
        setShow,
        lang,
        dataIDList,
        selectedCheckbox,
        dataType
    } = props;

    const [active, setActive] = useState(0);
    const [data, setData] = useState([]);
    const [chartData, setChartData] = useState({ datasets: [], labels: [] });
    const [loading, setLoading] = useState(false);
    const [isCorrectDate, setIsCorrectDate] = useState({
        monthly: false,
        quarterly: false,
        yearly: false
    });
    const [periodType, setPeriodType] = useState("");
    const [chartType, setChartType] = useState("pie");
    const [chartDataType, setChartDataType] = useState("price");
    useEffect(() => {
        let temp = true;
        if (show) {
            if (dataIDList[list[selectedCheckbox]].length > 10) {
                setIsCorrectDate({
                    monthly: false,
                    quarterly: false,
                    yearly: false
                })
                temp = false;
            } else {
                for (let i = 0; i < date.length; i++) {
                    const e = date[i];
                    const result = isFullRange(e.fromDate, e.toDate)
                    temp = result.month;
                    setIsCorrectDate({
                        monthly: result.month,
                        quarterly: result.quarter,
                        yearly: result.year
                    });
                    if (!temp) break;
                }
            }
            if (temp) {
                getList(chartType, periodType)
            }
        } else {
            setActive(0)
            setPeriodType("")
            setChartType("pie")
            setChartDataType("price")
            setIsCorrectDate({
                onthly: false,
                quarterly: false,
                yearly: false
            })
            setChartData({ datasets: [], labels: [] });
        }
    }, [show])
    const handleChangePeriodType = (periodType) => {
        let tempChartType, tempPeriodType;
        if (periodType == "default") {
            tempPeriodType = "";
            tempChartType = "pie";
        } else {
            tempPeriodType = periodType;
            if (periodType === "yearly") {
                tempChartType = "pie"
            } else {
                tempChartType = "line"
            }
        }
        setPeriodType(tempPeriodType)
        setChartType(tempChartType)
        setChartData({ datasets: [], labels: [] })
        getList(tempChartType, tempPeriodType)
    }
    const handleChangeChartType = (chartType) => {
        setChartType(chartType)
        setChartData({ datasets: [], labels: [] })
        convertDataToChartData(data, active, chartType, periodType, chartDataType)
    }
    const handleChangeDataType = (chartDataType) => {
        setChartDataType(chartDataType)
        setChartData({ datasets: [], labels: [] })
        convertDataToChartData(data, active, chartType, periodType, chartDataType)
    }

    const handleChangePeriod = (period) => {
        setActive(period)
        setChartData({ datasets: [], labels: [] })
        convertDataToChartData(data, period, chartType, periodType, chartDataType)
    }



    const getList = (chartType = "pie", periodType) => {
        setLoading(true);
        const temp = {
            is_active: true,
            is_deleted: false,
            dataType,
            is_active: true,
            deleted: false,
            filterByDate: date,
            periodType: periodType || "yearly",
            limit: 10,
            filterBy: selectedCheckbox,
            ...dataIDList,
            sortBy: "USD",
            sortByDesc: true,
        }
        SearchService.graphReport(temp).then(res => {
            setData(res.data.data)
            convertDataToChartData(res.data.data, active, chartType, periodType, chartDataType)
        }).catch(err => {
            showToast('error', err.response.data.message);
        }).finally(() => {
            setLoading(false);
        })
    }
    const convertDataToChartData = (data, period, chartType = "pie", periodType, chartDataType) => {
        try {
            periodType = periodType ? periodType : "yearly"
            const type = chartDataType === "price" ? "sum_price_usd" : "quantity"
            const temp = {}
            if (periodType === "monthly") {
                temp.labels = getMonthsInRange(date[period].fromDate, date[period].toDate)
            } else if (periodType === "quarterly") {
                temp.labels = getQuartersInRange(date[period].fromDate, date[period].toDate)
            } else {
                temp.labels = getYearsInRange(date[period].fromDate, date[period].toDate)
            }
            if (chartType === "line") {
                temp.defaultFontFamily = "Poppins";
                temp.datasets = data.map((element, index) => {
                    return {
                        fill: false,
                        label: element.name,
                        data: element[`period_${period + 1}`][returnObject[periodType]].map(e => e[type]),
                        borderColor: colors[index],
                        pointBackgroundColor: "rgba(0, 0, 1128, .3)",
                    }
                })
            } else if (chartType === "bar") {
                temp.datasets = data.map((element, index) => {
                    return {
                        label: element.name,
                        data: element[`period_${period + 1}`][returnObject[periodType]].map(e => e[type]),
                        backgroundColor: colors[index]
                    }
                })
            } else if (chartType === "pie") {
                temp.datasets = [{
                    data: [],
                    backgroundColor: [],
                }];
                temp.labels = []
                data.forEach((element, index) => {
                    temp.labels.push(`${element.name}: ${element[`period_${period + 1}`][returnObject[periodType]][type]}`)
                    temp.datasets[0].data.push(element[`period_${period + 1}`][returnObject[periodType]][type])
                    temp.datasets[0].backgroundColor.push(colors[index])
                })
            }

            setChartData(temp)
        } catch (error) {
            setChartData({ datasets: [], labels: [] })
        }

    }
    return (
        <Modal
            dialogClassName='modal-dialog-info'
            show={show}
            onHide={setShow}
        >
            <div className="modal-header">
                <h4 className='m-0'>{TR(lang, "chart.chart_title")}</h4>
                <button type="button" className="btn-close" onClick={() => setShow(false)} data-dismiss="modal"><span></span></button>
            </div>

            <div className="modal-body">
                <div className="row">

                    <div className="col-md-9">
                        <div className='d-flex mb-5'>
                            {
                                date.map((key, index) => (
                                    <label key={index} className={`btn ${active === index ? 'bg-success' : 'bg-primary'} text-white ms-2`} onClick={() => handleChangePeriod(index)}>
                                        {key.fromDate} : {key.toDate}
                                    </label>
                                ))
                            }
                        </div>
                        <div style={{ height: "85%", width: "100%" }}>
                            {
                                loading ? <Loading /> :
                                    periodType ?
                                        isCorrectDate[periodType] ?
                                            data && data.length ?
                                                (
                                                    chartType === "line" ? <AreaLineChart data={chartData} /> :
                                                        chartType === "bar" ? <DoubleBarChart data={chartData} /> :
                                                            chartType === "pie" ? <PieChart data={chartData} /> : null
                                                ) : TR(lang, "chart.not_found")
                                            : TR(lang, "chart.invalid_date_message")
                                        : isCorrectDate.monthly ? <PieChart data={chartData} /> : TR(lang, "chart.invalid_date_message")
                            }
                        </div>
                    </div>
                    <div className="col-md-3">
                        <div className="row mb-4">
                            <label className="form-label fw-semibold">{TR(lang, "chart.filter_type")}</label>
                            <select className="form-select" value={periodType} onChange={(e) => handleChangePeriodType(e.target.value)}>
                                <option value="default"></option>
                                <option value="monthly">{TR(lang, "chart.monthly")}</option>
                                <option value="quarterly">{TR(lang, "chart.quarterly")}</option>
                                <option value="yearly">{TR(lang, "chart.yearly")}</option>
                            </select>
                        </div>
                        {
                            periodType ?
                                <div className="row mb-4">
                                    <label className="form-label fw-semibold">{TR(lang, "chart.chart_type")}</label>
                                    <select className="form-select" value={chartType} onChange={(e) => handleChangeChartType(e.target.value)}>
                                        {
                                            periodType === "yearly" ?
                                                null : <option value="line">Line Chart</option>
                                        }
                                        {
                                            periodType === "yearly" ?
                                                null : <option value="bar">Bar Chart</option>
                                        }
                                        {
                                            periodType === "yearly" ?
                                                <option value="pie">Pie Chart</option> : null
                                        }
                                    </select>
                                </div>
                                : null

                        }


                        <div className="row">
                            <label className="form-label fw-semibold">{TR(lang, "chart.data_type")}</label>
                            <select className="form-select" value={chartDataType} onChange={(e) => handleChangeDataType(e.target.value)}>
                                <option value="price">{TR(lang, "chart.price")}</option>
                                <option value="quantity">{TR(lang, "chart.quantity")}</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </Modal>

    )
}
function isFullRange(startDate, endDate) {
    const start = moment(startDate, 'DD-MM-YYYY');
    const end = moment(endDate, 'DD-MM-YYYY');

    const result = {
        success: start.year() === end.year(),
        month: start.date() === 1 && end.date() === end.daysInMonth(),
        quarter: start.isSame(start.clone().startOf('quarter'), 'day') && end.isSame(end.clone().endOf('quarter'), 'day'),
        year:
            start.year() === end.year() && // Ensure same year
            start.isSame(start.clone().startOf('year'), 'day') && // Start of year
            end.isSame(start.clone().endOf('year'), 'day') // End of year
    }
    if (result.success) {
        result.success = result.month || result.quarter || result.year;
    }

    return result
}

function getMonthsInRange(startDate, endDate) {
    let start = moment(startDate, 'DD-MM-YYYY').startOf('month');
    let end = moment(endDate, 'DD-MM-YYYY').endOf('month');
    let months = [];

    while (start.isBefore(end) || start.isSame(end, 'month')) {
        months.push(start.format('MMMM')); // Full month name
        start.add(1, 'month');
    }

    return months;
}

function getQuartersInRange(startDate, endDate) {
    let start = moment(startDate, 'DD-MM-YYYY').startOf('quarter');
    let end = moment(endDate, 'DD-MM-YYYY').endOf('quarter');
    let quarters = [];

    while (start.isBefore(end) || start.isSame(end, 'quarter')) {
        quarters.push(`Q${start.quarter()} ${start.year()}`);
        start.add(1, 'quarter');
    }

    return quarters;
}

function getYearsInRange(startDate, endDate) {
    let start = moment(startDate, 'DD-MM-YYYY').startOf('year');
    let end = moment(endDate, 'DD-MM-YYYY').endOf('year');
    let years = [];

    while (start.isBefore(end) || start.isSame(end, 'year')) {
        years.push(start.year());
        start.add(1, 'year');
    }

    return years;
}