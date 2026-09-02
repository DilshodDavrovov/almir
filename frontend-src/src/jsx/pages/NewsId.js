import React, { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { connect } from "react-redux";
import { baseURL } from "../../services/AxiosInstance";
import NewsApi from "../../services/cruds/NewsService"
import Loading from "../components/Loading";
import SafeImage from "../components/SafeImage";
import { showToast } from './../../utils/index';
import { TR } from "../../utils/helpers";
import "./home.css";

function NewsId({ lang }) {
    const { id } = useParams();
    const [news, setNews] = useState({});
    const [loading, setLoading] = useState(false);
    useEffect(() => {
        setLoading(true);
        NewsApi.getById(id).then(res => {
            setNews(res.data.data);
            setLoading(false);
        }).catch(err => {
            setLoading(false);
            showToast('error', err.response && err.response.data ? err.response.data.message : String(err));
        })
    }, [id])
    if (loading) return <Loading />;
    return (
        <div className="hm-article-wrap">
            <Link to="/news" className="hm-back"><i className="fas fa-arrow-left" />{TR(lang, "sidebar.News")}</Link>
            <article className="hm-article">
                <SafeImage src={news.image ? `${baseURL}/public/${news.image}-b.png` : ""} alt={news.title} />
                <div className="hm-article-body">
                    <h1>{news.title}</h1>
                    <p>{news.description}</p>
                </div>
            </article>
        </div>
    );
}
const mapStateToProps = (state) => ({ lang: state.language.lang });
export default connect(mapStateToProps)(NewsId);
