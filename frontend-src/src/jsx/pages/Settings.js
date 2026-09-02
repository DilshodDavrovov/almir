import { useEffect } from "react";
import { Card, Form, Row } from "react-bootstrap";
import SettingApi from '../../services/SettingService'
import { useState } from "react";
import { TR } from "../../utils/helpers";
import { connect } from "react-redux";
import { baseURL } from "../../services/AxiosInstance";
import { showToast } from "../../utils";
import Loading from "../components/Loading";
function Setting(props) {
    const {lang} = props;
    const [loading, setLoading] = useState(false);
    const [data, setData] = useState({
        app_name:"",
        app_version: "",
        support_email:"",
        description:"",
        contact_phone:"",
        contact_email: "",
        contact_fax:"",
        contact_address:"",
        referent_cost_file_link: "",
        reg_cost_glc_file_link: "",
        customer_cost_file_link: "",
        referent_cost_file: null,
        reg_cost_glc_file: null,
        customer_cost_file: null
    });
    function editSetting(e){
        e.preventDefault();
        setLoading(true);
        const formData = new FormData();
        const temp = _.omit(data, ['referent_cost_file_link', 'reg_cost_glc_file_link', 'customer_cost_file_link']);
        Object.keys(temp).map(key => {
            if(temp[key] !== null){
                formData.append(key, temp[key]);
            }
        });
        SettingApi.setSetting(formData).then(res => {
            const temp = res.data.data;
            setData({
                app_name: temp.app_name,
                app_version: temp.app_version,
                support_email: temp.support_email,
                description: temp.description,
                contact_phone: temp.contact_phone,
                contact_email: temp.contact_email,
                contact_fax: temp.contact_fax,
                contact_address: temp.contact_address,
                referent_cost_file_link: temp.referent_cost_file,
                reg_cost_glc_file_link: temp.reg_cost_glc_file,
                customer_cost_file_link: temp.customer_cost_file,
            });
            setLoading(false);
            showToast('success', res.data.message);
        }).catch(err =>{
            showToast('error', err.response.data.message);
        })
    }
    
    useEffect(() => {
        SettingApi.getSetting().then(res => {
            const temp = res.data.data;
            setData({
                app_name: temp.app_name,
                app_version: temp.app_version,
                support_email: temp.support_email,
                description: temp.description,
                contact_phone: temp.contact_phone,
                contact_email: temp.contact_email,
                contact_fax: temp.contact_fax,
                contact_address: temp.contact_address,
                referent_cost_file_link: temp.referent_cost_file,
                reg_cost_glc_file_link: temp.reg_cost_glc_file,
                customer_cost_file_link: temp.customer_cost_file,
                referent_cost_file: null,
                reg_cost_glc_file: null,
                customer_cost_file: null,
            })
        }).catch()
    }, [])
    return (
        <Card>
            <Card.Header>
                <h3 className={"text-center"}>{TR(lang, "content.settingsEditing")}</h3>
            </Card.Header>
            <Card.Body>
                <Form onSubmit={e => editSetting(e)}>
                    <Row>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "reg.name")}</label>
                            <input 
                                className='form-control'
                                type = 'text' 
                                placeholder={TR(lang, "reg.name")}
                                onChange={e => setData({...data, app_name: e.target.value})} 
                                value={data.app_name} 
                                required   
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "auth.email")}</label>
                            <input 
                                className='form-control'
                                type = 'text' 
                                placeholder={TR(lang, "auth.email")}
                                onChange={e => setData({...data, support_email: e.target.value})} 
                                value={data.support_email} 
                                required   
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "table.text")}</label>
                            <input 
                                className='form-control'
                                type = 'text' 
                                placeholder={TR(lang, "table.text")}
                                onChange={e => setData({...data, description: e.target.value})} 
                                value={data.description} 
                                required   
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "reg.phone")}</label>
                            <input 
                                className='form-control'
                                type = 'text' 
                                placeholder={TR(lang, "reg.phone")}
                                onChange={e => setData({...data, contact_phone: e.target.value})} 
                                value={data.contact_phone} 
                                required   
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "auth.email")}</label>
                            <input 
                                className='form-control'
                                type = 'text' 
                                placeholder={TR(lang, "auth.email")}
                                onChange={e => setData({...data, contact_email: e.target.value})} 
                                value={data.contact_email} 
                                required   
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "reg.fax")}</label>
                            <input 
                                className='form-control'
                                type = 'text' 
                                placeholder={TR(lang, "reg.fax")}
                                onChange={e => setData({...data, contact_fax: e.target.value})} 
                                value={data.contact_fax} 
                                required   
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "reg.address")}</label>
                            <input 
                                className='form-control'
                                type = 'text' 
                                placeholder={TR(lang, "reg.address")}
                                onChange={e => setData({...data, contact_address: e.target.value})} 
                                value={data.contact_address} 
                                required   
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "reg.appVer")}</label>
                            <input 
                                className='form-control'
                                type = 'text' 
                                placeholder={TR(lang, "reg.appVer")}
                                onChange={e => setData({...data, app_version: e.target.value})} 
                                value={data.app_version} 
                                required   
                            />
                        </div>
                    </Row>
                    
                    <div className="row mt-2">
                        <div className="mb-3 col-md-6">
                            <label className="form-label">{TR(lang, "content.uploadReferentPrices")}</label>
                            <input
                                type="file" 
                                src={`${baseURL}/public${data.referent_cost_file_link}`}
                                className="form-control" 
                                accept=".xlsx, .xls, .pdf, .docx"
                                onChange={e => {
                                    setData({...data, referent_cost_file: e.target.files[0]})
                                }}
                            />
                        </div>
                        <div className="mb-3 col-md-6">
                            <label className="form-label">{TR(lang, "content.uploadRegisteredGls")}</label>
                            <input 
                                type="file" 
                                src={`${baseURL}/public${data.reg_cost_glc_file_link}`}
                                className="form-control" 
                                accept=".xlsx, .xls, .pdf, .docx"
                                onChange={e => {
                                    setData({...data, reg_cost_glc_file: e.target.files[0]})
                                }}
                            />
                        </div>
                        <div className="mb-3 col-md-6">
                            <label className="form-label">{TR(lang, "content.uploadValueAddedTax")}</label>
                            <input 
                                type="file" 
                                src={`${baseURL}/public${data.customer_cost_file_link}`}
                                className="form-control" 
                                accept=".xlsx, .xls, .pdf, .docx"
                                onChange={e => {
                                    setData({...data, customer_cost_file: e.target.files[0]})
                                }}
                            />
                        </div>
                    </div>
                    <div className='d-flex my-2 float-end'>
                        <button type='submit' className = "ms-3 btn btn-primary">{TR(lang, "content.save")} {loading ? <i className='fa fa-spinner fa-spin'></i> : ''}</button>
                    </div>
                </Form>
            </Card.Body>
        </Card>
    )
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(Setting);