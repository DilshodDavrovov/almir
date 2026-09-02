import React,{useState, useEffect} from 'react';
import { connect } from 'react-redux';
import {Route, useHistory, useLocation} from 'react-router-dom';
import CrudTable from '../../../components/CrudTable/CrudTable';
import API from '../../../../services/cruds/DrugsService'
import DeleteModal from '../../../components/Modals/DeleteModal';
import EditDrugs from './EditDrugs';
import AddDrugs from './AddDrugs';
import { Form, Modal, Button} from "react-bootstrap";
import { TR } from '../../../../utils/helpers';
import { showToast } from '../../../../utils';
import StatusModal from '../../../components/Modals/StatusModal';

const Drugs = (props) => {
    const {lang, productTypes} = props;
    const history = useHistory();
    const title = "products.med";
    const {url} = props.match;
    const [loading, setLoading] = useState(true);
    const [timer, setTimer] = useState(null)
    const [multiSelectLoading, setMultiSelectLoading] = useState(false);
    const [sort, setSort] = useState({key: null, value: true});
    const [ids, setIds] = useState([]);
    const [list, setList] = useState([]);
    const [selectedList, setSelectedList] = useState([]);
    const [uploadLoading, setUploadLoading] = useState(-1);
    const [uploadErrorList, setUploadErrorList] = useState([]);
    const [data, setData] = useState([]);
    const [delId, setDelId] = useState(null);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [filterStatus, setFilterStatus] = useState("active");
    const [delModal, setDelModal] = useState(false);
    const [statusModal, setStatusModal] = useState(false);
    const [uploadModal, setUploadModal] = useState(false);
    const [selectedData, setSelectedData] = useState([]);
    const [dataCode, setDataCode] = useState('')
    const [dataCodeTimer, setDataCodeTimer] = useState(null)
    const [selectedProductTypes, setSelectedProductTypes] = useState([]);
    const [productTypeIds, setProductTypeIds] = useState([]);
    const columns = [
        {
            Header: TR(lang,"table.id"),
            accessor: '_manufacturers',
            location: 'drugs_crud',
            serverSort: "mf",
            Cell: ( {value} ) => {
                return <div>
                    {
                        value.map((key, index)=>{
                            return(
                                <p className='text-nowrap' key = {index}>({key.counter}) {key.full_name}</p>
                            )
                        })
                    }
                </div>
            }
        },
        {
            Header: TR(lang,"table.name"),
            minWidth: 300,
            serverSort: "name",
            accessor: "name",
        },
        {
            Header: "RX&OTC",
            accessor: "is_otc",
            Cell: ( {value} ) => (value) ? 'OTC' : 'RX'
        },
        {
            Header: TR(lang,"products.mnn"),
            accessor: "drug_inn.name",
        },
        {
            Header: TR(lang,"products.dfg"),
            accessor: "drug_form_group.name",
        },
        {
            Header: TR(lang,"products.tpg"),
            accessor: "drug_ts_group.name",
        },
        {
            Header: TR(lang,"products.dt"),
            accessor: "_dt.name",
        },
        {
            Header: TR(lang,"products.td"),
            accessor: "trademark.name",
        },
        {
            Header: TR(lang,"products.df"),
            accessor: "drug_form.name",
        },
    ];


    const handleChangeDataCode = (e) =>{
        setDataCode(e.target.value);
        clearTimeout(dataCodeTimer);
        const newDataCodeTimer = setTimeout(() =>{
            filter(page, limit, filterStatus, ids, sort, e.target.value, productTypeIds)
        }, 1000)
        setDataCodeTimer(newDataCodeTimer)
    }

    const handleAdd = () => {
        history.push("/admin/drugs/add");
    };
    const handleEdit = (item) => {
        history.push(`/admin/drugs/update/${item.id}`)
    };
    const handleDelete = (item) => {
        setDelModal(true);
        setDelId({id: item.id, deleted: item.deleted});
    };
    const handleStatusChange = (item) => {
        setStatusModal(true);
        setDelId({id: item.id});
    };
    const handleUpload = () => {
        setUploadModal(true);
    }
    const handleSelect = (id)=>{
        const index = selectedData.indexOf(id);
        if(index > -1){
            let temp = selectedData;
            temp.splice(index, 1);
            setSelectedData([...temp]);
        } else {
            let temp = selectedData;
            setSelectedData([...temp, id]);
        }
    };
    const handleSelectAll=()=>{
        if(data.length === selectedData.length){
            setSelectedData([])
        } else {
            setSelectedData([...data.map(key => key.id)]);
        }
    };
    const handleLimit = (limit) => {
        setLimit(limit);
        filter(page, limit, filterStatus, ids, sort, dataCode, productTypeIds)
    };
    const gotoPage = (page) => {
        setPage(page);
       filter(page, limit, filterStatus, ids, sort, dataCode, productTypeIds)
    };
    const handleFilterStatus = (filterStatus) => {
        setFilterStatus(filterStatus);
        filter(page, limit, filterStatus, ids, sort, dataCode, productTypeIds);
    }
    const filter = (page, limit, filterStatus, dataID, sort, dataCode, productTypeIds) => {
        setLoading(true);
        API.getList(page, limit, filterStatus == "active", filterStatus == "deleted", dataID, sort, dataCode, productTypeIds).then(resp => {
            setData(resp.data.data);
            setTotalPages(resp.data.pagination.total_pages);
            setLoading(false);
        });
    }

    const changeStatus = (id) => {
        API.changeStatus(id, {is_active: filterStatus !== "active", deleted: false}).then(res => {
            showToast('success', res.data.message);
            setLoading(true);
            getAllList();
        }).catch(err=>{
            showToast('error', err.response.data.message);
        });
        setStatusModal(false);
    }

    const del = (id) => {
        if(filterStatus === "deleted") {
            API.softDelete(id).then(res => {
                showToast('success', res.data.message);
                setLoading(true);
                getAllList();
            }).catch(err=>{
                showToast('error', err.response.data.message);
            })
            setDelModal(false);
        } else {
            API.changeStatus(id, {is_active: false, deleted: true}).then(res => {
                showToast('success', res.data.message);
                setLoading(true);
                getAllList();
            }).catch(err=>{
                showToast('error', err.response.data.message);
            })
            setDelModal(false);
        }
    }

    const handleSort = (key, value) => {
        setSort({key, value});
        filter(page, limit, filterStatus, ids, {key, value}, dataCode, productTypeIds);
    }

    const handleSubmitFile = (e) =>{
        setUploadLoading(0);
        e.preventDefault();
        const formData = new FormData();
        formData.append('file', e.target[0].files[0]);
        API.uploadFile(formData, setUploadLoading).then(res => {
            if (res.status === 200){
                showToast('success', res.data.message);
            }
            setUploadModal(false);
            setLoading(true);
            getAllList()
        }).catch(err => {
            showToast('error', err.response.data.message);
            const errData = err.response.data;
            if(Array.isArray(errData.error)){
                if(errData.error.length > 1000){
                    setUploadErrorList([
                        ...errData.error.slice(0, 1000),
                        {message: '.................................'}
                    ]);
                } else {
                    setUploadErrorList(errData.error.slice(0, 1000));
                }
            }
            setUploadLoading(-1);
        })
    }
    const handleChangeSelect = (e) => {
        setSelectedList(e || []);
        const tempIds = e?.map(key => key.value) || [];
        setIds([...tempIds]);
        filter(page, limit, filterStatus, tempIds, sort, dataCode, productTypeIds);
    }

    const handleChangeProductTypesSelect = (e) => {
        setSelectedProductTypes(e || []);
        const tempIds = e?.map(key => key.value) || [];
        setProductTypeIds([...tempIds]);
        filter(page, limit, filterStatus, ids, sort, dataCode, tempIds);
    }

    const filterDb = (arr_key, API, value) => {
        setMultiSelectLoading(true);
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            API.search(value).then((res)=>{
                const new_list = [];
                res.data.data.forEach(key => {if(!ids.includes(key.id)) new_list.push({value: key.id, label: key.name})})
                setList([...selectedList, ...new_list])
                setMultiSelectLoading(false)
            })
        }, 1000);
        setTimer(newTimer);
    };


    const handleBulkUpdate = (status) => {
        const val = window.confirm(`Are you sure ?`);
        if(val){
            setLoading(true);
            let temp = {};
            temp['dataID'] =  [...selectedData];
            if(status == 'active'){
                temp["is_active"] = true;
                temp["deleted"] = false
            } else if(status == 'unactive') {
                temp["is_active"] = false;
                temp["deleted"] = false
            }
            API.updateSelected(temp)
            .then(res => {
                showToast('success', res.data.message);
                setSelectedData([]);
                getAllList();
            }).catch(err => {
                setLoading(false);
                showToast('error', err.response.data.message);
            });
        }
    }
    const handleBulkDelete = () => {
        const val = window.confirm(`${TR(lang, "content.rebootAllTitle")}?`);
        if(val){
            setLoading(true);
            let temp = {};
            temp['dataID'] =  [...selectedData];
            if(filterStatus ==='active' || filterStatus ==='unactive') {
                temp["is_active"] = false;
                temp["deleted"] = true;
                API.updateSelected(temp)
                .then(res => {
                    showToast('success', res.data.message);
                    setSelectedData([]);
                    getAllList();
                }).catch(err => {
                    setLoading(false);
                    showToast('error', err.response.data.message);
                });
            } else {
                API.deleteSelected(temp)
                .then(res => {
                    showToast('success', res.data.message);
                    setSelectedData([]);
                    getAllList();
                }).catch(err => {
                    setLoading(false);
                    showToast('error', err.response.data.message);
                });
            }
            
        }
    }
    const getAllList = () => {
        filter(page, limit, filterStatus, ids, sort, dataCode, productTypeIds);
    }
    useEffect(() => {
        getAllList();
    },[])
    return (
        <>
            <Route
                key={`${url}/`}
                path={`${url}/`}
                exact
            >
                <CrudTable 
                    API = {API}
                    isDrug = {true}
                    data = {data}
                    columns = {columns}
                    title = {title}
                    loading = {loading}
                    totalPages = {totalPages}
                    page = {page}
                    limit= {limit}
                    sort = {sort}
                    handleSort = {handleSort}
                    filterStatus = {filterStatus}
                    handleFilterStatus = {handleFilterStatus}
                    gotoPage = {gotoPage}
                    handleLimit = {handleLimit}
                    handleAdd = {handleAdd}
                    handleEdit = {handleEdit}
                    handleDelete = {handleDelete}
                    handleStatusChange = {handleStatusChange}
                    handleUpload = {handleUpload}
                    selectedData = {selectedData}
                    handleSelect = {handleSelect}
                    handleSelectAll = {handleSelectAll}
                    multiSelectLoading = {multiSelectLoading}
                    ids = {ids}
                    list = {list}
                    handleChangeSelect = {handleChangeSelect}
                    filterDb = {filterDb}
                    handleBulkDelete = {handleBulkDelete}
                    handleBulkUpdate = {handleBulkUpdate}
                    dataCode = {dataCode}
                    handleChangeDataCode = {handleChangeDataCode}
                    handleChangeProductTypesSelect={handleChangeProductTypesSelect}
                    productTypes={productTypes}
                    selectedProductTypes={selectedProductTypes}
                />
                <DeleteModal
                    del = {del}
                    delId = {delId}
                    delModal = {delModal}
                    setDelModal = {setDelModal}
                />
                <StatusModal
                    changeStatus = {changeStatus}
                    id = {delId}
                    statusModal = {statusModal}
                    setStatusModal = {setStatusModal}
                />
                <Modal show={uploadModal} onHide={setUploadModal}>
                    <Modal.Header className="c-header" closeButton>
                        <Modal.Title>Импорт excel</Modal.Title>
                    </Modal.Header>
                    <Form onSubmit={e => handleSubmitFile(e)}>
                        <Modal.Body>
                            <input
                                accept=".xlsx, .xls, .csv"
                                className="px-0 chooseFile mb-3"
                                type="file"
                                required
                            />
                            {
                                uploadLoading == 0 ? <> Uploading... </> 
                                :uploadLoading == 1 ? <> Progressing... </>: <>
                                    {
                                        uploadErrorList.map((elem, index) => {
                                            return <div className='text-danger' key={index}>
                                                <span className='ms-3'>{index < 1000 ? index+1: null}.</span> {elem.message}
                                            </div>
                                        })
                                    }
                                </>
                            }
                        </Modal.Body>
                        <Modal.Footer>
                        <Button type="submit" variant="primary">
                            Загрузить файл
                        </Button>
                        </Modal.Footer>
                    </Form>
                </Modal>
            </Route>
            <Route key={`${url}/add`}  path={`${url}/add`}>
                <AddDrugs
                    filter={getAllList}
                    setLoading={setLoading}
                    {...props}
                />
            </Route>
            <Route
                key ={`${url}/update/:id`}
                path={`${url}/update/:id`}
                render={(newProps) => <EditDrugs
                    filter={getAllList}
                    loading = {loading}
                    setLoading={setLoading}
                    {...props}
                    {...newProps}
                />}
            />
        </>
    );
   
}

const mapStateToProps = (state) => {
    return {
        productTypes: state.main.productTypes,
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(Drugs);