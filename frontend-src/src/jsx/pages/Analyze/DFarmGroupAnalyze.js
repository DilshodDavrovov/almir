import API from '../../../services/cruds/DFarmGroupService'
import Analyze from '../../components/Analyze/Analyze';
const DFarmGroupAnalyze = () => {
    const title = "analyzes.dfg";
    return <Analyze
        title = {title}
        API = {API}
    />
    
}

export default DFarmGroupAnalyze;