import React, { useEffect, useState } from "react";
import { formatDateToDay, getThisYear, stringToDate } from "../../../utils";
import { TR } from "../../../utils/helpers";
import ServerSelect from '../React-Select-Server';
import ReactDatePicker from "react-datepicker";
import 'react-datepicker/dist/react-datepicker.css'
import MaskedInput from 'react-text-mask'
import { autoCorrectedDatePipe } from '../../../utils/index';

const SearchFilter = (props) => {
    const {toggle, setToggle, date, api_list, handleSearch, defaultList, dataIDList, dataIdOptions, lang, lastUpdateDate} = props;
    const [ids, setIds] = useState({...defaultList});
    const [list, setList] = useState({...defaultList});
    const [selectedList, setSelectedList] = useState({...defaultList});
    const [loading, setLoading] = useState({
        drugID: false,
        distID: false,
        companyID: false,
        mfID: false,
        dfID: false,
        dfgID: false,
        innID: false,
        dtgID: false,
        trademarkID: false,
        countryID: false,
    });
    const [datePicker, setDatePicker] = useState(date);
    const [timer, setTimer] = useState(null);
    const handleChange=(value, index, id)=>{
        const DATA = [...datePicker];
        DATA[index][id] = formatDateToDay(value);
        setDatePicker(DATA);
    }
    const handleChangeSelect = (e, key) => {
        setSelectedList(data => {
            data[key] = e || [];
            return {...data}
        })
        const tempIds = e?.map(key => key.value) || [];
        setIds(data => {
            data[key] = [...tempIds];
            return {...data}
        })
    }
    const handleAdd=()=>{
        setDatePicker([...datePicker, {fromDate:null, toDate:null}])
    }

    const handleDelete = (index)=>{
        const DATA = [];
        datePicker.forEach((key,i)=>{
            if(i !== index) DATA.push(key);
        })
        setDatePicker(DATA);
    }

    const handleClear= (index)=>{
        const DATA = [...datePicker]; 
        DATA[index] = {fromDate:null, toDate:null};
        setDatePicker([...DATA]);
    }

    const filterDb = (list_key, API, value) => {
        const new_list = [];
        setLoading(data => {
            data[list_key] = true;
            return {...data}
        });
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            if(API.name === "drug"){
                API.search(value).then((res)=>{
                    res.data.data.forEach(key => {if(!ids[list_key].includes(key.id)) new_list.push({value: key.id, label: key.name || ""})})
                    setList(data => {
                        data[list_key] = [...selectedList[list_key], ...new_list]
                        return {...data}
                    });
                    setLoading(data => { data[list_key] = false; return {...data}});
                })
            } else {
                API.select(true, false, value).then((res)=>{
                    res.data.data.forEach(key => {if(!ids[list_key].includes(key.id)) new_list.push({value: key.id, label: key.full_name || ""})})
                    setList(data => {
                        data[list_key] = [...selectedList[list_key], ...new_list]
                        return {...data}
                    });
                    setLoading(data => { data[list_key] = false; return {...data}});
                })
            }
        }, 1000)
        setTimer(newTimer);
    };
    useEffect(() => {
        if(toggle){
            setIds({...dataIDList});
            setList({...dataIdOptions});
            setDatePicker(date);
            setSelectedList({...dataIdOptions});
        } else {
            setSelectedList({...defaultList});
            setIds({...defaultList});
            setList({...defaultList});
            setDatePicker([getThisYear(lastUpdateDate)]);
        }
    },[toggle])
    return (
        <>
            <div className={`sidebar-right media-width ${toggle ? "show" : ""}`}>
                <div className="bg-overlay" onClick={() => setToggle(!toggle)}></div>
                <div className="sidebar-right-inner media-width p-4" style={{overflowY: "auto", maxHeight: "600px"}}>
                    <div className="row">
                        {
                            api_list.map(element => {
                                return (
                                    <div key = {element.key} className="col-md-6">
                                        <h6>{element.title}</h6>
                                        <ServerSelect
                                            API = {element.api}
                                            arr_key={element.key}
                                            options = {list[element.key]}
                                            onChange = {e => handleChangeSelect(e, element.key)}
                                            isMulti
                                            value = {list[element.key].filter(key => ids[element.key].includes(key.value))}
                                            isLoading = {loading[element.key]}
                                            filterDb = {filterDb}
                                            required
                                        />
                                    </div>
                                )
                            })
                        }
                    </div>
                    <div className='my-2'>
                        {
                            datePicker.map((key,index)=>
                                <div key={index} className='my-3'>
                                    <h6 className='text-nowrap me-2'> {index+1} -  {TR(lang,"content.period")}:</h6>
                                    <div className="d-flex">

                                        <ReactDatePicker
                                            showYearDropdown
                                            showMonthDropdown
                                            dropdownMode="select"
                                            className="form-control form-control-sm"
                                            onSelect = {e => handleChange(e,index,'fromDate')}
                                            onChange = {e => handleChange(e,index,'fromDate')}
                                            maxDate = {key.toDate ? stringToDate(key.toDate, 'dd-mm-yyyy', '-'):null}
                                            selected = {key.fromDate?stringToDate(key.fromDate, 'dd-mm-yyyy', '-'):null}
                                            customInput = {<MaskedInput
                                                pipe={autoCorrectedDatePipe}
                                                mask={[/\d/, /\d/, '/', /\d/, /\d/, '/', /\d/, /\d/, /\d/, /\d/]}
                                                keepCharPositions= {true}
                                                guide = {true}
                                            />}
                                            placeholderText='__/__/____'
                                            dateFormat='dd/MM/yyyy'
                                            required
                                        />
                                        <ReactDatePicker
                                            showYearDropdown
                                            showMonthDropdown
                                            dropdownMode="select"
                                            className="form-control form-control-sm"
                                            onSelect = {e => handleChange(e, index, 'toDate')}
                                            onChange = {e => handleChange(e,index,'toDate')}
                                            minDate = {key.fromDate?stringToDate(key.fromDate, 'dd-mm-yyyy', '-'):null}
                                            selected = {key.toDate ? stringToDate(key.toDate, 'dd-mm-yyyy', '-'):null}
                                            customInput = {<MaskedInput
                                                pipe={autoCorrectedDatePipe}
                                                mask={[/\d/, /\d/, '/', /\d/, /\d/, '/', /\d/, /\d/, /\d/, /\d/]}
                                                keepCharPositions= {true}
                                                guide = {true}
                                            />}
                                            placeholderText='__/__/____'
                                            dateFormat='dd/MM/yyyy'
                                            required
                                        />
                                        <div className="d-flex align-items-start flex-column juctify-content-between filter-x">
                                        <i onClick={()=>handleClear(index)} className="fas fa-broom align-bottom d-flex align-items-center" role='button'></i>
                                        {
                                            (datePicker.length>1)?
                                            <i onClick={()=>handleDelete(index)} className="align-bottom fas fa-solid fa-xmark mt-2" role='button'></i>:""

                                        }
                                        {
                                            (index < 1 && datePicker.length-1 === index)?   
                                            <i className="cursor-pointer fas fa-plus add-plus" onClick={()=>handleAdd()}></i>:""
                                        }
                                        </div>
                                       
                                    </div>
                                </div>
                            )
                        }
                    </div>
                    <div className='d-flex justify-content-between'>
                        <button className = 'btn btn-danger media-w-btn' onClick={() => setToggle(!toggle)}> {TR(lang,"content.close")}</button>
                        <button className = 'btn btn-primary media-w-btn' onClick={() => handleSearch(datePicker, ids, list)}>{ TR(lang,"content.search")}</button>
                    </div>
                </div>
            </div>
        </>
    );
};

export default SearchFilter;
