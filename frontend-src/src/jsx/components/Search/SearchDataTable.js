import React, { useState } from "react";
import { connect } from "react-redux";
import { useTable } from "react-table";
import { TR } from "../../../utils/helpers";
import _ from "lodash";
import Loading from "../Loading";
import { checkRole, GetDiffferens, NumberToStr } from "../../../utils";
import ExportExcel from "../ExportExcel/ExportExcel";
import Pagination from "../Pagination";
import SearchFilter from "./SearchFilter";
import SearchByIdModal from "./SearchByIdModal";
const SearchDataTable = (props) => {
    const [dropdown, setDropdown] = useState(false);
    const [checkAll, setCheckAll] = useState(false);
    const [show, setShow] = useState(false);
    const [name, setName] = useState('');
    const [selectedItem, setSelectedItem] = useState({});
    const {
        API,
        columns,
        columnFilter,
        columnTitles,
        handleColumnFilter,
        data,
        total,
        sort,
        handleSort,
        date,
        title,
        loading,
        lang,
        lastUpdateDate,
        role,
        page,
        limit,
        totalPages,
        gotoPage,
        handleLimit,
        toggle,
        setToggle,
        handleSearch,
        handleReboot,
        api_list,
        defaultList,
        dataIDList,
        dataIdOptions,
        totalExcel,
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
        <>
            <div className="mb-3 mt-5" style={{ alignItems: 'top' }}>
                {
                    !loading &&
                    <div className="my-2 d-flex justify-content-between" style={{ alignItems: 'top' }}>
                        <h3 className="card-title" style={{ fontSize: '1.55rem', fontWeight: '500' }}></h3>
                        <div className="d-flex align-items-end">
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
                                            ([25, 50, 100, 500, 1000, 5000, 10000]).map(key => {
                                                if (checkRole("2", role) ||
                                                    checkRole("3", role) && key <= 100
                                                ) {
                                                    return <option key={key} value={key}>{key}</option>
                                                }
                                            })
                                        }
                                    </select>
                                    : null
                            }

                            <button
                                onClick={() => setToggle(true)}
                                type="button"
                                className="btn btn-outline-primary media-btn rounded-2 hover-none me-2"
                            >
                                <i className="fas fa-diagnoses mr-1 me-2" aria-hidden="true"></i>
                                <span>{TR(lang, "content.period")}</span>
                            </button>
                            <button
                                type="button"

                                className="btn btn-outline-primary media-btn rounded-2 hover-none"
                                onClick={() => handleReboot()}

                            >
                                <i className="fas fa-broom  mr-1 me-2" aria-hidden="true"></i>
                                <span>{TR(lang, "content.clearData")}</span>
                            </button>
                            <div className="filtr_button m-0"
                                onMouseEnter={() => setDropdown(!!data.length)}
                                onMouseLeave={() => setDropdown(false)}
                            >
                                <button
                                    disabled={!data.length}
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
                                disabled={!data.length}
                                headerColumns={columns}
                                total={total}
                                rows={rows}
                                fileName={TR(lang, title)}
                                loading={loading}
                            />
                        </div>
                    </div>
                }
                <div className="card">
                    {
                        !loading &&
                        <SearchFilter
                            API={API}
                            date={date}
                            lang={lang}
                            lastUpdateDate={lastUpdateDate}
                            toggle={toggle}
                            setToggle={setToggle}
                            api_list={api_list}
                            handleSearch={handleSearch}
                            defaultList={defaultList}
                            dataIDList={dataIDList}
                            dataIdOptions={dataIdOptions}
                        />
                    }
                    <div className="card-header">
                        <h4 className="card-title">{TR(lang, title)}</h4>
                    </div>
                    <div className="card-body">
                        <div className="table-responsive">
                            {loading ? (
                                <Loading />
                            ) : (
                                <table className="table display dataTable" {...getTableProps()}>
                                    <thead>
                                        <tr>
                                            {headerGroups[0] && <th className="align-top text-wrap">№</th>}
                                            {headerGroups[0] && headerGroups[0].headers.map((column) => (
                                                <th className="align-top text-center" {...column.getHeaderProps()}>
                                                    {
                                                        (column.serverSort) ?
                                                            <div className="me-2 d-inline cursor-pointer"
                                                                onClick={() => handleSort(column.serverSort, column.serverSort === sort.key ? !sort.value : true, column.period)}>
                                                                {
                                                                    (column.serverSort === sort.key && column.period === sort.period) ?
                                                                        sort.value ? <i className='fa fa-chevron-down' /> : <i className='fa fa-chevron-up' />
                                                                        : <i className='fa fa-bars' />
                                                                }
                                                            </div>
                                                            : ""
                                                    }
                                                    <div className="text-wrap d-inline">
                                                        {column.render("Header")}
                                                    </div>
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody {...getTableBodyProps()}>
                                        {rows.length ? headerGroups.map((headerGroup) => (
                                            <tr {...headerGroup.getHeaderGroupProps()}>
                                                <th className="allPrice__th"></th>
                                                {headerGroup.headers.map((column) => (
                                                    <th
                                                        className="allPrice__th"
                                                        {...column.getHeaderProps({ style: { minWidth: column?.minWidth || 'auto' } })}
                                                    >
                                                        {column.role === "name" ? TR(lang, "table.mainTurnOver")
                                                            : column.role === "percent" ? "100 %"
                                                                : column.role === "price" ||  column.role === "price_uz" || column.role === "count"
                                                                    ? NumberToStr(_.get(total, column.total_accessor)) || 0
                                                                    : column.role === "diffPrice.price" ||  column.role === "diffPrice.price_uz"
                                                                        ? GetDiffferens(_.get(total, column.total_accessor) || 0, 2)
                                                                        : column.role === "diffPrice.qty"
                                                                            ? GetDiffferens(_.get(total, column.total_accessor) || 0) : ""}
                                                    </th>
                                                ))}
                                            </tr>
                                        )) : null}
                                        {rows.map((row, i) => {
                                            prepareRow(row);
                                            return (
                                                <tr
                                                    {...row.getRowProps()}
                                                    onDoubleClick={() => {
                                                        setSelectedItem(row.original);
                                                        setName(row.original.drug_name);
                                                        setShow(true);
                                                    }}
                                                >
                                                    <td> {(page - 1) * limit + i + 1}</td>
                                                    {row.cells.map((cell) => {
                                                        return (
                                                            <td className="black-font" {...cell.getCellProps()}>
                                                                {cell.render("Cell")}
                                                            </td>
                                                        );
                                                    })}
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            )}
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
            </div>
            {
                !loading &&
                <SearchByIdModal
                    API={API}
                    date={date}
                    selectedItem={selectedItem}
                    dataID={selectedItem.drug_id}
                    mfID={[selectedItem.mf_id]}
                    dataIDList={dataIDList}
                    show={show}
                    lang={lang}
                    title={name}
                    setShow={setShow}
                />
            }
        </>
    );
};
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        lastUpdateDate: state.main.lastUpdateDate,
        role: state.main.userInfo ? state.main.userInfo.user_role : null
    };
};

export default connect(mapStateToProps)(SearchDataTable);
