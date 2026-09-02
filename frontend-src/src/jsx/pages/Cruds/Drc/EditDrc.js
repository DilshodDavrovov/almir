import React, {useEffect, useState} from 'react';
import ServerSelect from '../../../components/React-Select-Server';
import {TR} from "../../../../utils/helpers";
import { connect, useDispatch } from 'react-redux';
import Distributor from '../../../../services/cruds/DistributorService';
import Company from '../../../../services/cruds/CompanyService';
import Drug from '../../../../services/cruds/DrugsService';
import Drc from '../../../../services/cruds/DrcService';
import { Link, useHistory } from 'react-router-dom';
import { drcEditDataAction, drcEditSelectAction, drcEditSelectLoadingAction, priceLoadingAction } from './../../../../store/actions/DrcAction';
import { NumberToStr, ParseDateToString, showToast, StrtoNumber } from '../../../../utils';
import Loading from '../../../components/Loading';
import ReactDatePicker from 'react-datepicker';
import CounterParty from '../../../../services/cruds/CounterPartyService';
function EditDrc (props) {
    const {id} = props.match.params;
    const [drcLoading, setDrcLoading] = useState(true);
    const [timer, setTimer] = useState(null)
    const today = new Date();
    const date = today.getDate()+'/'+(today.getMonth()+1)+'/'+today.getFullYear();
    const dispatch = useDispatch();
    const history = useHistory();
    const priceOptions = [{value: "USD", label:"$"},{value: "EUR", label:"€"},{value: "RUB", label:"₽"},{value: "UZS", label:"SO'M"},];
    const {setLoading, filter, price_loading, c_price_loading, lang, data, list, listLoading} = props;

    const getDrcList=()=>{
        setDrcLoading(true);
        Drc.getOne(id, 1).then(res=>{
            const drc = res.data.data;
            dispatch(drcEditDataAction({
                ...data,
                drug_id: drc.drug_name.id,
                serial_number: drc.serial_number,
                shelf_life: new Date(drc.shelf_life),
                mode_70_date: new Date(drc.mode_70_date),
                m70d_id: drc.distributor70?.id,
                mode_40_date: new Date(drc.mode_40_date),
                m40d_id: drc.distributor40.id,
                sc_id: drc.sender_company.id,
                counterparty_id: drc.counterparty_id,
                period_code: drc.period_code,
                c_price_ccy: drc.c_price_ccy || "USD",
                c_price_ccy_rate: drc.c_price_ccy_rate || "",
                c_price_uzs: drc.c_price_uzs || "",
                c_price_usd: drc.c_price_usd || "",
                c_price_eur: drc.c_price_eur || "",
                c_price_rub: drc.c_price_rub || "",
                price_ccy: drc.price_ccy,
                price_ccy_rate: drc.price_ccy_rate,
                price_usd: drc.price_usd,
                price_uzs: drc.price_uzs,
                price_eur: drc.price_eur,
                price_rub: drc.price_rub,
                quantity: drc.quantity,
                sum_price_usd: drc.sum_price_usd,
                sum_price_uzs: drc.sum_price_uzs,
                sum_price_eur: drc.sum_price_eur,
                sum_price_rub: drc.sum_price_rub,
                is_local: drc.is_local
            }))
            dispatch(drcEditSelectAction({
                drug: [{value: drc.drug_name.id, label: drc.drug_name.name}],
                distributor_40: [{value: drc.distributor40.id, label: drc.distributor40.name}],
                distributor_70: [{value: drc.distributor70?.id, label: drc.distributor70?.name}],
                company: [{value: drc.sender_company.id, label: drc.sender_company.name}],
                counterparty: drc.counterparty ? [{ value: drc.counterparty.id, label: drc.counterparty.name }] : []
            }))
            setDrcLoading(false);
        }).catch(err => {
            setDrcLoading(false);
            showToast('error', err.response.data.message);
            filter();
            setLoading(true);
            history.push('/admin/drc');
        })
    };
    useEffect(()  => {
        getDrcList();
    },[]);
    const editDrc = e => {
        e.preventDefault();

        const temp_data = {...data};
        temp_data.shelf_life = ParseDateToString(data.shelf_life),
        temp_data.mode_70_date = ParseDateToString(data.mode_70_date),
        temp_data.mode_40_date = ParseDateToString(data.mode_40_date),
        temp_data.sum_price_usd = data.price_usd  * data.quantity;
        temp_data.sum_price_uzs = data.price_uzs  * data.quantity;
        temp_data.sum_price_eur = data.price_eur  * data.quantity;
        temp_data.sum_price_rub = data.price_usd  * data.quantity;

        if(data.is_local){
            temp_data.c_price_ccy = "USD",
            temp_data.c_price_ccy_rate = 0,
            temp_data.c_price_usd = 0,
            temp_data.c_price_uzs = 0,
            temp_data.c_price_eur = 0,
            temp_data.c_price_rub = 0,
            temp_data.mode_70_date = null;
            temp_data.m70d_id = null;
        }
        Drc.edit(id, temp_data).then(res=>{
            showToast('success', res.data.message);
            filter();
            setLoading(true);
            history.push('/admin/drc');
        }).catch(err=>{
            showToast('error', err.response.data.message);
        })
    }
    const handleLoading = (arr_key, value) => {
        listLoading[arr_key] = value;
        dispatch(drcEditSelectLoadingAction(listLoading));
    }
    const filterDb = (arr_key, API, value) => {
        handleLoading(arr_key, true)
        API.select(true, false, value).then((res)=>{
            list[arr_key] = [...res.data.data.map(key => ({
                value: key.id,
                label: key.full_name
            }))]
            dispatch(drcEditSelectAction(list));
            handleLoading(arr_key, false)
        })
    };
    const handleChangeValue = (value, type) => {
        const ccy = data[`${type}_ccy`].toLowerCase();
        data[`${type}_${ccy}`] = value;
        dispatch(priceLoadingAction(true, type));
        dispatch(drcEditDataAction({...data}));
        clearTimeout(timer)
        const newTimer = setTimeout(() => {
            Drc.getCurrencyList({ccv: value, ccy: data[`${type}_ccy`], date: data.mode_40_date}).then((res) => {
                const temp = res.data.data;
                data[`${type}_ccy_rate`] = temp[`${ccy}_price_rate`];
                data[`${type}_uzs`] = temp.uzs_price_rate;
                data[`${type}_usd`] = temp.usd_price_rate;
                data[`${type}_eur`] = temp.eur_price_rate;
                data[`${type}_rub`] = temp.rub_price_rate;
                dispatch(drcEditDataAction({...data}));
                dispatch(priceLoadingAction(false, type));
            })
        }, 500)
        setTimer(newTimer)
    }
    const handleChangeCcy = (value, type) => {
        const ccy = data[`${type}_ccy`].toLowerCase();
        dispatch(priceLoadingAction(true, type));
        Drc.getCurrencyList({ccv: data[`${type}_${ccy}`], ccy: value, date: data.mode_40_date}).then((res) => {
            const temp = res.data.data;
            data[`${type}_ccy`] = value;
            data[`${type}_ccy_rate`] = temp[`${ccy}_price_rate`];
            data[`${type}_uzs`] = temp.uzs_price_rate;
            data[`${type}_usd`] = temp.usd_price_rate;
            data[`${type}_eur`] = temp.eur_price_rate;
            data[`${type}_rub`] = temp.rub_price_rate;
            dispatch(drcEditDataAction({...data}));
            dispatch(priceLoadingAction(false, type));
        })
    }

    return  <div className="card">
        <div className="card-header">
            <h4 className="card-title">{TR(lang, "content.editing")} {TR(lang, "cruds.drc")} {TR(lang, "cruds.edit")}</h4>
            <div className="form-check">
                <input
                    checked={data.is_local} 
                    value = {data.is_local}
                    onChange={()=> dispatch(drcEditDataAction({...data, is_local : !data.is_local}))} 
                    type="checkbox"
                    className="form-check-input"
                    id="isNational"
                />
                <label
                    className="form-check-label"
                    htmlFor="isNational"
                >{TR(lang, "cruds.national")}</label>
            </div>
        </div>
        <div className="card-body">
            {
                drcLoading ? <Loading /> :
                <div className="basic-form">
                    <form onSubmit={(e) => editDrc(e)}>
                        <div className="row">
                            <div className="form-group mb-3 col-md-6">
                                <label>{TR(lang, "table.name")}</label>
                                <ServerSelect
                                    API = {Drug}
                                    arr_key = 'drug'
                                    options = {list.drug}
                                    onChange = {e => dispatch(drcEditDataAction({...data, drug_id: e.value}))}
                                    value = {list.drug.filter(key => key.value === data.drug_id)}
                                    isLoading = {listLoading.drug}
                                    filterDb = {filterDb}
                                    placeholder = {TR(lang, "products.name")}
                                    required
                                />
                            </div>
                            <div className="form-group mb-3 col-md-6">
                                <label htmlFor='prixod'>Юникод</label>
                                <input 
                                    className='form-control'
                                    id='prixod'
                                    type = 'text' 
                                    placeholder = 'Юникод' 
                                    onChange={e => dispatch(drcEditDataAction({...data, period_code: e.target.value}))} 
                                    value={data.period_code} 
                                    required   
                                />
                            </div>
                            <div className="form-group mb-3 col-md-6">
                                <label htmlFor='serial_number'>{TR(lang, "table.serialNum")}</label>
                                <input 
                                    className='form-control'
                                    onChange={e => dispatch(drcEditDataAction({...data, serial_number : e.target.value}))} 
                                    value={data.serial_number}
                                    placeholder = {TR(lang, "table.serialNum")} 
                                    id="serial_number"
                                    type = 'text' 
                                    required
                                />
                            </div>
                            <div className="form-group mb-3 col-md-6">
                                <label htmlFor='shelf_life'>{TR(lang, "table.shelfLife")}</label>
                                <ReactDatePicker
                                    showYearDropdown
                                    dropdownMode="select"
                                    className="form-control"
                                    onSelect = {e => dispatch(drcEditDataAction({...data, shelf_life: e}))}
                                    onChange = {e => dispatch(drcEditDataAction({...data, shelf_life: e}))}
                                    selected = {data.shelf_life}
                                    dateFormat='dd/MM/yyyy'
                                    required
                                />
                            </div>
                        </div>
                        {
                            !data.is_local && 
                            <div className="row">
                                <div className="form-group mb-3 col-md-6">
                                    <label htmlFor='mode_70_date'>{TR(lang, "cruds.date70")}</label>
                                    <ReactDatePicker
                                        showYearDropdown
                                        dropdownMode="select"
                                        className="form-control"
                                        onSelect = {e => dispatch(drcEditDataAction({...data, mode_70_date: e}))}
                                        onChange = {e => dispatch(drcEditDataAction({...data, mode_70_date: e}))}
                                        selected = {data.mode_70_date}
                                        dateFormat='dd/MM/yyyy'
                                        required
                                    />
                                </div>
                                <div className="form-group mb-3 col-md-6">
                                    <label>{TR(lang, "cruds.dist70")}</label>
                                    <ServerSelect
                                        API = {Distributor}
                                        arr_key = 'distributor_70'
                                        options = {list.distributor_70}
                                        onChange = {e => dispatch(drcEditDataAction({...data, m70d_id: e.value}))}
                                        value = {list.distributor_70.filter(key => key.value === data.m70d_id)}
                                        isLoading = {listLoading.distributor_70}
                                        filterDb = {filterDb}
                                        placeholder = {TR(lang, "cruds.dist70")}
                                        required
                                    />
                                </div>
                            </div>
                        }
                        <div className="row">
                            <div className="form-group mb-3 col-md-6">
                                <label htmlFor='mode_40_date'>{TR(lang, "cruds.date40")}</label>
                                <ReactDatePicker
                                    showYearDropdown
                                    dropdownMode="select"
                                    className="form-control"
                                    onSelect = {e => dispatch(drcEditDataAction({...data, mode_40_date: e}))}
                                    onChange = {e => dispatch(drcEditDataAction({...data, mode_40_date: e}))}
                                    selected = {data.mode_40_date}
                                    dateFormat='dd/MM/yyyy'
                                    required
                                />
                            </div>
                            <div className="form-group mb-3 col-md-6">
                                <label>{TR(lang, "table.dist")} 40</label>
                                <ServerSelect
                                    API = {Distributor}
                                    arr_key = 'distributor_40'
                                    options = {list.distributor_40}
                                    onChange = {e => dispatch(drcEditDataAction({...data, m40d_id: e.value}))}
                                    value = {list.distributor_40.filter(key => key.value === data.m40d_id)}
                                    isLoading = {listLoading.distributor_40}
                                    filterDb = {filterDb}
                                    placeholder = {`${TR(lang, "table.dist")} 40`}
                                    required
                                />
                            </div>
                            <div className="form-group mb-3 col-md-6">
                                <label htmlFor='sender_company'>{TR(lang, "table.sender")}</label>
                                <ServerSelect
                                    id="sender_company"
                                    API = {Company}
                                    arr_key = 'company'
                                    options = {list.company}
                                    onChange = {e => dispatch(drcEditDataAction({...data, sc_id: e.value}))}
                                    value = {list.company.filter(key => key.value === data.sc_id)}
                                    isLoading = {listLoading.company}
                                    filterDb = {filterDb}
                                    placeholder = {TR(lang, "table.sender")}
                                    required
                                />
                            </div>

                            <div className="form-group mb-3 col-md-6">
                                <label className='black-font' htmlFor='counterparty'>{TR(lang, "products.counterparty")}</label>
                                <ServerSelect
                                    id="counterparty"
                                    API={CounterParty}
                                    arr_key='counterparty'
                                    options={list.counterparty}
                                    onChange={e => dispatch(drcEditDataAction({ ...data, counterparty_id: e.value }))}
                                    value={list.counterparty.filter(key => key.value === data.counterparty_id)}
                                    isLoading={listLoading.counterparty}
                                    filterDb={filterDb}
                                    placeholder={TR(lang, "products.counterparty")}
                                />
                            </div>
                            <div className="form-group mb-3 col-md-6">
                                <label htmlFor='quantity'>{TR(lang, "table.qty")}</label>
                                <input 
                                    className='form-control'
                                    id='quantity'
                                    type = 'text' 
                                    placeholder = {TR(lang, "table.qty")} 
                                    onChange={e => dispatch(drcEditDataAction({...data, quantity : StrtoNumber(e.target.value)}))} 
                                    value={NumberToStr(data.quantity)} 
                                    required   
                                />
                            </div>
                            <div className="row">
                                {/* { !data.is_local && 
                                    <>
                                        <div className="form-group mb-3 col-md-6">
                                            <label>{TR(lang, "table.customsPr")}</label>
                                            <div className="input-group">
                                                <input
                                                    value={data[`c_price_${data.c_price_ccy.toLowerCase()}`]}
                                                    onChange={(e) => handleChangeValue(e.target.value, 'c_price')}
                                                    className="form-control" 
                                                    aria-label="Text input with dropdown button"
                                                    required
                                                    />
                                                <select
                                                    defaultValue={"option"}
                                                    className="form-select"
                                                    value={data.c_price_ccy}
                                                    onChange={(e) => handleChangeCcy(e.target.value, 'c_price')}
                                                >
                                                    {priceOptions.map(ccy => {
                                                        return <option key = {ccy.value} value={ccy.value}>{ccy.label}</option>
                                                    })}
                                                </select>
                                            </div>
                                            <div className='mt-2 me-3 d-inline-block'>
                                                <p className='d-inline-block'>usd</p>
                                                <h6 disabled className={`d-inline-block px-2`}>{c_price_loading ? '...' : NumberToStr(data.c_price_usd)}</h6> 
                                            </div>
                                            <div className='mt-2 me-4 d-inline-block'>
                                                <p className='d-inline-block'>eur</p>
                                                <h6 disabled className={`d-inline-block px-2`}>{c_price_loading ? '...' : NumberToStr(data.c_price_eur)}</h6> 
                                            </div>
                                            <div className='mt-2 me-4 d-inline-block'>
                                                <p className='d-inline-block'>rub</p>
                                                <h6 disabled className={`d-inline-block px-2`}>{c_price_loading ? '...' : NumberToStr(data.c_price_rub)}</h6> 
                                            </div>
                                            <div className='mt-2 me-4 d-inline-block'>
                                                <p className='d-inline-block'>uzs</p>
                                                <h6 disabled className={`d-inline-block px-2`}>{c_price_loading ? '...' : NumberToStr(data.c_price_uzs)}</h6> 
                                            </div>
                                            <div className='d-inline-block'>
                                                <p className='d-inline-block' style={{fontSize: '13px', fontWeight: 500}}>{TR(lang, "cruds.currVal")} : </p>
                                                <h6 disabled className={`d-inline-block px-2`}>{date}</h6> 
                                            </div>
                                        </div>
                                    </>
                                } */}
                                
                                <div className="form-group mb-3 col-md-6">
                                    <label>{TR(lang, "table.price")}</label>
                                    <div className="input-group">
                                        <input
                                            value={data[`price_${data.price_ccy.toLowerCase()}`]}
                                            onChange={(e) => handleChangeValue(e.target.value, 'price')}
                                            className="form-control" 
                                            aria-label="Text input with dropdown button"
                                            required
                                            />
                                        <select
                                            defaultValue={"option"}
                                            className="form-select"
                                            value={data.price_ccy}
                                            onChange={(e) => handleChangeCcy(e.target.value, 'price')}
                                        >
                                            {priceOptions.map(ccy => {
                                                return <option key = {ccy.value} value={ccy.value}>{ccy.label}</option>
                                            })}
                                        </select>
                                    </div>
                                    <div className='mt-2 me-3 d-inline-block'>
                                        <p className='d-inline-block black-font'>usd</p>
                                        <h6 disabled className={`d-inline-block px-2`}>{price_loading ? '...' : NumberToStr(data.price_usd)}</h6> 
                                    </div>
                                    <div className='mt-2 me-4 d-inline-block'>
                                        <p className='d-inline-block black-font'>eur</p>
                                        <h6 disabled className={`d-inline-block px-2`}>{price_loading ? '...' : NumberToStr(data.price_eur)}</h6> 
                                    </div>
                                    <div className='mt-2 me-4 d-inline-block'>
                                        <p className='d-inline-block black-font'>rub</p>
                                        <h6 disabled className={`d-inline-block px-2`}>{price_loading ? '...' : NumberToStr(data.price_rub)}</h6> 
                                    </div>
                                    <div className='mt-2 me-4 d-inline-block'>
                                        <p className='d-inline-block black-font'>uzs</p>
                                        <h6 disabled className={`d-inline-block px-2`}>{price_loading ? '...' : NumberToStr(data.price_uzs)}</h6> 
                                    </div>
                                    <div className='d-inline-block'>
                                        <p className='d-inline-block black-font' style={{fontSize: '13px', fontWeight: 500}}>{TR(lang, "cruds.currVal")} : </p>
                                        <h6 disabled className={`d-inline-block px-2`}>{date}</h6> 
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className='d-flex my-2 float-end'>
                            <Link className='btn btn-warning w-100 text-white text-decoration-none' to='/admin/drc'>{TR(lang, "content.cancel")}</Link>
                            <button type='submit' className = "ms-3 btn btn-primary">{TR(lang, "content.save")}</button>
                        </div>
                    </form>
                </div>
            }
        </div>
    </div>
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        data: state.drc.editData,
        list: state.drc.editList,
        listLoading: state.drc.editListLoading,
        price_loading: state.drc.price_loading,
        c_price_loading: state.drc.c_price_loading,
    };
};

export default connect(mapStateToProps)(EditDrc);