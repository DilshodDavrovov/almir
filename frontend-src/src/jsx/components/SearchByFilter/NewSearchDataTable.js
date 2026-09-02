import React, { useState } from "react";
import { connect } from "react-redux";
import { useTable } from "react-table";
import { TR } from "../../../utils/helpers";
import _ from "lodash";
import Loading from "../Loading";
import { checkRole, GetDiffferens, NumberToStr } from "../../../utils";
import ExportExcel from "../ExportExcel/ExportExcel";
import Pagination from "../Pagination";
import NewSearchByIdModal from "./NewSearchByIdModal";
import NewSearchFilter from "./NewSearchFilter";
import DataTypeSwitch from "../DataTypeSwitch";
import NewSearchChartModal from "./NewSearchChartModal";
import NewSearchMapModal from "./NewSearchMapModal";
const NewSearchDataTable = (props) => {
    const [dropdown, setDropdown] = useState(false);
    const [checkAll, setCheckAll] = useState(false);
    const [graphModal, setGraphModal] = useState(false);
    const [mapModal, setMapModal] = useState(false);
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
        selectedCheckbox,
        setSelectedCheckbox,
        dataIDList,
        dataIdOptions,
        totalExcel,
        productTypes,
        dataType,
        others,
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
    /** Quick Приход/Продажа switch from the report header — same reset the old in-panel radio did. */
    const handleDataTypeChange = (t) => {
        const nextCheckbox = (t === 1 && ['region', 'district', 'sc'].includes(selectedCheckbox)) ? 'drug_name' : selectedCheckbox;
        handleSearch(date, nextCheckbox, dataIDList, dataIdOptions, t);
    };
    return (
        <>
            <div className="mb-3 mt-1" style={{ alignItems: 'top' }}>
                {
                    !loading &&
                    <div className="my-2 d-flex justify-content-between" style={{ alignItems: 'top' }}>
                        <h3 className="card-title d-flex align-items-center" style={{ fontSize: '1.55rem', fontWeight: '500' }}>
                            <DataTypeSwitch value={dataType} onChange={handleDataTypeChange} disabled={loading} />
                        </h3>
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
                            <button
                                type="button"

                                className="btn btn-outline-primary media-btn rounded-2 hover-none ms-2 me-1"
                                onClick={() => setGraphModal(true)}

                            >
                                <i className="fas fa-chart-bar" aria-hidden="true"></i>
                            </button>
                            <button
                                type="button"
                                disabled={dataType === 1 ? true : false}
                                className="btn btn-outline-primary media-btn rounded-2 hover-none ms-2 me-1"
                                onClick={() => setMapModal(true)}

                            >
                                <i className="fas fa-map" aria-hidden="true"></i>
                            </button>
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
                        <NewSearchFilter
                            API={API}
                            date={date}
                            lang={lang}
                            lastUpdateDate={lastUpdateDate}
                            toggle={toggle}
                            setToggle={setToggle}
                            api_list={api_list}
                            handleSearch={handleSearch}
                            selectedCheckbox={selectedCheckbox}
                            defaultList={defaultList}
                            dataIDList={dataIDList}
                            dataIdOptions={dataIdOptions}
                            productTypes={productTypes}
                            dataType={dataType}
                            others={others}
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
                                                                : column.role === "price" || column.role === "price_uz" || column.role === "count"
                                                                    ? NumberToStr(_.get(total, column.total_accessor)) || 0
                                                                    : column.role === "diffPrice.price" || column.role === "diffPrice.price_uz"
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
                                                        setName(row.original.name);
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
                <NewSearchByIdModal
                    API={API}
                    date={date}
                    selectedItem={selectedItem}
                    dataID={selectedItem.id}
                    dataIDList={dataIDList}
                    selectedCheckbox={selectedCheckbox}
                    show={show}
                    lang={lang}
                    title={name}
                    setShow={setShow}
                    dataType={dataType}
                    others={others}
                />
            }
            {
                !loading &&
                <NewSearchMapModal
                    API={API}
                    date={date}
                    dataIDList={dataIDList}
                    selectedCheckbox={selectedCheckbox}
                    show={mapModal}
                    setShow={setMapModal}
                    lang={lang}
                    dataType={dataType}
                    others={others}
                />
            }
            {
                !loading &&
                <NewSearchChartModal
                    API={API}
                    date={date}
                    dataIDList={dataIDList}
                    selectedCheckbox={selectedCheckbox}
                    show={graphModal}
                    setShow={setGraphModal}
                    lang={lang}
                    dataType={dataType}
                    others={others}
                />
            }
        </>
    );
};
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        lastUpdateDate: state.main.lastUpdateDate,
        role: state.main.userInfo ? state.main.userInfo.user_role : null,
        productTypes: state.main.productTypes,
    };
};

export default connect(mapStateToProps)(NewSearchDataTable);
