import API from '../../../services/cruds/InnService'
import Analyze from '../../components/Analyze/Analyze';
const InnAnalyze = () => {
    const title = "analyzes.mnn";
    return <Analyze
        title = {title}
        API = {API}
    />
}

export default InnAnalyze;