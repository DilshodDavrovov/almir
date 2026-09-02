import React,{useState} from 'react';
import { connect } from 'react-redux';
import API from '../../../services/cruds/NewsService'
import Crud from '../../components/Crud/Crud';
import { TR } from '../../../utils/helpers';
import { checkRole } from '../../../utils';
import UserNews from '../../components/UserNews'
const News = ({lang, role}) => {
    const [loading, setLoading] = useState(true);
    const title = "sidebar.News";
    const [data, setData] = useState([]);
    const [delId, setDelId] = useState(null);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [limit, setLimit] = useState(25);
    const [filterStatus, setFilterStatus] = useState("active");
    const [editData, setEditData] = useState({title: "", description: "", is_active: true, deleted: false});
    const columns = [
        {
            Header: "№",
            accessor:'id'
        },
        {   
            Header: TR(lang, "table.title"),
            accessor: "title",
        }
    ];
    return <>
    {
        checkRole("1", role) ? 
        <Crud
            API = {API}
            isNews = {true}
            title = {title}
            columns = {columns}
            loading = {loading}
            setLoading = {setLoading}
            data = {data} 
            setData = {setData}
            delId = {delId} 
            setDelId = {setDelId}
            page = {page} 
            setPage = {setPage}
            totalPages = {totalPages} 
            setTotalPages = {setTotalPages}
            limit = {limit} 
            setLimit = {setLimit}
            editData = {editData} 
            setEditData = {setEditData}
            filterStatus = {filterStatus} 
            setFilterStatus = {setFilterStatus}
        />
        :  <div className='mt-4'>
            <UserNews/> 
        </div>
    }
        
    </>
}
const mapStateToProps = (state) => {
    return {
        lang: state.language.lang,
        role: state.main.userInfo ? state.main.userInfo.user_role : null
    };
};

export default connect(mapStateToProps)(News);