import React, { useState } from 'react';
import { connect } from 'react-redux';
import API from '../../../services/cruds/CountryService'
import { TR } from '../../../utils/helpers';
import Crud from '../../components/Crud/Crud';

const Countries = ({ lang }) => {
    const [loading, setLoading] = useState(true);
    const title = "products.mfc";
    const [data, setData] = useState([]);
    const [delId, setDelId] = useState(null);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [filterStatus, setFilterStatus] = useState("active");
    const [editData, setEditData] = useState({ name: "", code: "", is_active: true, deleted: false });
    const columns = [
        {
            Header: TR(lang, "table.id"),
            accessor: "id",
            serverSort: "id",
        }, {
            Header: TR(lang, "table.name"),
            accessor: "name",
            serverSort: "name",
        },
        {
            Header: TR(lang, "table.codeCity"),
            accessor: "code",
            serverSort: "code",
        }
    ];
    return <>
        <Crud
            isCountry={true}
            API={API}
            title={title}
            columns={columns}
            loading={loading}
            setLoading={setLoading}
            data={data}
            setData={setData}
            delId={delId}
            setDelId={setDelId}
            page={page}
            setPage={setPage}
            totalPages={totalPages}
            setTotalPages={setTotalPages}
            limit={limit}
            setLimit={setLimit}
            editData={editData}
            setEditData={setEditData}
            filterStatus={filterStatus}
            setFilterStatus={setFilterStatus}
        />
    </>

};
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang
    };
};

export default connect(mapStateToProps)(Countries);