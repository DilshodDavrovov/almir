import API from '../../../services/cruds/DrugsService'
import Analyze from '../../components/Analyze/Analyze';
const DrugAnalyze = () => {
    const title = "analyzes.names";
    return <Analyze
        title = {title}
        API = {API}
    />
}

export default DrugAnalyze;