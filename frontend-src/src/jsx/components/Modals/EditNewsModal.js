import React from 'react';
import { connect } from 'react-redux';
import {Modal, Form} from "react-bootstrap";
import { useState } from 'react';
import { TR } from '../../../utils/helpers';
import { useEffect } from 'react';
import { baseURL } from './../../../services/AxiosInstance';
function EditNewsModal(props) {
    const {editData, editModal, setEditModal, edit, lang, API} = props;
    const [imagePreview, setImagePreview] = useState(null);
    const [image, setImage] = useState(null);
    const [title, setTitle] = useState("");
    const [description, setDescription] = useState("");
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
        if(editData?.title){
            API.getById(editData.id).then(res => {
                const temp = res.data.data;
                setImagePreview(`${baseURL}/public/${temp.image}-b.png`)
                setDescription(temp.description);
                setTitle(temp.title);
                setIsActive(temp.is_active);
                setIsDeleted(temp.deleted);
            })
        } else {
            setDescription("");
            setTitle("");
            setImagePreview(null);
            setIsActive(true);
            setIsDeleted(false);
        }
        
    },[editData])
    const handleChange = e => {
        if(e.target.files){
            setImage(e.target.files[0]);
            setImagePreview(URL.createObjectURL(e.target.files[0]))
        }
    }
    return (
        <Modal show={editModal} onHide={setEditModal}>
            <Modal.Header><h5 className='m-0'>{TR(lang, "content.editing")}</h5></Modal.Header>
            <Modal.Body>
                <Form onSubmit={e => {
                    const formData = new FormData();
                    formData.append("slug", "");
                    formData.append('title', title);
                    formData.append('description', description);
                    formData.append('image', image);
                    formData.append('is_active', isActive?"1":"0");
                    formData.append('deleted', isDeleted?"1":"0");
                    edit(e, editData.id, formData);
                }}>
                    <div className="form-group mb-3">
                        <input 
                            className="form-control form-control-sm mb-1"
                            onChange={handleChange}
                            value={image?.filename} 
                            accept="image/*" 
                            type='file'
                        />
                        <img style={{maxWidth: "100%", height: 'auto'}} src={imagePreview} alt="your image" />
                    </div>
                    <div className="form-group mb-3">
                        <label className="text-black font-w500">{TR(lang, "table.title")}</label>
                        <input 
                            onChange={e => setTitle(e.target.value)}
                            value={title}
                            className="form-control"
                            placeholder = {TR(lang, "table.title")}
                            required
                        />
                    </div>
                    <div className="form-group mb-3">
                        <label className="text-black font-w500">{TR(lang, "table.text")}</label>
                        <textarea
                            onChange={e => setDescription(e.target.value)}
                            value={description}
                            className="form-control"
                            rows = {4}
                            required
                        />
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
                    <div className='d-flex justify-content-between'>
                        <button type="button" onClick={()=> setEditModal(false)} className="btn btn-danger"> {TR(lang, "content.cancel")}</button>      
                        <button type="submit" className="btn btn-primary">{TR(lang, "content.save")}</button>   
                    </div>
                </Form>
            </Modal.Body>
        </Modal>
    );
  }
  const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(EditNewsModal);