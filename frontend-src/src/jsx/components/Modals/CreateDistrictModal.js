import React, { useEffect } from 'react';
import {Modal} from 'react-bootstrap';
import { connect } from 'react-redux';
import { useState } from 'react';
import { TR } from '../../../utils/helpers';
import ServerSelect from '../../components/React-Select-Server';
import API from '../../../services/cruds/RegionService'
function CreateDistrictModal(props) {
    const {addModal, setAddModal, add, lang} = props;
    const [regionList, setRegionList] = useState([]);
    const [listLoading, setListLoading] = useState(false);
    const handleDelete=()=>{
        if(isDeleted){
            setIsDeleted(false);
        } else {
            setIsDeleted(true);
            setIsActive(false);
        }

    };
    const handleActive=()=>{
        if(isActive){
            setIsActive(false);
        } else {
            setIsActive(true);
            setIsDeleted(false);
        }
    };
    const filterDb = (arr_key, API, value) => {
        setListLoading(true);
        API.select(true, false, value).then((res)=>{
            setRegionList([...res.data.data.map(key => ({
                value: key.id,
                label: key.full_name
            }))])
            setListLoading(false);
        });
    };
    
    const [name, setName] = useState('');
    const [regionId, setRegionId] = useState('');
    const [isActive, setIsActive] = useState(true);
    const [isDeleted, setIsDeleted] = useState(false);
    useEffect(()=>{
        if(!addModal){
            setName("");
            setRegionId("");
            setIsActive(true);
            setIsDeleted(false);
        }
    },[addModal])
    return (
        <Modal className="modal fade"  show={addModal} onHide={setAddModal} >
            <div className="" role="document">
                <form onSubmit={e => add(e, {name, region_id: regionId, is_active: isActive, deleted: isDeleted})}>
                    <div className="modal-header">
                        <h4 className="modal-title fs-20">{TR(lang, "content.adding")} </h4>
                        <button type="button" className="btn-close" onClick={()=> setAddModal(false)} data-dismiss="modal"><span></span></button>
                    </div>
                    <div className="modal-body">
                        <i className="flaticon-cancel-12 close" data-dismiss="modal"></i>
                        <div className="add-contact-box">
                            <div className="add-contact-content">
                                <div className="form-group mb-3">
                                    <label className="text-black font-w500" htmlFor="c_name">{TR(lang, "table.name")}</label>
                                    <div className="contact-name">
                                        <input 
                                            onChange={e=>setName(e.target.value)}
                                            id='c_name' type="text"  className="form-control"  autoComplete="off"
                                            name="name" required="required"
                                        />
                                        <span className="validation-text"></span>
                                    </div>
                                </div>
                                <div className="form-group mb-3">
                                    <label className="text-black font-w500">{TR(lang, "products.region")}</label>
                                    <div className="contact-name">
                                    <ServerSelect
                                        API = {API}
                                        options = {regionList}
                                        onChange = {e => setRegionId(e.value)}
                                        value = {regionList.filter(key => key.value === regionId)}
                                        isLoading = {listLoading}
                                        filterDb = {filterDb}
                                        placeholder={TR(lang, "products.region")}
                                        required
                                    />
                                    <span className="validation-text"></span>
                                    </div>
                                </div>
                                <div className="form-group mb-3 d-flex">
                                    <div className="form-check form-switch me-2">
                                        <input 
                                            checked={isActive} 
                                            onChange={()=>handleActive()}
                                            type="checkbox" role="switch" className="form-check-input" id="df_is_active" />
                                        <label className="form-check-label" htmlFor="df_is_active" >
                                            {TR(lang, "content.activeOne")}
                                        </label>
                                    </div>
                                    <div className="form-check form-switch">
                                        <input 
                                            checked={isDeleted} 
                                            onChange={()=>handleDelete()}
                                            type="checkbox" role="switch" className="form-check-input" id="df_is_delete" />
                                        <label className="form-check-label" htmlFor="df_is_delete" >
                                            {TR(lang, "content.deletedOne")}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button type="button" onClick={()=> setAddModal(false)} className="btn btn-danger"> {TR(lang, "content.cancel")}</button>      
                        <button type="submit" className="btn btn-primary">{TR(lang, "content.toAdd")}</button>   
                    </div>
                </form>
            </div>
        </Modal>
    );
  }
  const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(CreateDistrictModal);