import { useEffect, useState } from "react";
import { connect } from "react-redux";
import NewsApi from "../../services/cruds/NewsService"
import { TR } from "../../utils/helpers";
import { baseURL } from "../../services/AxiosInstance";
import { Link } from "react-router-dom";
import SafeImage from "./SafeImage";

function UserNews({ lang }) {
    const [news, setNews] = useState([]);
    useEffect(() => {
        NewsApi.getForHome().then((res) => setNews([...(res.data.data || [])])).catch(() => setNews([]));
    }, [])
    return (
        <div className="hm-news">
            <div className="hm-section">
                <h3>{TR(lang, 'home.news')}</h3>
                <Link to="/news" className="hint">{TR(lang, 'sidebar.News')} →</Link>
            </div>
            {news.length ? (
                <div className="hm-news-grid">
                    {news.map((elem, i) => (
                        <Link to={`/news/${elem.id}`} className="hm-news-card" key={elem.id || i}>
                            <SafeImage src={elem.image ? `${baseURL}/public/${elem.image}-b.png` : ""} alt={elem.title} />
                            <div className="hm-news-body">
                                <h4 className="hm-news-title">{elem.title}</h4>
                                <p className="hm-news-text">{elem.description}</p>
                                <span className="hm-news-more">{TR(lang, 'content.more')} <i className="fas fa-arrow-right" /></span>
                            </div>
                        </Link>
                    ))}
                </div>
            ) : <div className="hm-news-empty">—</div>}
        </div>
    )
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
    };
};
export default connect(mapStateToProps)(UserNews);
