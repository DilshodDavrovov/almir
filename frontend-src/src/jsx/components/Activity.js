import { useState } from 'react';
import { Link } from 'react-router-dom';
import { connect, useDispatch } from 'react-redux';
import { TR } from '../../utils/helpers';
import { buildParams, parseParams } from '../../utils';
import { useHistory } from 'react-router-dom';
import { useEffect } from 'react';
import ActivityApi from "../../services/ActivityService"
import { setAnalyzeActivity } from '../../store/actions/MainAction';
const pages = {
    "dist": "distributors",
    "mf": "manufacturers",
    "sc": "companies",
    "inn": "inn",
    "drugs": "drugs",
    "df": "d-form",
    "dtg": "t-groups",
    "dfg": "d-farm-groups",
    "trademark": "trademark",
}
const translate = {
    "dist": "dist",
    "mf": "mf",
    "sc": "companies",
    "inn": "mnn",
    "drugs": "names",
    "df": "df",
    "dtg": "tpg",
    "dfg": "dfg",
    "trademark": "td",
}
function Activity({activity, lang}){
    const dispatch = useDispatch();
    const history = useHistory();
    const [demoToggle, setDemoToggle] = useState(false);
    const handleClick = (obj) => {
        const page = pages[obj.by_table];
        const params = buildParams(JSON.parse(obj.body));
        history.push({
            pathname: `/analyze/${page}`,
            search: params
        })
    }
    useEffect(() => {
        if(demoToggle){
            ActivityApi.getActivity().then(res => {
                dispatch(setAnalyzeActivity(res.data.data || []));
            }).catch()
        }
    },[demoToggle])
    return ( 
    <div className={`dlab-demo-panel ${demoToggle ? "show" : ""}`}>
        <div className="bg-overlay" onClick={() => setDemoToggle(!demoToggle)}></div>
            <div className="bg-close"  onClick={() => setDemoToggle(!demoToggle)}></div>
            <Link to="#" className="dlab-demo-trigger" onClick={() => setDemoToggle(!demoToggle)}>
                <i className="fa fa-history"></i>
            </Link>
            <div className="dlab-demo-inner">
                <div className='d-flex align-items-baseline justify-content-between'>
                    <h3 className="dlab-demo-header h-100"> {TR(lang, "activity")} </h3>
                    <Link to={"#"} onClick={() => setDemoToggle(!demoToggle)}>
                        <i className="h3 fa fa-times" aria-hidden="true"></i>
                    </Link>
                </div>
                <h6> {TR(lang, "activityText")} </h6>
                <div className='vh-100'>
                    {activity.map((key, index) => {
                        return <div
                            onClick={() => handleClick(key)}
                            className='activity-item btn btn-primary mb-2 d-flex justify-content-between align-items-baseline'
                            key={index}
                            >
                            <div className='d-inline'>
                                <div> {TR(lang,`analyzes.${translate[key.by_table]}`)}</div>
                                <div>{key.name}</div>
                            </div>
                            <i className="fa fa-link float-end"></i>
                        </div>
                    })}
                </div>
            </div>
    </div>
    )
}
const mapStateToProps = (state) => {
    return {
        activity: state.main.analyzeActivity,
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(Activity);