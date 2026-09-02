import React, { useEffect, useState } from 'react'
import { TR } from '../../../../utils/helpers'
import { connect, useDispatch } from 'react-redux'
import User from '../../../../services/cruds/UserService'
import { Link, useHistory } from 'react-router-dom'
import Loading from '../../../components/Loading'
import { userEditDataAction } from '../../../../store/actions/UserAction'
import { ParseDateToString, showToast } from '../../../../utils'
import ReactDatePicker from 'react-datepicker'


function EditUser(props) {
    const { id } = props.match.params;
    const [userLoading, setUserLoading] = useState(true);
    const dispatch = useDispatch();
    const history = useHistory()
    const { setLoading, filter, lang, data } = props;

    const getUserList = () => {
        setUserLoading(true);
        User.getOne(id).then(res => {
            const user = res.data.data;
            dispatch(userEditDataAction({
                user_id: user.id,
                email: user.email,
                first_name: user.first_name,
                last_name: user.last_name,
                middle_name: user.middle_name,
                phone_number: user.phone_number,
                passport_info: user.passport_info,
                user_role: Number(user.user_role),
                address: user.address,
                company_name: user.company_name,
                company_inn: user.company_inn,
                um_created_at: new Date(user.um_created_at),
                um_expired_at: new Date(user.um_expired_at),
                otm_created_at: new Date(user.otm_created_at),
                user_mac: user.user_mac,
                one_time_mac: user.one_time_mac,
                confirmed: user.confirmed,
                is_blocked: user.is_blocked,
                status: user.status,
                shipment_access: user.shipment_access
            }))

            setUserLoading(false);
        })
    };
    useEffect(() => {
        getUserList()
    }, []);

    const editUser = (e) => {
        e.preventDefault();
        const temp_data = { ...data };

        User.edit(temp_data).then(res => {
            showToast('success', res.data.message);
            filter();
            setLoading(true);
            history.push('/user');
        }).catch(err => {
            showToast('error', err.response.data.message);
        })
    }



    return (
        <>
            <div className='card'>
                <div className='card-header'>
                    <h4 className='card-title'>{`${TR(lang, "content.editing")} ${TR(lang, "cruds.user")}`}</h4>
                </div>
                <div className='card-body'>
                    {
                        userLoading ? <Loading /> :
                            <div className='basic-from'>
                                <form onSubmit={(e) => editUser(e)}>
                                    <div className='row'>
                                        <div className='form-group mb-3 col-md-4'>
                                            <label htmlFor='first_name'>{TR(lang, 'reg.name')}
                                                <span className='red-star'>*</span>
                                            </label>
                                            <input
                                                className='form-control'
                                                onChange={e => dispatch(userEditDataAction({ ...data, first_name: e.target.value }))}
                                                value={data.first_name}
                                                placeholder={TR(lang, 'reg.name')}
                                                id='first_name'
                                                type='text'
                                                required
                                            />
                                        </div>
                                        <div className='form-group mb-3 col-md-4'>
                                            <label htmlFor='last_name'>{TR(lang, 'reg.secName')}
                                                <span className='red-star'>*</span>
                                            </label>
                                            <input
                                                className='form-control'
                                                onChange={e => dispatch(userEditDataAction({ ...data, last_name: e.target.value }))}
                                                value={data.last_name}
                                                placeholder={TR(lang, 'reg.secName')}
                                                id='last_name'
                                                type='text'
                                                required
                                            />
                                        </div>
                                        <div className='form-group mb-3 col-md-4'>
                                            <label htmlFor='middle_name'>{TR(lang, 'reg.lastName')}
                                            </label>
                                            <input
                                                className='form-control'
                                                onChange={e => dispatch(userEditDataAction({ ...data, middle_name: e.target.value }))}
                                                value={data.middle_name}
                                                placeholder={TR(lang, 'reg.lastName')}
                                                id='middle_name'
                                                type='text'
                                            />
                                        </div>
                                        <div className='form-group mb-3 col-md-12'>
                                            <label htmlFor='email'>{TR(lang, 'auth.email')}
                                                <span className='red-star'>*</span>
                                            </label>
                                            <input
                                                className='form-control'
                                                onChange={e => dispatch(userEditDataAction({ ...data, email: e.target.value }))}
                                                value={data.email}
                                                placeholder={TR(lang, 'auth.email')}
                                                id='email'
                                                type='text'
                                                required
                                            />
                                        </div>

                                        <div className='form-group mb-3 col-md-6'>
                                            <label htmlFor='email'>{TR(lang, 'cruds.userMac')}
                                                <span className='red-star'>*</span>
                                            </label>
                                            <input
                                                className='form-control'
                                                onChange={e => dispatch(userEditDataAction({ ...data, user_mac: e.target.value }))}
                                                value={data.user_mac}
                                                placeholder={TR(lang, 'cruds.userMac')}
                                                id='user_mac'
                                                type='text'
                                                required
                                            />
                                        </div>
                                        <div className='form-group mb-3 col-md-6'>
                                            <label htmlFor='one_time_mac'>{TR(lang, 'cruds.disMac')}
                                            </label>
                                            <input
                                                className='form-control'
                                                onChange={e => dispatch(userEditDataAction({ ...data, one_time_mac: e.target.value }))}
                                                value={data.one_time_mac}
                                                placeholder={TR(lang, 'cruds.disMac')}
                                                id='one_time_mac'
                                                type='text'
                                            />
                                        </div>

                                        <div className='col-md-12 mb-3'>
                                            <label>{TR(lang, "content.role")}</label>
                                            <select className="form-select text-black"
                                                onChange={e => dispatch(userEditDataAction({ ...data, user_role: e.target.value }))}
                                            >
                                                <option selected={data.user_role == 1} value={1}>Admin</option>
                                                <option selected={data.user_role == 2} value={2}>Employe</option>
                                                <option selected={data.user_role == 3} value={3}>User</option>
                                                <option selected={data.user_role == 4} value={4}>Guest</option>
                                                <option selected={data.user_role == 5} value={5}>Demo</option>
                                            </select>
                                        </div>

                                        <div className='col-md-6 mb-3'>
                                            <label htmlFor='um_created_at'>{TR(lang, "cruds.UMCreated")}</label>
                                            <ReactDatePicker
                                                showYearDropdown
                                                dropdownMode="select"
                                                className="form-control form-control-sm"
                                                onSelect={e => dispatch(userEditDataAction({ ...data, um_created_at: e }))}
                                                onChange={e => dispatch(userEditDataAction({ ...data, um_created_at: e }))}
                                                selected={data.um_created_at}
                                                dateFormat='dd/MM/yyyy'
                                                id='um_created_at'
                                                required
                                            />
                                        </div>
                                        <div className='col-md-6 mb-3'>
                                            <label htmlFor='um_expired_at'>{TR(lang, "cruds.UMExp")}</label>
                                            <ReactDatePicker
                                                showYearDropdown
                                                dropdownMode="select"
                                                className="form-control form-control-sm"
                                                onSelect={e => dispatch(userEditDataAction({ ...data, um_expired_at: e }))}
                                                onChange={e => dispatch(userEditDataAction({ ...data, um_expired_at: e }))}
                                                selected={data.um_expired_at}
                                                dateFormat='dd/MM/yyyy'
                                                id='um_expired_at'
                                                required
                                            />
                                        </div>
                                        <div className='col-md-6 mb-3'>
                                            <label htmlFor='otm_created_at'>{TR(lang, "cruds.OTMCreated")}</label>
                                            <ReactDatePicker
                                                showYearDropdown
                                                dropdownMode="select"
                                                className="form-control form-control-sm"
                                                onSelect={e => dispatch(userEditDataAction({ ...data, otm_created_at: e }))}
                                                onChange={e => dispatch(userEditDataAction({ ...data, otm_created_at: e }))}
                                                selected={data.otm_created_at}
                                                dateFormat='dd/MM/yyyy'
                                                id='otm_created_at'
                                                required
                                            />
                                        </div>
                                        <div className='col-md-6 mb-3'></div>

                                        <div className='form-group mb-3 col-md-6'>
                                            <label htmlFor='company_name'>{TR(lang, "reg.compName")}
                                                <span className='red-star'>*</span>
                                            </label>
                                            <input
                                                className='form-control'
                                                id='company_name'
                                                value={data.company_name}
                                                type='text'
                                                placeholder={TR(lang, "reg.compName")}
                                                required
                                                onChange={e => dispatch(userEditDataAction({ ...data, company_name: e.target.value }))}
                                            />
                                        </div>
                                        <div className='form-group mb-3 col-md-6'>
                                            <label htmlFor='company_inn'>{TR(lang, "cruds.inn")}
                                                <span className='red-star'>*</span>
                                            </label>
                                            <input
                                                className='form-control'
                                                id='company_inn'
                                                value={data.company_inn}
                                                type='text'
                                                placeholder={TR(lang, "cruds.inn")}
                                                required
                                                onChange={e => dispatch(userEditDataAction({ ...data, company_inn: e.target.value }))}
                                            />
                                        </div>

                                        <div className='form-group mb-3 col-md-6'>
                                            <label htmlFor='phone_number'>{TR(lang, "reg.phone")}
                                                <span className='red-star'>*</span>
                                            </label>
                                            <input
                                                className='form-control'
                                                id='phone_number'
                                                value={data.phone_number}
                                                type='text'
                                                placeholder={TR(lang, "reg.phone")}
                                                required
                                                onChange={e => dispatch(userEditDataAction({ ...data, phone_number: e.target.value }))}
                                            />
                                        </div>

                                        <div className='form-group mb-3 col-md-6'>
                                            <label htmlFor='passport_info'>{TR(lang, "content.serPass")}
                                            </label>
                                            <input
                                                className='form-control'
                                                id='passport_info'
                                                value={data.passport_info}
                                                type='text'
                                                placeholder={TR(lang, "content.serPass")}
                                                onChange={e => dispatch(userEditDataAction({ ...data, passport_info: e.target.value }))}
                                            />
                                        </div>

                                        <div className='form-group mb-3 col-md-6'>
                                            <label htmlFor='address'>{TR(lang, "reg.address")}
                                            </label>
                                            <input
                                                className='form-control'
                                                id='address'
                                                value={data.address}
                                                type='text'
                                                placeholder={TR(lang, "reg.address")}
                                                onChange={e => dispatch(userEditDataAction({ ...data, address: e.target.value }))}
                                            />
                                        </div>
                                        <div className='col-md-6 d-flex justify-content-between'>
                                            <div className='form-check form-switch d-flex align-items-center'>
                                                <input
                                                    type='checkbox'
                                                    className='form-check-input'
                                                    checked={data.is_blocked}
                                                    onChange={() => dispatch(userEditDataAction({ ...data, is_blocked: !data.is_blocked }))}
                                                />
                                                <label className='form-check-label text-nowrap my-0'>
                                                    {TR(lang, "content.block")}
                                                </label>
                                            </div>
                                            <div className='form-check form-switch d-flex align-items-center'>
                                                <input
                                                    type='checkbox'
                                                    className='form-check-input'
                                                    checked={data.status}
                                                    onChange={() => dispatch(userEditDataAction({ ...data, status: !data.status }))}
                                                />
                                                <label className='form-check-label text-nowrap my-0'>
                                                    {TR(lang, "content.status")}
                                                </label>
                                            </div>
                                            <div className='form-check form-switch d-flex align-items-center'>
                                                <input
                                                    type='checkbox'
                                                    className='form-check-input'
                                                    checked={data.confirmed}
                                                    onChange={() => dispatch(userEditDataAction({ ...data, confirmed: !data.confirmed }))}
                                                />
                                                <label className='form-check-label text-nowrap my-0'>
                                                    {TR(lang, "content.confirmed")}
                                                </label>
                                            </div>
                                        </div>
                                        <div className='col-md-6'>
                                            <div className='form-check form-switch'>
                                                <label htmlFor='user_shipment_access'>
                                                    {TR(lang, "content.shipment_access")}
                                                </label>
                                                <input
                                                    id = 'user_shipment_access'
                                                    type='checkbox'
                                                    className='form-check-input'
                                                    checked={data.shipment_access}
                                                    onChange={() => dispatch(userEditDataAction({ ...data, shipment_access: !data.shipment_access }))}
                                                />

                                            </div>
                                        </div>
                                    </div>
                                    <div className='d-flex my-2 float-end'>
                                        <Link className='btn btn-warning w-100 text-white text-decoration-none' to='/user'>{TR(lang, "content.cancel")}</Link>
                                        <button type='submit' className="ms-3 btn btn-primary">{TR(lang, "content.save")}</button>
                                    </div>
                                </form>
                            </div>
                    }
                </div>
            </div>

        </>
    )
}

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        data: state.user.editData
    };
};

export default connect(mapStateToProps)(EditUser)




