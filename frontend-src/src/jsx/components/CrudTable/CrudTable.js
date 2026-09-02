import React from "react";
import { Link } from "react-router-dom";
import { connect } from "react-redux";
import { TR } from "../../../utils/helpers";
import Loading from "../Loading";
import ServerSelect from '../React-Select-Server';
import { useTable } from "react-table";
import ExportExcel from "../ExportExcel/ExportExcel";
import Pagination from "../Pagination";
import { checkRole } from "../../../utils";
import MultiSelect from "../MultiSelect";
import { useState } from "react";
import { FaCalendar } from 'react-icons/fa';
import '@wojtekmaj/react-daterange-picker/dist/DateRangePicker.css';
import 'react-calendar/dist/Calendar.css';
import DateRangePicker from "@wojtekmaj/react-daterange-picker";
const CrudTable = ({
    API,
    isNews,
    isDrc,
    isUser,
    isDrug,
    data,
    columns,
    columnFilter,
    columnTitles,
    title,
    loading,
    lang,
    page,
    limit,
    totalPages,
    gotoPage,
    sort,
    handleSort,
    filterStatus,
    handleFilterStatus,
    handleLimit,
    handleUpload,
    handleAdd,
    handleEdit,
    handleChangePassword,
    handleChangeAccess,
    handleDelete,
    handleStatusChange,
    selectedData,
    handleSelect,
    handleSelectAll,
    handleColumnFilter,
    multiSelectLoading,
    ids,
    list,
    handleChangeSelect,
    handleChangeProductTypesSelect,
    filterDb,
    handleBulkUpdate,
    handleBulkDelete,
    dataCode,
    drugCode,
    handleChangeDataCode,
    handleChangeDrugCode,
    dateRange,
    handleChangeDateRange,
    productTypes,
    selectedProductTypes,
    handleReboot,
    handleUpdateLog,
    handleChangeUserFilter,
    userFilterData,
    role
}) => {
    const [dropdown, setDropdown] = useState(false);
    const [dropdownDate, setDropdownDate] = useState(false);
    const [checkAll, setCheckAll] = useState(false);
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
    const handleCheck = (key) => {
        columnFilter[key] = !columnFilter[key];
        handleColumnFilter(columnFilter)
    }
    const filterSelectAll = () => {
        setCheckAll(!checkAll);
        for (const key in columnFilter) {
            columnFilter[key] = checkAll;
        }
        handleColumnFilter(columnFilter);
    }
    return (
        <div className="card mt-1">
            <div className="card-header media-header">
                <h4 className="card-title">{TR(lang, title)}</h4>

                <div className={`d-flex media-btn-wrapper`}>
                    <div className="media-column">
                        <div className="d-flex align-items-end justify-content-end media-select ">
                            {
                                isUser || isNews ? null :
                                    <div className={`d-flex align-items-end justify-content-end media-width-full`}>
                                        {
                                            isDrc || isDrug ?
                                                <MultiSelect
                                                    styles={{ width: '200px', marginLeft: "3px" }}
                                                    placeholder="products.dt"
                                                    onChange={e => handleChangeProductTypesSelect(e)}
                                                    value={selectedProductTypes}
                                                    options={productTypes}
                                                />
                                                : null
                                        }
                                        {
                                            isDrc ?
                                                <input
                                                    onChange={e => handleChangeDrugCode(e)}
                                                    value={drugCode}
                                                    className="pbtn black-font"
                                                    style={{
                                                        width: '110px',
                                                        margin: "0px 3px",
                                                        padding: "0px 2px",
                                                        textAlign: "center",
                                                        borderRadius: "10px",
                                                        border: "1px solid #ccc",
                                                    }}
                                                    type='search'
                                                    placeholder="ID"
                                                />
                                                : null
                                        }
                                        <ServerSelect
                                            styles={{ width: '200px', marginLeft: "3px" }}
                                            API={API}
                                            options={list}
                                            onChange={e => handleChangeSelect(e)}
                                            isMulti
                                            value={list.filter(key => ids.includes(key.value))}
                                            isLoading={multiSelectLoading}
                                            filterDb={filterDb}
                                            placeholder={TR(lang, "content.search_name")}
                                            required
                                        />

                                        {
                                            isDrc ?
                                                <input
                                                    onChange={e => handleChangeDataCode(e)}
                                                    value={dataCode}
                                                    className="pbtn black-font"
                                                    style={{
                                                        width: '110px',
                                                        margin: "0px 3px",
                                                        padding: "0px 2px",
                                                        textAlign: "center",
                                                        borderRadius: "10px",
                                                        border: "1px solid #ccc",
                                                    }}
                                                    type='search'
                                                    placeholder="Номер"
                                                />
                                                : null
                                        }
                                        {
                                            isDrug ?
                                                <input
                                                    onChange={e => handleChangeDataCode(e)}
                                                    value={dataCode}
                                                    className="pbtn black-font"
                                                    style={{
                                                        width: '110px',
                                                        margin: "0px 3px",
                                                        padding: "0px 2px",
                                                        textAlign: "center",
                                                        borderRadius: "10px",
                                                        border: "1px solid #ccc",
                                                    }}
                                                    type='search'
                                                    placeholder="ID"
                                                />
                                                : null
                                        }

                                    </div>
                            }
                            {
                                isUser ? <>
                                    <input
                                        onChange={e => handleChangeUserFilter("company_name", e)}
                                        value={userFilterData["company_name"]}
                                        className="pbtn black-font"
                                        style={{
                                            width: '110px',
                                            margin: "0px 3px",
                                            padding: "0px 2px",
                                            textAlign: "center",
                                            borderRadius: "10px",
                                            border: "1px solid #ccc",
                                        }}
                                        type='search'
                                        placeholder={TR(lang, "products.senders")}
                                    />
                                    <input
                                        onChange={e => handleChangeUserFilter("company_inn", e)}
                                        value={userFilterData["company_inn"]}
                                        className="pbtn black-font"
                                        style={{
                                            width: '110px',
                                            margin: "0px 3px",
                                            padding: "0px 2px",
                                            textAlign: "center",
                                            borderRadius: "10px",
                                            border: "1px solid #ccc",
                                        }}
                                        type='search'
                                        placeholder={TR(lang, "cruds.inn")}
                                    />
                                    <input
                                        onChange={e => handleChangeUserFilter("email", e)}
                                        value={userFilterData["email"]}
                                        className="pbtn black-font"
                                        style={{
                                            width: '110px',
                                            margin: "0px 3px",
                                            padding: "0px 2px",
                                            textAlign: "center",
                                            borderRadius: "10px",
                                            border: "1px solid #ccc",
                                        }}
                                        type='search'
                                        placeholder={TR(lang, "auth.email")}
                                    />
                                    <input
                                        onChange={e => handleChangeUserFilter("phone_number", e)}
                                        value={userFilterData["phone_number"]}
                                        className="pbtn black-font"
                                        style={{
                                            width: '110px',
                                            margin: "0px 3px",
                                            padding: "0px 2px",
                                            textAlign: "center",
                                            borderRadius: "10px",
                                            border: "1px solid #ccc",
                                        }}
                                        type='search'
                                        placeholder={TR(lang, "reg.phone")}
                                    />
                                </> : null
                            }

                            <select
                                value={filterStatus}
                                className="form-select pbtn form-select-sm black-font"
                                style={{
                                    marginRight: "3px",
                                    width: "110px",
                                    color: "#cccccc"
                                }}
                                onChange={(e) => handleFilterStatus(e.target.value)}
                            >
                                <option value="active">{TR(lang, "content.active")}</option>
                                <option value="unactive">{TR(lang, "content.unactive")}</option>
                                {isUser ? <option value="blocked">{TR(lang, "content.blocked")}</option> : null}
                                {checkRole("1", role) ? <option value="deleted">{TR(lang, "content.deleted")}</option> : null}
                            </select>
                            <select
                                disabled={!data.length}
                                value={limit}
                                className="form-select pbtn form-select-sm black-font"
                                style={{
                                    marginRight: "3px",
                                    width: "80px",
                                    color: "#cccccc"
                                }}
                                onChange={(e) => handleLimit(Number(e.target.value))}
                            >
                                {
                                    ([25, 50, 100, 500, 1000, 5000, 10000]).map(key => {
                                        if (checkRole("2", role) ||
                                            checkRole("3", role) && key <= 100 ||
                                            checkRole("4", role) && key <= 25
                                        ) {
                                            return <option key={key} value={key}>{key}</option>
                                        }
                                    })
                                }
                            </select>
                            {
                                isDrc ?
                                    <div className="filtr_button me-1"
                                        onMouseEnter={() => setDropdownDate(true)}
                                        onMouseLeave={() => setDropdownDate(false)}
                                    >
                                        <button
                                            title={TR(lang, "content.filter")}
                                            className={`btn btn-outline-primary media-btn rounded-2 hover-none media-btn992  media-btn ms-1`}>
                                            <i className="fas fa-calendar me-1" />{" "}
                                            {/*<span> {TR(lang, "content.filter")}</span>*/}
                                        </button>
                                        <div className={` ${(dropdownDate) ? 'd-block' : 'd-none'} dropdown_menu pt-5`}>
                                            <DateRangePicker
                                                onChange={handleChangeDateRange}
                                                value={dateRange.length == 0 ? null : dateRange}
                                                className="date_range_picker"
                                                calendarClassName="date_range_calendar"
                                                format="dd/MM/yyyy"
                                                dayPlaceholder="dd"
                                                monthPlaceholder="mm"
                                                yearPlaceholder="yyyy"
                                                maxDate={new Date('01-01-9999')}
                                                calendarIcon={FaCalendar}
                                            />
                                        </div>
                                    </div> : null
                            }
                            <div className=" d-flex align-items-center juctify-content-end media-eia">
                                {
                                    isDrc ? <button
                                        title={TR(lang, "content.dateofupdate")}
                                        type="button"
                                        className="btn btn-outline-primary rounded-2 hover-none media-btn992 media-btn me-1"
                                        onClick={() => handleUpdateLog()}
                                    >
                                        <i className="fa fa-clock-o mr-1 me-2" aria-hidden="true"></i>
                                        {/*<span>{TR(lang, "content.dateofupdate")}</span>*/}
                                    </button> : null
                                }
                                {
                                    isDrc ? <button
                                        type="button"
                                        title={TR(lang, "content.clearCache")}
                                        className="btn btn-outline-primary rounded-2 hover-none media-btn992 media-btn me-1"
                                        onClick={() => handleReboot()}

                                    >
                                        <i className="fas fa-broom  mr-1 me-2" aria-hidden="true"></i>
                                        {/*<span>{TR(lang, "content.clearCache")}</span>*/}
                                    </button> : null
                                }
                                {
                                    isUser ? null :
                                        <button
                                            type="button"
                                            title={TR(lang, "content.import")}
                                            className="btn btn-outline-primary rounded-2 hover-none media-btn992  media-btn"
                                            onClick={handleUpload}
                                        >
                                            <i className="fas fa-file-import mr-1 me-2" aria-hidden="true"></i>
                                            {/*<span> {TR(lang, "content.import")}</span>*/}
                                        </button>
                                }
                                {
                                    isDrc ?
                                        <div className="filtr_button m-0"
                                            onMouseEnter={() => setDropdown(true)}
                                            onMouseLeave={() => setDropdown(false)}
                                        >
                                            <button
                                                title={TR(lang, "content.filter")}
                                                className={`btn btn-outline-primary media-btn rounded-2 hover-none media-btn992  media-btn ms-1`}>
                                                <i className="fas fa-sliders-h me-1" />{" "}
                                                {/*<span> {TR(lang, "content.filter")}</span>*/}
                                            </button>
                                            <div className={` ${(dropdown) ? 'd-block' : 'd-none'} dropdown_menu dropdown_menu_datas media-margin`}>
                                                <div className='mx-0 p-1'
                                                    style={{ float: 'right', cursor: 'pointer' }}
                                                    onClick={() => filterSelectAll()} >
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
                                        </div> : null
                                }
                                <ExportExcel
                                    headerColumns={columns}
                                    rows={rows}
                                    fileName={TR(lang, title)}
                                    loading={loading}
                                />
                                {
                                    isUser ? null :
                                        <button
                                            onClick={handleAdd}
                                            title={TR(lang, "content.toAdd")}
                                            type="button"
                                            className="btn btn-primary rounded-2 media-btn992  media-btn"
                                        >
                                            <i className="fas fa-plus mr-1 me-2" aria-hidden='true'></i>
                                            {/*<span> {TR(lang, "content.toAdd")}</span>*/}
                                        </button>
                                }
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div className="my-3 mx-4">
                {
                    !isUser && selectedData.length ? <div style={{ float: "right" }}>
                        {
                            filterStatus !== 'active' ?
                                <button
                                    onClick={() => handleBulkUpdate('active')}
                                    className="btn btn-sm btn-success mx-1"
                                >
                                    Change Active Status
                                </button> : null}
                        {
                            filterStatus !== 'unactive' ?
                                <button
                                    onClick={() => handleBulkUpdate('unactive')}
                                    className="btn btn-sm btn-success mx-1"
                                >
                                    Change Unactive Status
                                </button> : null
                        }
                        {
                            checkRole("1", role) ?
                                <button
                                    onClick={() => handleBulkDelete()}
                                    className="btn btn-sm btn-danger mx-1"
                                >
                                    Delete
                                </button> : null
                        }

                    </div> : null
                }
            </div>

            <div className="card-body pt-0">
                <div className="w-100 table-responsive">
                    <div id="example_wrapper" className="dataTables_wrapper">
                        <table id="example" className="display w-100 dataTable" {...getTableProps()}>
                            <thead>
                                <tr>
                                    {
                                        !isUser && data.length ?
                                            <th className={`text-center border-bottom-0 tableFont`}>
                                                <input
                                                    onChange={() => handleSelectAll()}
                                                    type='checkbox'
                                                    checked={selectedData.length == data.length ? true : false}
                                                />
                                            </th>
                                            : null
                                    }
                                    {headerGroups[0] && headerGroups[0].headers.map((column) => {
                                        if (column.hide) return null;
                                        return (
                                            <th {...column.getHeaderProps({
                                                style: {
                                                    minWidth: column?.minWidth || 'auto',
                                                    whiteSpace: column?.noWrap ? 'nowrap' : 'normal'
                                                }
                                            })}>
                                                {
                                                    (column.serverSort) ?
                                                        <div className="me-2 cursor-pointer d-inline"
                                                            onClick={() => handleSort(column.serverSort, column.serverSort === sort.key ? !sort.value : true)}>
                                                            {
                                                                (column.serverSort === sort.key) ?
                                                                    sort.value ? <i className='fa fa-chevron-down' /> : <i className='fa fa-chevron-up' />
                                                                    : <i className='fa fa-bars' />
                                                            }
                                                        </div>
                                                        : ""
                                                }
                                                <div className="d-inline">
                                                    {column.render("Header")}
                                                </div>
                                            </th>
                                        )
                                    }
                                    )}
                                    <th key="content.status">{TR(lang, "content.status")}</th>
                                    <th key="content.actions">{TR(lang, "content.actions")}</th>
                                </tr>
                            </thead>

                            {loading ? (
                                <Loading />
                            ) : (
                                <tbody  {...getTableBodyProps()}>
                                    {rows.map((row, i) => {
                                        const selected = !isUser ? selectedData.indexOf(row.original.id) + 1 : 0;
                                        prepareRow(row);
                                        return (
                                            <tr
                                                className={`${selected ? 'selected' : ''}`}
                                                style={{ backgroundColor: '#000 !important' }}
                                                {...row.getRowProps()}
                                                onDoubleClick={() => { if (checkRole("2", role)) handleEdit(row.original) }}
                                            >
                                                {
                                                    !isUser ?
                                                        <td className={`text-center`}>
                                                            <input onChange={() => handleSelect(row.original.id)} type='checkbox' checked={selected ? true : false} />
                                                        </td> : null
                                                }
                                                {row.cells.map((cell) => {
                                                    if (cell.column.hide) {
                                                        return null;
                                                    }
                                                    return (
                                                        <td className="align-top tableFont" {...cell.getCellProps()}>
                                                            {cell.render("Cell")}
                                                        </td>
                                                    );
                                                })}
                                                <td onClick={() => handleStatusChange(row.original)}
                                                    key="is_active"
                                                    className="align-top black-font cursor-pointer">
                                                    <div className="d-flex align-items-center">
                                                        <i className="fa fa-circle text-success me-1" />
                                                        {row.original.is_active
                                                            ? TR(lang, "content.activeOne")
                                                            : TR(lang, "content.unactiveOne")}
                                                    </div>
                                                </td>
                                                <td key="action" className="align-top">
                                                    <div className="d-flex">
                                                        {
                                                            isUser && checkRole("1", role) ?
                                                                <Link
                                                                    to="#"
                                                                    onClick={() => handleChangeAccess(row.original)}
                                                                    className="btn btn-warning shadow btn-xs sharp me-1">
                                                                    <i className="fa fa-universal-access"></i>
                                                                </Link> : null
                                                        }
                                                        {
                                                            isUser && checkRole("1", role) ?
                                                                <Link
                                                                    to="#"
                                                                    onClick={() => handleChangePassword(row.original)}
                                                                    className="btn btn-secondary shadow btn-xs sharp  me-1">
                                                                    <i className="fa fa-key"></i>
                                                                </Link> : null
                                                        }
                                                        {
                                                            checkRole("2", role) ?
                                                                <Link
                                                                    onClick={() => handleEdit(row.original)}
                                                                    to="#"
                                                                    className="btn btn-primary shadow btn-xs sharp me-1" >
                                                                    <i className="fa fa-pencil"></i>
                                                                </Link> : null
                                                        }
                                                        {
                                                            checkRole(filterStatus === 'deleted' ? "2" : "3", role) ?
                                                                <Link
                                                                    to="#"
                                                                    onClick={() => handleDelete(row.original)}
                                                                    className="btn btn-danger shadow btn-xs sharp">
                                                                    <i className="fa fa-remove"></i>
                                                                </Link> : null
                                                        }

                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            )}

                        </table>
                    </div>
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
    );
};
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        role: state.main.userInfo ? state.main.userInfo.user_role : null
    };
};

export default connect(mapStateToProps)(CrudTable);
