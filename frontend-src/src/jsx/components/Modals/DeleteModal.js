import React from 'react';
import {Modal} from 'react-bootstrap';
import { TR } from './../../../utils/helpers';
import { connect } from 'react-redux';

function DeleteModal(props) {

  const {delModal, setDelModal, delId, del, lang} = props;
  const {id, deleted} = delId || {id:undefined, deleted: undefined};
  let option = {
    title : TR(lang, "content.deleting"),
    text : TR(lang, "content.toDelete"),
    btnColor : "danger",
  };

    return (
        <Modal className="modal fade"  show={delModal} onHide={setDelModal} >
            <div className="modal-header">
                <h4 className="modal-title fs-20">{option.title}</h4>
                <button type="button" className="btn-close" onClick={()=> setDelModal(false)} data-dismiss="modal"><span></span></button>
            </div>
            <div className="modal-body">
                <i className="flaticon-cancel-12 close" data-dismiss="modal"></i>
                <div className="add-contact-box">
                    {TR(lang, "content.modalQuestOne")} {option.text.toLowerCase()}{TR(lang, "content.modalQuestLast")}
                </div>
            </div>
            <div className="modal-footer">
                <button onClick={()=> setDelModal(false)} className={`btn btn-primary`}>{TR(lang, "content.cancel")}</button>      
                <button className={`btn btn-${option.btnColor}`} onClick={(e) =>{e.preventDefault(); del(id, deleted)}}>{option.text}</button>   
            </div>
        </Modal>
    );
  }
  
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(DeleteModal);