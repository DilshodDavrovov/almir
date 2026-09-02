import React from 'react';
import {Modal} from 'react-bootstrap';
import { connect } from 'react-redux';
import { useState } from 'react';
import { TR } from '../../../utils/helpers';
import { useEffect } from 'react';
function EditCountryModal(props) {
    const {editData, editModal, setEditModal, edit, lang, API} = props;
    const [name, setName] = useState("");
    const [code, setCode] = useState("");
    const [isActive, setIsActive] = useState(true);
    const [isDeleted, setIsDeleted] = useState(false);

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
    useEffect(()=>{
        if(editData?.name){
            API.getById(editData.id).then(res => {
                const temp = res.data.data;
                setName(temp.name);
                setCode(temp.code);
                setIsActive(temp.is_active);
                setIsDeleted(temp.deleted);
            })
        } else {
            setName("");
            setCode("");
            setIsActive(true);
            setIsDeleted(false);
        }
        
    },[editData])
    return (
        <Modal className="modal fade"  show={editModal} onHide={setEditModal} >
            <div className="" role="document">
                <form onSubmit={e => edit(e, editData.id, {name, code, is_active: isActive, deleted: isDeleted})}>
                    <div className="modal-header">
                        <h4 className="modal-title fs-20">{TR(lang, "content.editing")} </h4>
                        <button type="button" className="btn-close" onClick={()=> setEditModal(false)} data-dismiss="modal"><span></span></button>
                    </div>
                    <div className="modal-body">
                        <i className="flaticon-cancel-12 close" data-dismiss="modal"></i>
                        <div className="add-contact-box">
                            <div className="add-contact-content">
                                <div className="form-group mb-3">
                                    <label className="text-black font-w500" htmlFor="df_name">{TR(lang, "table.name")}</label>
                                    <div className="contact-name">
                                        <input 
                                            onChange={e=>setName(e.target.value)}
                                            value={name}
                                            id='df_name' type="text"  className="form-control"  autoComplete="off"
                                            name="name" required="required"
                                        />
                                        <span className="validation-text"></span>
                                    </div>
                                </div>
                                <div className="form-group mb-3">
                                    <label className="text-black font-w500" htmlFor="c_code">{TR(lang, "content.code")}</label>
                                    <div className="contact-name">
                                        <input 
                                            value={code}
                                            onChange={e=>setCode(e.target.value)}
                                            id='c_code' type="text"  className="form-control"  autoComplete="off"
                                            name="name" required="required"
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
                        <button type="button" onClick={()=> setEditModal(false)} className="btn btn-danger"> {TR(lang, "content.cancel")}</button>      
                        <button type="submit" className="btn btn-primary">{TR(lang, "content.save")}</button>   
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

export default connect(mapStateToProps)(EditCountryModal);