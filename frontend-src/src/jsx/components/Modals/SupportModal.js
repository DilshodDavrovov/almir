import React from 'react';
import { Modal } from 'react-bootstrap';
import { TR } from '../../../utils/helpers';
import { connect } from 'react-redux';
import { useState } from 'react';
import API from '../../../services/SettingService'
import { showToast } from '../../../utils';
import { useEffect } from 'react';
function SupportModal(props) {

    const {lang, show, setShow, settingsData, userInfo } = props;
    const [data, setData] = useState({
        email: '',
        subject: '',
        description: '',
    })
    useEffect(() => {
        setData({
            email: userInfo.email,
            subject: '',
            description: '',
        })
    }, [show])
    const handleSubmit = (e) => {
        e.preventDefault();
        API.sendMessage(data).then(res => {
            showToast('success', res.data.message);
        }).catch(err => {
            showToast('error', err.response.data.message);
        }).finally(() => {
            setData({
                email: userInfo.email,
                subject: '',
                description: '',
            })
            setShow(false);
        })
    }
    return (
        <Modal show={show} onHide={setShow}>
            <Modal.Header className="c-header" closeButton>
                <Modal.Title>{TR(lang, 'content.contact')}</Modal.Title>
            </Modal.Header>
            <Modal.Body>
                <form onSubmit={(e) => handleSubmit(e)}>
                    <div className="mb-3">
                        <label className="form-label">
                            {TR(lang, 'auth.email')}
                        </label>
                        <input
                            type="email"
                            onChange={e => setData({...data, email: e.target.value})}
                            value = {data.email}
                            className="form-control"
                        />
                    </div>
                    <div className="mb-3">
                        <label className="form-label">
                            {TR(lang, 'content.topic')}
                        </label>
                        <input
                            type="text"
                            onChange={e => setData({...data, subject: e.target.value})}
                            value = {data.subject}
                            className="form-control"
                        />
                    </div>
                    <div className="mb-3">
                        <label className="form-label">
                            {TR(lang, 'table.text')}
                        </label>
                        <textarea
                            onChange={e => setData({...data, description: e.target.value})}
                            value = {data.description}
                            style={{ height: '100px' }}
                            className="form-control"
                            rows="3"
                        ></textarea>
                    </div>
                    <div className="text-center mb-3">
                        {
                            settingsData.contact_fax ? 
                            <div className="text-nowrap text-black ms-1">
                                {TR(lang, "reg.fax")}: {settingsData.contact_fax}
                            </div>: null
                        }
                        {
                            settingsData.contact_email ? 
                            <div className="text-nowrap text-black ms-1">
                                {TR(lang, "auth.email")}: {settingsData.contact_email}
                            </div>: null
                        }
                        {
                            settingsData.contact_phone ? 
                            <div className="text-nowrap text-black ms-1">
                                {TR(lang, "reg.phone")}: {settingsData.contact_phone}
                            </div>: null
                        }
                    </div>
                    <div className='d-flex justify-content-between'>
                        <button type="button" onClick={()=> setShow(false)} className="btn btn-danger"> {TR(lang, "content.cancel")}</button>      
                        <button type="submit" className="btn btn-primary">{TR(lang, "support.send")}</button>   
                    </div>
                </form>

            </Modal.Body>
        </Modal>
    );

}

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        settingsData: state.main.settingsData,
        userInfo: state.main.userInfo
    };
};

export default connect(mapStateToProps)(SupportModal);