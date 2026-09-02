import { useEffect, useState } from "react";
import { connect } from "react-redux";
import NewsApi from "../../services/cruds/NewsService"
import { TR } from "../../utils/helpers";
import { baseURL } from "../../services/AxiosInstance";
import { Link } from "react-router-dom";
function UserNews({lang}) {
    const [news, setNews] = useState([]);
    useEffect(async () => {
        const resNews = await NewsApi.getForHome();
        setNews([...resNews.data.data])
    }, [])
    return (
        <div className="col-xl-12">
            <div className="card">
                <h2 className="card-title p-4 pb-0">{TR(lang, 'home.news')}</h2>
                <div className="card-body p-3 pb-0">
                    <div className="row p-2">
                        {news.map((elem, i) => (
                            <div className="col-xl-6 p-2" key={i}>
                                <Link to={`/news/${elem.id}`}>
                                    <div className="card coin-card w-100 m-0">
                                        <div className="card-body d-sm-flex d-block align-items-center p-3">
                                            <img
                                                src={`${baseURL}/public/${elem.image}-b.png`}
                                                height={"199px"}
                                                width={"250px"}
                                                style={{ borderRadius: "10px", marginRight: "" }}
                                                alt="image"
                                            />
                                            <div className="ps-3">
                                                <h3
                                                    className="text-white"
                                                    style={{ fontSize: "23px" }}
                                                >
                                                    {elem.title}
                                                </h3>
                                                <p>{elem.description}</p>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    )
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
    };
};
export default connect(mapStateToProps)(UserNews);
