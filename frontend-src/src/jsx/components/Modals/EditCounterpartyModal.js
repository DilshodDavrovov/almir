import React from 'react';
import { Modal } from 'react-bootstrap';
import { connect } from 'react-redux';
import { useState } from 'react';
import { TR } from '../../../utils/helpers';
import { useEffect } from 'react';
import ServerSelect from '../../components/React-Select-Server';
import District from '../../../services/cruds/DistrictService'
import Region from '../../../services/cruds/RegionService'
function EditCounterpartyModal(props) {
    const { editData, editModal, setEditModal, edit, lang, API } = props;
    const [data, setData] = useState({
        name: "",
        inn: "",
        country_id: 19,
        region_id: null,
        district_id: null,
        is_active: true,
        deleted: false
    })
    const [listLoading, setListLoading] = useState({
        region: false,
        district: false
    })
    const [list, setList] = useState({
        region: [],
        district: []
    })

    const handleDelete = () => {
        if (data.deleted) {
            setData({ ...data, deleted: false })
        } else {
            setData({ ...data, is_active: false, deleted: true })
        }

    };
    const handleActive = () => {
        if (data.is_active) {
            setData({ ...data, is_active: false })
        } else {
            setData({ ...data, is_active: true, deleted: false })
        }
    };
    useEffect(() => {
        if (editData?.name) {
            API.getById(editData.id).then(res => {
                const temp = res.data.data;
                setData({
                    ...data,
                    name: temp.name,
                    inn: temp.inn,
                    region_id: temp.region_id || null,
                    district_id: temp.district_id || null,
                    is_active: temp.is_active,
                    deleted: temp.deleted
                })
                Region.getList(1, 100, 1, 0, [], { key: "id", "value": true }).then((res) => {
                    const newList = res.data.data.filter(el => el.id !== data[`region_id`]);
                    setList({
                        district: [{ value: temp.district?.id, label: temp.district?.name }],
                        region: newList.map(key => ({
                            value: key.id,
                            label: key.name
                        }))
                    });
                })
            })
        } else {
            setList({
                region: [],
                district: []
            })
            setData({
                name: "",
                inn: "",
                country_id: 19,
                region_id: null,
                district_id: null,
                is_active: true,
                deleted: false
            })
        }
    }, [editData])
    const filterDb = (arr_key, API, value, index, additional) => {
        setListLoading(prev => {
            prev[arr_key] = true;
            return prev;
        })
        API.select(true, false, value, additional).then((res) => {
            const newList = res.data.data.filter(el => el.id !== data[`${arr_key}_id`]);

            setList(prev => {
                prev[arr_key] = [
                    ...newList.map(key => ({
                        value: key.id,
                        label: key.full_name
                    })),
                    ...prev[arr_key]
                ]
                return { ...prev }
            });
            setListLoading(prev => {
                prev[arr_key] = false;
                return { ...prev };
            })
        })
    };

    return (
        <Modal className="modal fade" show={editModal} onHide={setEditModal} >
            <div className="" role="document">
                <form onSubmit={e => edit(e, editData.id, data)}>
                    <div className="modal-header">
                        <h4 className="modal-title fs-20">{TR(lang, "content.editing")} </h4>
                        <button type="button" className="btn-close" onClick={() => setEditModal(false)} data-dismiss="modal"><span></span></button>
                    </div>
                    <div className="modal-body">
                        <i className="flaticon-cancel-12 close" data-dismiss="modal"></i>
                        <div className="add-contact-box">
                            <div className="add-contact-content">
                                <div className="form-group mb-3">
                                    <label className="text-black font-w500" htmlFor="df_name">{TR(lang, "table.name")}</label>
                                    <div className="contact-name">
                                        <input
                                            onChange={e => setData({ ...data, name: e.target.value })}
                                            value={data.name}
                                            id='df_name' type="text" className="form-control" autoComplete="off"
                                            name="name" required="required"
                                        />
                                        <span className="validation-text"></span>
                                    </div>
                                </div>
                                <div className="form-group mb-3">
                                    <label className="text-black font-w500" htmlFor="c_inn">{TR(lang, "cruds.inn")}</label>
                                    <div className="contact-name">
                                        <input
                                            onChange={e => setData({ ...data, inn: e.target.value })}
                                            value={data.inn}
                                            id='c_inn' type="text" className="form-control" autoComplete="off"
                                            name="name" required="required"
                                        />
                                        <span className="validation-text"></span>
                                    </div>
                                </div>
                                {
                                    data.country_id ?
                                        <div className="form-group mb-3">
                                            <label className='black-font' htmlFor='region'>{TR(lang, "products.region")}</label>
                                            <ServerSelect
                                                id="region"
                                                API={Region}
                                                arr_key='region'
                                                options={list.region}
                                                onChange={e => {
                                                    if (e.value !== data.region_id) {
                                                        setData({ ...data, district_id: null, region_id: e.value });
                                                        setList({ ...list, district: [] });
                                                    }
                                                }}
                                                value={list.region.filter(key => key.value === data.region_id)}
                                                isLoading={listLoading.region}
                                                filterDb={filterDb}
                                                additional={{ country_id: data.country_id }}
                                                placeholder={TR(lang, "products.region")}
                                            />
                                        </div>
                                        : null
                                }
                                {
                                    data.region_id ?
                                        <div className="form-group mb-3">
                                            <label className='black-font' htmlFor='district'>{TR(lang, "products.district")}</label>
                                            <ServerSelect
                                                id="district"
                                                API={District}
                                                arr_key='district'
                                                options={list.district}
                                                onChange={e => setData({ ...data, district_id: e.value })}
                                                value={list.district.filter(key => key.value === data.district_id)}
                                                isLoading={listLoading.district}
                                                filterDb={filterDb}
                                                additional={{ region_id: data.region_id }}
                                                placeholder={TR(lang, "products.district")}
                                            />
                                        </div>
                                        : null
                                }
                                <div className="form-group mb-3 d-flex">
                                    <div className="form-check form-switch me-2">
                                        <input
                                            checked={data.is_active}
                                            onChange={() => handleActive()}
                                            type="checkbox" role="switch" className="form-check-input" id="df_is_active" />
                                        <label className="form-check-label" htmlFor="df_is_active" >
                                            {TR(lang, "content.activeOne")}
                                        </label>
                                    </div>
                                    <div className="form-check form-switch">
                                        <input
                                            checked={data.deleted}
                                            onChange={() => handleDelete()}
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
                        <button type="button" onClick={() => setEditModal(false)} className="btn btn-danger"> {TR(lang, "content.cancel")}</button>
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

export default connect(mapStateToProps)(EditCounterpartyModal);