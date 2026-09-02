import React from 'react';
import {Modal} from 'react-bootstrap';
import { TR } from '../../../utils/helpers';
import { connect } from 'react-redux';

function StatusModal(props) {

    const {changeStatus, id, lang, statusModal, setStatusModal} = props;
    return (
        <Modal className="modal fade"  show={statusModal} onHide={setStatusModal} >
            <div className="modal-header">
                <h4 className="modal-title fs-20">{TR(lang, "content.modalStatTitle")}</h4>
                <button type="button" className="btn-close" onClick={()=> setStatusModal(false)} data-dismiss="modal"><span></span></button>
            </div>
            <div className="modal-body">
                <i className="flaticon-cancel-12 close" data-dismiss="modal"></i>
                <div className="add-contact-box">
                    {TR(lang, "content.modalStatText")}
                </div>
            </div>
            <div className="modal-footer">
                <button onClick={setStatusModal} className={`btn btn-primary`}>{TR(lang, "content.cancel")}</button>
                <button onClick={()=> changeStatus(id.id)} className='btn btn-success'>{TR(lang, "content.change")}</button>
            </div>
        </Modal>
    );
    
  }
  
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(StatusModal);