import React, { useState } from 'react';
import {useTable} from "react-table";
import { NumberToStr, checkRole } from '../../../utils';
import { TR } from '../../../utils/helpers';
import ExportExcel from '../ExportExcel/ExportExcel';
import Loading from '../Loading';
import Pagination from '../Pagination';
import { connect } from 'react-redux';
function AnalyzeInfoTable(props) {
    const {
        data, 
        columns, 
        getList,
        columnFilter,
        columnTitles,
        handleColumnFilter, 
        date, 
        active, 
        loading, 
        lang, 
        title, 
        handleLimit, 
        gotoPage, 
        page, 
        totalPages, 
        total, 
        totalExcel, 
        limit, 
        role
    } = props;
    const {
        getTableProps,
        getTableBodyProps,
        headerGroups,
        rows,
        prepareRow,
    } = useTable({
        columns,
        data
    });
    const [dropdown, setDropdown] = useState(false);
    const [checkAll, setCheckAll] = useState(false);
    const handleCheck = (key) => {
        columnFilter[key] = !columnFilter[key];
        handleColumnFilter(columnFilter)
    }
    const handleSelectAll = () => {
        setCheckAll(!checkAll);
        for (const key in columnFilter) {
            columnFilter[key] = checkAll;
        }
        handleColumnFilter(columnFilter);
    }
    return (
        <div className="card p-0 m-0">
            <div className="card-header">
                <h4 className="card-title">{title}</h4>
                <div className='d-flex align-items-end'>
                    <div className='d-flex me-2'>
                        {
                            date.map((key, index) => (
                                <button key={index} className={`btn ${active === index?'bg-success':'bg-primary'} text-white ms-2`} onClick={()=>getList(index)}>
                                    {key.fromDate} : {key.toDate}
                                </button>
                            ))
                        }
                    </div>
                    {
                        checkRole("3", role) ?
                            <select
                                disabled={!data.length}
                                value={limit}
                                className="form-select mx-2"
                                style={{ width: "100px" }}
                                onChange={(e) => handleLimit(Number(e.target.value))}
                            >
                                {
                                    ([25, 50, 100, 500, 1000]).map(key => {
                                        if (checkRole("2", role) ||
                                            checkRole("3", role) && key <= 500
                                        ) {
                                            return <option key={key} value={key}>{key}</option>
                                        }
                                    })
                                }
                            </select>
                            : null
                    }
                    <div className="filtr_button m-0"
                        onMouseEnter={() => setDropdown(true)}
                        onMouseLeave={() => setDropdown(false)}
                    >
                        <button
                            disabled={loading ? true : false}
                            className={`btn btn-outline-primary media-btn rounded-2 hover-none me-0 ms-2`}>
                            <i className="fas fa-sliders-h me-1" />{" "}
                            <span> {TR(lang, "content.filter")}</span>
                        </button>
                        <div className={` ${(dropdown) ? 'd-block' : 'd-none'} dropdown_menu dropdown_menu_datas media-margin`}>
                            <div className='mx-0 p-1'
                                style={{ float: 'right', cursor: 'pointer' }}
                                onClick={() => handleSelectAll()} >
                                {checkAll ? TR(lang, "content.checkAll") : TR(lang, "content.clearAll")}
                            </div>
                            <div className="mt-4">
                                {Object.keys(columnFilter).map(key => (
                                    <div className='form-check form-switch' key={key}>
                                        <input type='checkbox' checked={columnFilter[key]} className='form-check-input' id={key}
                                            onChange={() => handleCheck(key)}
                                        />
                                        <label className='form-check-label text-nowrap' htmlFor={key} key={key}>
                                            {TR(lang, columnTitles[key])}
                                        </label>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                    <ExportExcel
                        totalExcel={totalExcel}
                        headerColumns={columns}
                        total={total}
                        rows={rows}
                        fileName={title}
                        loading={loading}
                    />
                </div>
            </div>
            <div className="card-body py-0 pb-1">    
                <div 
                    className="table-responsive">
                    {
                        loading ? <Loading />: 
                            <table 
                                className="modal_table table display dataTable" 
                                {...getTableProps()}
                                style={{
                                    display: 'block',
                                    height: '400px',
                                    overflowY: 'auto',
                                    overflowX: 'auto',
                                }}
                            >
                                <thead>
                                    <tr>
                                        <th>№</th>
                                        {headerGroups[0] && headerGroups[0].headers.map(column => {
                                            if(column.hide){
                                                return null;
                                            }
                                            return <th className="align-top" {...column.getHeaderProps()}>
                                                {column.render('Header')}
                                            </th>
                                        }
                                        )}
                                    </tr>
                                </thead>
                                <tbody {...getTableBodyProps()}>
                                {rows.length ? headerGroups.map((headerGroup) => (
                                <tr {...headerGroup.getHeaderGroupProps()}>
                                    <th className="allPrice__th"></th>
                                    {headerGroup.headers.map((column) => {
                                        if(column.hide){
                                            return null;
                                        }
                                        return <th
                                            className="allPrice__th"
                                            {...column.getHeaderProps({style: {minWidth: column?.minWidth || 'auto'}})}
                                        >
                                            {column.role === "name" ? TR(lang, "table.mainTurnOver")
                                            :column.role === "percent" ? "100 %"
                                            : column.role === "price" || column.role === "price_uz" || column.role === "count"
                                            ? NumberToStr( _.get(total, column.total_accessor) ) || 0
                                            : ""}
                                        </th>
                                    })}
                                </tr>
                                )):null}
                                    {rows.map((row, i) => {
                                        prepareRow(row);
                                        return (
                                            <tr {...row.getRowProps()}>
                                            <td> { (page -1) * limit + i + 1 }</td> 
                                            {row.cells.map(cell => {
                                                if(cell.column.hide){
                                                    return null;
                                                }
                                                return <td className='text-nowrap'  {...cell.getCellProps()}>{cell.render("Cell")}</td>;
                                            })}
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                    }
                </div>
                {
                    checkRole("3", role) ?
                        <Pagination
                            gotoPage={gotoPage}
                            totalPages={totalPages}
                            page={page}
                        /> : null
                }
            </div>
        </div>
    )
}

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        role: state.main.userInfo ? state.main.userInfo.user_role : null
    };
};

export default connect(mapStateToProps)(AnalyzeInfoTable);
