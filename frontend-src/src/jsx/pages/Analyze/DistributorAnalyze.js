import API from '../../../services/cruds/DistributorService'
import Analyze from '../../components/Analyze/Analyze';
const DistributorAnalyze = () => {
    const title = "analyzes.dist";
    return <Analyze
        title = {title}
        API = {API}
    />
}

export default DistributorAnalyze;