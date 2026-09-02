import React, { useState, useContext, useEffect } from 'react';
import ServerSelect from '../../../components/React-Select-Server';
import { TR } from "../../../../utils/helpers";
import { connect, useDispatch } from 'react-redux';
import Distributor from '../../../../services/cruds/DistributorService';
import CounterParty from '../../../../services/cruds/CounterPartyService';
import Drug from '../../../../services/cruds/DrugsService';
import Sale from '../../../../services/cruds/DrcService';
// import Country from '../../../../services/cruds/CountryService';
// import Region from '../../../../services/cruds/RegionService';
// import District from '../../../../services/cruds/DistrictService';
import { useHistory } from 'react-router-dom';
import { saleAddDataAction, saleAddSelectAction, saleAddSelectLoadingAction, priceLoadingAction } from '../../../../store/actions/SaleAction';
import { NumberToStr, ParseDateToString, showToast } from '../../../../utils';
import ReactDatePicker from 'react-datepicker';
function AddSale(props) {
    const [timer, setTimer] = useState(null);
    const today = new Date();
    const date = today.getDate() + '/' + (today.getMonth() + 1) + '/' + today.getFullYear();
    const dispatch = useDispatch();
    const history = useHistory();
    const priceOptions = [{ value: "USD", label: "$" }, { value: "EUR", label: "€" }, { value: "RUB", label: "₽" }, { value: "UZS", label: "SO'M" },];
    const { setLoading, filter, price_loading, c_price_loading, lang, data, list, listLoading } = props;
    const clearData = () => {
        dispatch(saleAddDataAction({
            "drug_id": "",
            "serial_number": null,
            "shelf_life": null,
            "mode_70_date": null,
            "m70d_id": null,
            "mode_40_date": new Date(),
            "m40d_id": "",
            "sc_id": "",
            "country_id": 19,
            // "region_id": "",
            // "district_id": "",
            "counterparty_id": "",
            "period_code": "",
            "c_price_ccy": "UZS",
            "c_price_ccy_rate": "",
            "c_price_uzs": "",
            "c_price_usd": "",
            "c_price_eur": "",
            "c_price_rub": "",
            "price_ccy": "UZS",
            "price_ccy_rate": "",
            "price_usd": "",
            "price_uzs": "",
            "price_eur": "",
            "price_rub": "",
            "quantity": "",
            "sum_price_usd": "",
            "sum_price_uzs": "",
            "sum_price_eur": "",
            "sum_price_rub": "",
            "is_local": false,
        }));
        dispatch(saleAddSelectAction({
            drug: [],
            distributor_40: [],
            // country: [],
            // region: [],
            // district: [],
            counterparty: []
        }));
        dispatch(saleAddSelectLoadingAction({
            drug: false,
            distributor_40: false,
            // country: false,
            // region: false,
            // district: false,
            counterparty: false
        }));
    }
    const addSale = e => {
        e.preventDefault();
        const temp_data = { ...data };
        temp_data.mode_40_date = ParseDateToString(data.mode_40_date);
        temp_data.sum_price_usd = data.price_usd * data.quantity;
        temp_data.sum_price_uzs = data.price_uzs * data.quantity;
        temp_data.sum_price_eur = data.price_eur * data.quantity;
        temp_data.sum_price_rub = data.price_usd * data.quantity;
        temp_data.data_type = 2
        if (data.is_local) {
            temp_data.c_price_ccy = "USD";
            temp_data.c_price_ccy_rate = 0;
            temp_data.c_price_usd = 0;
            temp_data.c_price_uzs = 0;
            temp_data.c_price_eur = 0;
            temp_data.c_price_rub = 0;
        }
        Sale.save(temp_data).then(res => {
            if (res.status === 200) {
                showToast('success', res.data.message);
                clearData();
                setLoading(true);
                filter();
                history.push('/admin/sale');
            }
        }).catch(err => {
            showToast('error', err.response.data.message);
        })

    };
    const handleLoading = (arr_key, value) => {
        listLoading[arr_key] = value;
        dispatch(saleAddSelectLoadingAction(listLoading));
    }
    const filterDb = (arr_key, API, value, index, additional) => {
        handleLoading(arr_key, true)
        API.select(true, false, value, additional).then((res) => {
            list[arr_key] = [...res.data.data.map(key => ({
                value: key.id,
                label: key.full_name
            }))]
            dispatch(saleAddSelectAction(list));
            handleLoading(arr_key, false)
        })
    };
    const handleChangeValue = (value, type) => {
        const ccy = data[`${type}_ccy`].toLowerCase();
        data[`${type}_${ccy}`] = value;
        dispatch(priceLoadingAction(true, type));
        dispatch(saleAddDataAction({ ...data }));
        clearTimeout(timer)
        const newTimer = setTimeout(() => {
            Sale.getCurrencyList({ ccv: value, ccy: data[`${type}_ccy`], date: data.mode_40_date }).then((res) => {
                const temp = res.data.data;
                data[`${type}_ccy_rate`] = temp[`${ccy}_price_rate`];
                data[`${type}_uzs`] = temp.uzs_price_rate;
                data[`${type}_usd`] = temp.usd_price_rate;
                data[`${type}_eur`] = temp.eur_price_rate;
                data[`${type}_rub`] = temp.rub_price_rate;
                dispatch(saleAddDataAction({ ...data }));
                dispatch(priceLoadingAction(false, type));
            })
        }, 500)
        setTimer(newTimer)
    }
    const handleChangeCcy = (value, type) => {
        const ccy = data[`${type}_ccy`].toLowerCase();
        dispatch(priceLoadingAction(true, type));
        Sale.getCurrencyList({ ccv: data[`${type}_${ccy}`], ccy: value, date: data.mode_40_date }).then((res) => {
            const temp = res.data.data;
            data[`${type}_ccy`] = value;
            data[`${type}_ccy_rate`] = temp[`${ccy}_price_rate`];
            data[`${type}_uzs`] = temp.uzs_price_rate;
            data[`${type}_usd`] = temp.usd_price_rate;
            data[`${type}_eur`] = temp.eur_price_rate;
            data[`${type}_rub`] = temp.rub_price_rate;
            dispatch(saleAddDataAction({ ...data }));
            dispatch(priceLoadingAction(false, type));
        })
    }

    return <div className="card">
        <div className="card-header">
            <h4 className="card-title">{TR(lang, "content.adding")} {TR(lang, "products.sale")} {TR(lang, "cruds.add")}</h4>
            <div className="form-check">
                <input
                    checked={data.is_local}
                    value={data.is_local}
                    onChange={() => dispatch(saleAddDataAction({ ...data, is_local: !data.is_local }))}
                    type="checkbox"
                    className="form-check-input"
                    id="isNational"
                />
                <label
                    className="form-check-label black-font"
                    htmlFor="isNational"
                >{TR(lang, "cruds.national")}</label>
            </div>
        </div>
        <div className="card-body">
            <div className="basic-form">
                <form onSubmit={(e) => addSale(e)}>
                    <div className="row">
                        <div className='form-group mb-3 col-md-6'>
                            <label className='black-font'>{TR(lang, "table.name")}</label>
                            <ServerSelect
                                style={{ width: '100%' }}
                                API={Drug}
                                arr_key='drug'
                                options={list.drug}
                                onChange={e => dispatch(saleAddDataAction({ ...data, drug_id: e.value }))}
                                value={list.drug.filter(key => key.value === data.drug_id)}
                                isLoading={listLoading.drug}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.name")}
                                required
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font' htmlFor='prixod'>Юникод</label>
                            <input
                                className='form-control'
                                id='prixod'
                                type='text'
                                placeholder='Юникод'
                                onChange={e => dispatch(saleAddDataAction({ ...data, period_code: e.target.value }))}
                                value={data.period_code}
                                required
                            />
                        </div>
                    </div>
                    <div className="row">
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font' htmlFor='mode_40_date'>{TR(lang, "cruds.date40")}</label>
                            <ReactDatePicker
                                showYearDropdown
                                dropdownMode="select"
                                className="form-control form-control-sm"
                                onSelect={e => dispatch(saleAddDataAction({ ...data, mode_40_date: e }))}
                                onChange={e => dispatch(saleAddDataAction({ ...data, mode_40_date: e }))}
                                selected={data.mode_40_date}
                                dateFormat='dd/MM/yyyy'
                                required
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font'>{TR(lang, "table.dist")} 40</label>
                            <ServerSelect
                                API={Distributor}
                                arr_key='distributor_40'
                                options={list.distributor_40}
                                onChange={e => dispatch(saleAddDataAction({ ...data, m40d_id: e.value }))}
                                value={list.distributor_40.filter(key => key.value === data.m40d_id)}
                                isLoading={listLoading.distributor_40}
                                filterDb={filterDb}
                                placeholder={`${TR(lang, "table.dist")} 40`}
                                required
                            />
                        </div>
                        {/* <div className="form-group mb-3 col-md-6">
                            <label className='black-font' htmlFor='country'>{TR(lang, "products.country")}</label>
                            <ServerSelect
                                id="country"
                                API={Country}
                                arr_key='country'
                                options={list.country}
                                onChange={e => dispatch(saleAddDataAction({ ...data, country_id: e.value }))}
                                value={list.country.filter(key => key.value === data.country_id)}
                                isLoading={listLoading.country}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.country")}
                                required
                            />
                        </div> */}
                        {/* {
                            data.country_id ?
                                <div className="form-group mb-3 col-md-6">
                                    <label className='black-font' htmlFor='region'>{TR(lang, "products.region")}</label>
                                    <ServerSelect
                                        id="region"
                                        API={Region}
                                        arr_key='region'
                                        options={list.region}
                                        onChange={e => {
                                            if (e.value !== data.region_id) {
                                                dispatch(saleAddDataAction({ ...data, district_id: null, region_id: e.value }))
                                                dispatch(saleAddSelectAction({ ...list, district: [] }));
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
                                <div className="form-group mb-3 col-md-6">
                                    <label className='black-font' htmlFor='district'>{TR(lang, "products.district")}</label>
                                    <ServerSelect
                                        id="district"
                                        API={District}
                                        arr_key='district'
                                        options={list.district}
                                        onChange={e => dispatch(saleAddDataAction({ ...data, district_id: e.value }))}
                                        value={list.district.filter(key => key.value === data.district_id)}
                                        isLoading={listLoading.district}
                                        filterDb={filterDb}
                                        additional={{ region_id: data.region_id }}
                                        placeholder={TR(lang, "products.district")}
                                    />
                                </div>
                                : null
                        } */}

                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font' htmlFor='counterparty'>{TR(lang, "products.counterparty")}</label>
                            <ServerSelect
                                id="counterparty"
                                API={CounterParty}
                                arr_key='counterparty'
                                options={list.counterparty}
                                onChange={e => dispatch(saleAddDataAction({ ...data, counterparty_id: e.value }))}
                                value={list.counterparty.filter(key => key.value === data.counterparty_id)}
                                isLoading={listLoading.counterparty}
                                filterDb={filterDb}
                                placeholder={TR(lang, "products.counterparty")}
                            />
                        </div>
                        <div className="form-group mb-3 col-md-6">
                            <label className='black-font' htmlFor='quantity'>{TR(lang, "table.qty")}</label>
                            <input
                                className='form-control'
                                id='quantity'
                                type='text'
                                placeholder={TR(lang, "table.qty")}
                                onChange={e => dispatch(saleAddDataAction({ ...data, quantity: e.target.value }))}
                                value={data.quantity}
                                required
                            />
                        </div>
                        <div className="row">
                            <div className="form-group mb-3 col-md-6">
                                <label className='black-font'>{TR(lang, "table.price")}</label>
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
                                            return <option key={ccy.value} value={ccy.value}>{ccy.label}</option>
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
                                    <p className='d-inline-block black-font' style={{ fontSize: '13px', fontWeight: 500 }}>{TR(lang, "cruds.currVal")} : </p>
                                    <h6 disabled className={`d-inline-block px-2`}>{date}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div className='d-flex my-2 float-end'>
                        <button onClick={(e) => { e.preventDefault(); clearData(); }} className="btn btn-warning">{TR(lang, "content.resetAll")}</button>
                        <button type='submit' className="ms-3 btn btn-primary">{TR(lang, "content.save")}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        data: state.sale.data,
        list: state.sale.list,
        listLoading: state.sale.listLoading,
        price_loading: state.sale.price_loading,
        c_price_loading: state.sale.c_price_loading,
    };
};

export default connect(mapStateToProps)(AddSale);