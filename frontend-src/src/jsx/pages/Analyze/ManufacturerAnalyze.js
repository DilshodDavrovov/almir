import API from '../../../services/cruds/ManufacturerService'
import Analyze from '../../components/Analyze/Analyze';
const ManufacturerAnalyze = () => {
    const title = "analyzes.mf";
    return <Analyze
        title = {title}
        API = {API}
    />
}

export default ManufacturerAnalyze;