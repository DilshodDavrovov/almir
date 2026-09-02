import React, { useState, useEffect } from "react";
import API from "../../services/HomeService";
import { connect } from "react-redux";
import Tops from "./../components/Tops";
import { NumberToStr } from "../../utils";
import CountCard from './../components/CountCard';
import { TR } from './../../utils/helpers';
import Loading from './../components/Loading/index';
import HomeModal from "../components/HomeModal";
import { getThisYear } from './../../utils/index';
import UserNews from "../components/UserNews";
import "./home.css";

/** Counter tiles: label key, icon, colour tone and the comparative-analysis page they open. */
const CARDS = [
    { key: "drug", keyCount: "totalDrugs", title: "table.topDrugs", titleCount: "products.med", icon: "fa-pills", tone: "indigo", to: "/analyze/drugs" },
    { key: "df", keyCount: "totalDrugForm", title: "table.topDf", titleCount: "products.df", icon: "fa-capsules", tone: "teal", to: "/analyze/d-form" },
    { key: "dist", keyCount: "totalDistributor", title: "table.topDist", titleCount: "products.dist", icon: "fa-truck", tone: "amber", to: "/analyze/distributors" },
    { key: "mf", keyCount: "totalManufacturer", title: "table.topMf", titleCount: "products.mf", icon: "fa-industry", tone: "rose", to: "/analyze/manufacturers" },
    { key: "sc", keyCount: "totalCompany", title: "table.topComp", titleCount: "products.senders", icon: "fa-building", tone: "violet", to: "/analyze/companies" },
    { key: "dtg", keyCount: "totalDTG", title: "table.topTG", titleCount: "products.dfg", icon: "fa-layer-group", tone: "emerald", to: "/analyze/t-groups" },
    { key: "dfg", keyCount: "totalDFG", title: "table.topFG", titleCount: "products.tpg", icon: "fa-flask", tone: "sky", to: "/analyze/d-farm-groups" },
    { key: "trademark", keyCount: "totalTrademark", title: "table.topTd", titleCount: "products.td", icon: "fa-tag", tone: "orange", to: "/analyze/trademark" },
    { key: "inn", keyCount: "totalInn", title: "table.topMnn", titleCount: "products.mnn", icon: "fa-dna", tone: "slate", to: "/analyze/inn" },
];

const Home = ({ lang, lastUpdateDate }) => {
    const list_arr = ["dist", "mf", "sc", "inn", "drug", "df", "dtg", "dfg", "trademark"];
    const [show, setShow] = useState(false);
    const [date, setDate] = useState(getThisYear(lastUpdateDate));
    const [data, setData] = useState({});
    const [total, setTotal] = useState({});
    const [loading, setLoading] = useState(true);
    const [first, setFirst] = useState(true);
    const [names, setNames] = useState(CARDS.map((c) => ({ ...c, count: 0 })));

    const filter = async (date, total) => {
        setLoading(true);
        const obj = {};
        try {
            const temp = await API.getTopsList(list_arr, date.fromDate, date.toDate);
            const data = temp.data.data;
            names.forEach(({ key, title }) => {
                obj[key] = {
                    data: data[key] ? data[key].map(key => {
                        const per = total && total.USD ? (key.USD / total.USD) * 100 : 0;
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
            setLoading(false);
        }
    };
    const getTotal = async (date) => {
        const res = await API.getTotal(date.fromDate, date.toDate);
        const temp = res.data.data[0];
        if (temp) {
            setTotal(temp.period_1)
        }
        return temp ? temp.period_1 : {};
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
            setNames(names.map((key) => ({ ...key, count: count[key.keyCount] })));
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
        <div className="hm">
            <div className="hm-head">
                <div>
                    <h2>{TR(lang, 'sidebar.Home')}</h2>
                    <div className="hm-sub">
                        <span>{TR(lang, 'home.period')}: <b>{date.fromDate} — {date.toDate}</b></span>
                        {lastUpdateDate ? <><span className="dot">•</span><span>{TR(lang, 'content.inputOfLastUpdate')}: <b>{lastUpdateDate}</b></span></> : null}
                    </div>
                </div>
                <button type="button" className="btn btn-outline-primary" onClick={() => setShow(true)}>
                    <i className="fas fa-calendar-alt me-2" aria-hidden="true" />{TR(lang, 'home.refresh')}
                </button>
            </div>

            <div className="hm-grid">
                <div className="hm-hero">
                    <div>
                        <div className="lbl"><i className="fas fa-chart-line" aria-hidden="true" />{TR(lang, 'table.mainTurnOver')}</div>
                        <div className="val"><small>USD</small>{NumberToStr(total.USD)}</div>
                        <div className="per">{TR(lang, 'home.period')}: <b>{date.fromDate} — {date.toDate}</b></div>
                    </div>
                    <button type="button" className="btn" onClick={() => setShow(true)}>
                        <i className="fas fa-sync-alt me-2" aria-hidden="true" />{TR(lang, 'home.refresh')}
                    </button>
                </div>
                <div className="hm-counts">
                    {names.map(({ key, titleCount, count, icon, tone, to }) => (
                        <CountCard key={key} title={TR(lang, titleCount)} count={count} icon={icon} tone={tone} to={to} />
                    ))}
                </div>
            </div>

            <div className="hm-section">
                <h3>{TR(lang, 'home.tops')}</h3>
                <span className="hint">{TR(lang, 'home.period')}: {date.fromDate} — {date.toDate}</span>
            </div>
            <div className="hm-tops">
                {Object.keys(data).map((key) => (
                    <Tops key={key} arr={data[key].data} title={TR(lang, data[key].title)} />
                ))}
            </div>

            <UserNews />

            <HomeModal
                date={date}
                show={show}
                setShow={setShow}
                handleChartSubmit={handleChartSubmit}
            />
        </div>
    );
};

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        lastUpdateDate: state.main.lastUpdateDate
    };
};

export default connect(mapStateToProps)(Home);
