import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import { baseURL } from "../../services/AxiosInstance";
import NewsApi from "../../services/cruds/NewsService"
import Loading from "../components/Loading";
import { showToast } from './../../utils/index';
function NewsId() {
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
            showToast('error', err.response.data.message);
        })
    }, [])
    return (
        <>
            {
                loading ? <Loading /> :
                    <div className="row px-3 mt-5">
                        <div className="card coin-card w-100 m-0 p-0 col-xl-12">
                            <div className="card-body d-sm-flex d-block align-items-top p-3">
                                <img
                                    src={`${baseURL}/public/${news.image}-b.png`}
                                    height={"335px"}
                                    width={"450px"}
                                    style={{ borderRadius: "10px", objectFit: 'cover' }}
                                    alt="image"
                                />
                                <div className="px-4">
                                    <h3 className="text-white">{news.title}</h3>
                                    <p>{news.description}</p>
                                    {/* <time className="text-white">
                                        <span>{news.time}</span> {news.date}
                                    </time> */}
                                </div>
                            </div>
                        </div>
                    </div>
            }

        </>
    );
}
export default NewsId;
