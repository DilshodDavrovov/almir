import React from 'react';
import { connect } from 'react-redux';
import {Modal, Form} from "react-bootstrap";
import { useState } from 'react';
import { TR } from '../../../utils/helpers';
function CreateNewsModal(props) {
    const {addModal, setAddModal, add, lang} = props;
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
    const handleChange = e => {
        if(e.target.files){
            setImage(e.target.files[0]);
            setImagePreview(URL.createObjectURL(e.target.files[0]))
        }
    }
    return (
        <Modal show={addModal} onHide={setAddModal}>
            <Modal.Header><h5 className='m-0'>{TR(lang, "content.adding")}</h5></Modal.Header>
            <Modal.Body>
                <Form onSubmit={e => {
                    const formData = new FormData();
                    formData.append("slug", "");
                    formData.append('title', title);
                    formData.append('description', description);
                    formData.append('image', image);
                    formData.append('is_active', isActive?"1":"0");
                    formData.append('deleted', isDeleted?"1":"0");
                    add(e, formData);
                }}>
                    <div className="form-group mb-3">
                        <input 
                            className="form-control form-control-sm mb-1"
                            onChange={handleChange}
                            value={image?.filename} 
                            accept="image/*" 
                            type='file' 
                            required
                        />
                        <img  style={{maxWidth: "100%", height: 'auto'}}  src={imagePreview} alt="your image" />
                    </div>
                    <div className="form-group mb-3">
                        <label className="text-black font-w500">{TR(lang, "table.title")}</label>
                        <input 
                            onChange={e => setTitle(e.target.value)}
                            className="form-control"
                            placeholder = {TR(lang, "table.title")}
                            required
                        />
                    </div>
                    <div className="form-group mb-3">
                        <label className="text-black font-w500">{TR(lang, "table.text")}</label>
                        <textarea
                            onChange={e => setDescription(e.target.value)}
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
                        <button type="button" onClick={()=> setAddModal(false)} className="btn btn-danger"> {TR(lang, "content.cancel")}</button>      
                        <button type="submit" className="btn btn-primary">{TR(lang, "content.toAdd")}</button>   
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

export default connect(mapStateToProps)(CreateNewsModal);