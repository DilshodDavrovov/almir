import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import CrudTable from '../../components/CrudTable/CrudTable';
import DeleteModal from '../../components/Modals/DeleteModal';
import CreateModal from '../../components/Modals/CreateModal';
import EditModal from '../../components/Modals/EditModal';
import CreateCountryModal from '../../components/Modals/CreateCountryModal';
import EditCountryModal from '../../components/Modals/EditCountryModal';
import CreateManufacturerModal from '../../components/Modals/CreateManufacturerModal';
import EditManufacturerModal from '../../components/Modals/EditManufacturerModal';
import { Form, Modal, Button } from "react-bootstrap";
import { TR } from '../../../utils/helpers';
import { showToast } from '../../../utils';
import StatusModal from '../Modals/StatusModal';
import CreateNewsModal from '../Modals/CreateNewsModal';
import EditNewsModal from '../Modals/EditNewsModal';
import CreateDistModal from '../Modals/CreateDistModal';
import EditDistModal from '../Modals/EditDistModal';
import CreateDistrictModal from '../Modals/CreateDistrictModal';
import EditDistrictModal from '../Modals/EditDistrictModal';
import CreateRegionModal from '../Modals/CreateRegionModal';
import EditRegionModal from '../Modals/EditRegionModal';
import EditCounterpartyModal from '../Modals/EditCounterpartyModal';
import CreateCounterpartyModal from '../Modals/CreateCounterpartyModal';
const Crud = (props) => {
    const {
        API, lang, title, columns,
        loading, setLoading,
        data, setData,
        delId, setDelId,
        page, setPage,
        totalPages, setTotalPages,
        limit, setLimit,
        editData, setEditData,
        filterStatus, setFilterStatus,
        isCountry, isManufacturer, isNews, isDistributor, isRegion, isDistrict, isCounterparty
    } = props;
    const [sort, setSort] = useState({ key: "id", value: true });
    const [timer, setTimer] = useState(null);
    const [multiSelectLoading, setMultiSelectLoading] = useState(false);
    const [ids, setIds] = useState([]);
    const [list, setList] = useState([]);
    const [selectedList, setSelectedList] = useState([]);
    const [delModal, setDelModal] = useState(false);
    const [statusModal, setStatusModal] = useState(false);
    const [addModal, setAddModal] = useState(false);
    const [editModal, setEditModal] = useState(false);
    const [uploadModal, setUploadModal] = useState(false);
    const [selectedData, setSelectedData] = useState([]);

    const handleLimit = (limit) => {
        setLimit(limit);
        filter(page, limit, filterStatus, ids, sort)
    };
    const gotoPage = (page) => {
        setPage(page);
        filter(page, limit, filterStatus, ids, sort)
    };
    const handleFilterStatus = (filterStatus) => {
        setFilterStatus(filterStatus);
        filter(page, limit, filterStatus, ids, sort);
    }
    const handleAdd = () => {
        setAddModal(true);
    };
    const handleEdit = (item) => {
        setEditModal(true);
        setEditData(item);
    };
    const handleDelete = (item) => {
        setDelModal(true);
        setDelId({ id: item.id, deleted: item.deleted });
    };
    const handleStatusChange = (item) => {
        setStatusModal(true);
        setDelId({ id: item.id });
    };
    const handleSelect = (id) => {
        const index = selectedData.indexOf(id);
        if (index > -1) {
            let temp = selectedData;
            temp.splice(index, 1);
            setSelectedData([...temp]);
        } else {
            let temp = selectedData;
            setSelectedData([...temp, id]);
        }
    };
    const handleSelectAll = () => {
        if (data.length === selectedData.length) {
            setSelectedData([])
        } else {
            setSelectedData([...data.map(key => key.id)]);
        }
    };
    const handleUpload = () => {
        setUploadModal(true);
    }
    const handleSort = (key, value) => {
        setSort({ key, value });
        filter(page, limit, filterStatus, ids, { key, value });
    }
    const filter = (page, limit, filterStatus, dataId, sort) => {
        setLoading(true);
        API.getList(page, limit, filterStatus == "active", filterStatus == "deleted", dataId, sort).then(resp => {
            setData(resp.data.data);
            setTotalPages(resp.data.pagination.total_pages);
            setLoading(false);
        });
    }
    const handleSubmitFile = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('file', e.target[0].files[0]);
        API.uploadFile(formData).then(res => {
            if (res.status === 200) {
                setUploadModal(false);
                showToast('success', res.data.message);
            }
            getAllList();
        }).catch(err => {
            showToast('error', err.response.data.message);
        })
        setUploadModal(false);
    }
    const add = (e, data) => {
        e.preventDefault();
        API.save(data).then(res => {
            if (res.status === 200) {
                setAddModal(false);
                showToast('success', res.data.message);
            }
            getAllList();
        }).catch(err => {
            showToast('error', err.response.data.message);
        })
    };
    const changeStatus = (id) => {
        API.changeStatus(id, { is_active: filterStatus !== "active", deleted: false }).then(res => {
            showToast('success', res.data.message);
            setLoading(true);
            getAllList();
        }).catch(err => {
            showToast('error', err.response.data.message);
        });
        setStatusModal(false);
    }
    const del = (id) => {
        if (filterStatus === "deleted") {
            API.softDelete(id).then(res => {
                showToast('success', res.data.message);
                setLoading(true);
                getAllList();
            }).catch(err => {
                showToast('error', err.response.data.message);
            })
            setDelModal(false);
        } else {
            API.changeStatus(id, { is_active: false, deleted: true }).then(res => {
                showToast('success', res.data.message);
                setLoading(true);
                getAllList();
            }).catch(err => {
                showToast('error', err.response.data.message);
            })
            setDelModal(false);
        }
    }
    const edit = (e, id, data) => {
        e.preventDefault();
        API.edit(id, data).then(res => {
            showToast('success', res.data.message);
            setLoading(true)
            getAllList()
        }).catch(err => {
            showToast('error', err.response.data.message);
        })
        setEditModal(false);
    }
    const handleChangeSelect = (e) => {
        setSelectedList(e || []);
        const tempIds = e?.map(key => key.value) || [];
        setIds([...tempIds]);
        filter(page, limit, filterStatus, tempIds, sort);
    }
    const filterDb = (arr_key, API, value) => {
        setMultiSelectLoading(true);
        clearTimeout(timer);
        const newTimer = setTimeout(() => {
            API.select(filterStatus == "active", filterStatus == "deleted", value).then((res) => {
                const new_list = [];
                res.data.data.forEach(key => { if (!ids.includes(key.id)) new_list.push({ value: key.id, label: key.full_name }) })
                setList([...selectedList, ...new_list])
                setMultiSelectLoading(false)
            })
        }, 1000);
        setTimer(newTimer);
    };

    const handleBulkUpdate = (status) => {
        const val = window.confirm(`Are you sure ?`);
        if (val) {
            setLoading(true);
            let temp = {};
            temp['dataID'] = [...selectedData];
            if (status == 'active') {
                temp["is_active"] = true;
                temp["deleted"] = false
            } else if (status == 'unactive') {
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
        if (val) {
            setLoading(true);
            let temp = {};
            temp['dataID'] = [...selectedData];
            if (filterStatus === 'active' || filterStatus === 'unactive') {
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
        filter(page, limit, filterStatus, ids, sort);
    }
    useEffect(() => {
        getAllList();
    }, [])
    return <>
        <CrudTable
            API={API}
            data={data}
            columns={columns}
            title={title}
            loading={loading}
            totalPages={totalPages}
            page={page}
            limit={limit}
            sort={sort}
            handleSort={handleSort}
            filterStatus={filterStatus}
            handleFilterStatus={handleFilterStatus}
            gotoPage={gotoPage}
            handleLimit={handleLimit}
            handleAdd={handleAdd}
            handleEdit={handleEdit}
            handleDelete={handleDelete}
            handleStatusChange={handleStatusChange}
            selectedData={selectedData}
            handleSelect={handleSelect}
            handleSelectAll={handleSelectAll}
            handleUpload={handleUpload}
            multiSelectLoading={multiSelectLoading}
            ids={ids}
            list={list}
            handleChangeSelect={handleChangeSelect}
            filterDb={filterDb}
            handleBulkDelete={handleBulkDelete}
            handleBulkUpdate={handleBulkUpdate}
            isNews={isNews}
        />
        {
            isCounterparty ? <>
                <CreateCounterpartyModal
                    add={add}
                    addModal={addModal}
                    setAddModal={setAddModal}
                />
                <EditCounterpartyModal
                    API={API}
                    edit={edit}
                    editModal={editModal}
                    setEditModal={setEditModal}
                    editData={editData}
                />
            </> :
                isDistributor ? <>
                    <CreateDistModal
                        add={add}
                        addModal={addModal}
                        setAddModal={setAddModal}
                    />
                    <EditDistModal
                        API={API}
                        edit={edit}
                        editModal={editModal}
                        setEditModal={setEditModal}
                        editData={editData}
                    />
                </> : isCountry ? <>
                    <CreateCountryModal
                        add={add}
                        addModal={addModal}
                        setAddModal={setAddModal}
                    />
                    <EditCountryModal
                        API={API}
                        edit={edit}
                        editModal={editModal}
                        setEditModal={setEditModal}
                        editData={editData}
                    />
                </> : isManufacturer ? <>
                    <CreateManufacturerModal
                        add={add}
                        addModal={addModal}
                        setAddModal={setAddModal}
                    />
                    <EditManufacturerModal
                        API={API}
                        edit={edit}
                        editModal={editModal}
                        setEditModal={setEditModal}
                        editData={editData}
                    />
                </> : isNews ? <>
                    <CreateNewsModal
                        add={add}
                        addModal={addModal}
                        setAddModal={setAddModal}
                    />
                    <EditNewsModal
                        API={API}
                        edit={edit}
                        editModal={editModal}
                        setEditModal={setEditModal}
                        editData={editData}
                    />
                </> : isRegion ? <>
                    <CreateRegionModal
                        add={add}
                        addModal={addModal}
                        setAddModal={setAddModal}
                    />
                    <EditRegionModal
                        API={API}
                        edit={edit}
                        editModal={editModal}
                        setEditModal={setEditModal}
                        editData={editData}
                    />
                </> : isDistrict ? <>
                    <CreateDistrictModal
                        add={add}
                        addModal={addModal}
                        setAddModal={setAddModal}
                    />
                    <EditDistrictModal
                        API={API}
                        edit={edit}
                        editModal={editModal}
                        setEditModal={setEditModal}
                        editData={editData}
                    />
                </> : <>
                    <CreateModal
                        add={add}
                        addModal={addModal}
                        setAddModal={setAddModal}
                    />
                    <EditModal
                        API={API}
                        edit={edit}
                        editModal={editModal}
                        setEditModal={setEditModal}
                        editData={editData}
                    />
                </>
        }
        <StatusModal
            changeStatus={changeStatus}
            id={delId}
            statusModal={statusModal}
            setStatusModal={setStatusModal}
        />
        <DeleteModal
            del={del}
            delId={delId}
            delModal={delModal}
            setDelModal={setDelModal}
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
                </Modal.Body>
                <Modal.Footer>
                    <Button type="submit" variant="primary">
                        Загрузить файл
                    </Button>
                </Modal.Footer>
            </Form>
        </Modal>
    </>

}

const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(Crud);