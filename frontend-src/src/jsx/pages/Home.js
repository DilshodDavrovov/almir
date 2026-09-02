import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import API from "../../services/HomeService";
import { connect } from "react-redux";
import pattern6 from "./../../images/pattern6.png";
import Tops from "./../components/Tops";
import { NumberToStr } from "../../utils";
import CountCard from './../components/CountCard';
import { TR } from './../../utils/helpers';
import Loading from './../components/Loading/index';
import HomeModal from "../components/HomeModal";
import { getThisYear } from './../../utils/index';
import UserNews from "../components/UserNews";

const Home = ({ lang, lastUpdateDate }) => {
    const list_arr = ["dist", "mf", "sc", "inn", "drug", "df", "dtg", "dfg", "trademark"];
    const [show, setShow] = useState(false);
    const [date, setDate] = useState(getThisYear(lastUpdateDate));
    const [data, setData] = useState({});
    const [total, setTotal] = useState({});
    const [loading, setLoading] = useState(true);
    const [first, setFirst] = useState(true);
    const [names, setNames] = useState([
        {
            key: "drug",
            keyCount: "totalDrugs",
            title: "table.topDrugs",
            titleCount: "products.med",
            bg: "#E4C694",
            count: 0
        },
        {
            key: "df",
            keyCount: "totalDrugForm",
            title: "table.topDf",
            titleCount: "products.df",
            bg: "#9CE4AB",
            count: 0
        },
        {
            key: "dist",
            keyCount: "totalDistributor",
            title: "table.topDist",
            titleCount: "products.dist",
            bg: "#A6B5E8",
            count: 0
        },
        {
            key: "mf",
            keyCount: "totalManufacturer",
            title: "table.topMf",
            titleCount: "products.mf",
            bg: "#82BBDD",
            count: 0
        },
        {
            key: "sc",
            keyCount: "totalCompany",
            title: "table.topComp",
            titleCount: "products.senders",
            bg: "#A1C6E1",
            count: 0
        },
        {
            key: "dtg",
            keyCount: "totalDTG",
            title: "table.topTG",
            titleCount: "products.dfg",
            bg: "#D1E2F2",
            count: 0
        },
        {
            key: "dfg",
            keyCount: "totalDFG",
            title: "table.topFG",
            titleCount: "products.tpg",
            bg: "#84A9D1",
            count: 0
        },
        {
            key: "trademark",
            keyCount: "totalTrademark",
            title: "table.topTd",
            titleCount: "products.td",
            bg: "#6EADF1",
            count: 0
        },
        {
            key: "inn",
            keyCount: "totalInn",
            title: "table.topMnn",
            titleCount: "products.mnn",
            bg: "#5F6A75",
            count: 0
        },
    ])

    const filter = async (date, total) => {
        setLoading(true);
        const obj = {};
        try {
            const temp = await API.getTopsList(list_arr, date.fromDate, date.toDate);
            const data = temp.data.data;
            names.map(async ({ key, title }) => {
                obj[key] = {
                    data: data[key] ? data[key].map(key => {
                        const per = (key.USD / total.USD) * 100;
                        return {
                            ...key,
                            perc: per.toFixed(2)
                        }
                    }) : [],
                    title,
                    key
                }
            });
            setData({ ...obj });
            setLoading(false);
        } catch (error) {

        }
    };
    const getTotal = async (date) => {
        const res = await API.getTotal(date.fromDate, date.toDate);
        const temp = res.data.data[0];
        if (temp) {
            setTotal(temp.period_1)
        }
        return temp.period_1;
    }
    const handleChartSubmit = async (date) => {
        setDate(date);
        setShow(false);
    };


    useEffect(async () => {
        setLoading(true);
        if (first) {
            const resCount = await API.getCount();
            const count = resCount.data.data;
            const tempArr = [];
            names.map(key => {
                tempArr.push({
                    ...key,
                    count: count[key.keyCount]
                })
            })
            setNames(tempArr);
        }
        const total = await getTotal(date);
        await filter(date, total);
        setFirst(false)
    }, [date])
    if (loading) {
        return <div className="mt-5">
            <Loading />
        </div>
    }
    return (
        <>
            <div className="row mt-5">
                <div className="col-xl-10">
                    <div className="card" style={{ maxHeight: "fit-content" }}>
                        <div className="card-body p-3">
                            <div className="row align-items-center">
                                <div className="col-xl-6">
                                    <div className="card-bx bg-blue">
                                        <img className="pattern-img" src={pattern6} alt="HAAA" />
                                        <div className="card-info text-white">
                                            {/* <img src={circle} className="mb-4" alt="" /> */}
                                            <h2 className="text-white card-balance">${NumberToStr(total.USD)}</h2>
                                            <p className="fs-16">{TR(lang, 'home.period')} {`${date.fromDate} : ${date.toDate}`}</p>
                                            {/* <p className="fs-16">{TR(lang, 'content.inputOfLastUpdate')}: {lastUpdateDate}</p> */}
                                        </div>
                                        <Link
                                            to={"#"}
                                            onClick={() => setShow(true)}
                                            className="change-btn"
                                            id="change-btn"
                                            style={{ fontSize: "14px" }}
                                        >
                                            <i className="fa fa-caret-up up-ico"></i>
                                            {TR(lang, 'home.refresh')}
                                            <span className="reload-icon">
                                                <i className="fa fa-refresh reload active"></i>
                                            </span>
                                        </Link>
                                    </div>
                                </div>
                                {
                                    Object.keys(data).map((key, index) => {
                                        return <Tops
                                            key={index}
                                            arr={data[key].data}
                                            title={TR(lang, data[key].title)}
                                        />
                                    })
                                }
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-xl-2">
                    <div className="row invoice-card-row">
                        {
                            names.map(({ titleCount, count, bg }, index) => {
                                return <CountCard
                                    key={index}
                                    title={TR(lang, titleCount)}
                                    count={count}
                                    bg={bg}
                                />
                            })
                        }
                    </div>
                </div>
            </div>

            <UserNews />

            <HomeModal
                date={date}
                show={show}
                setShow={setShow}
                handleChartSubmit={handleChartSubmit}
            />
        </>
    );
};

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        lastUpdateDate: state.main.lastUpdateDate
    };
};

export default connect(mapStateToProps)(Home);