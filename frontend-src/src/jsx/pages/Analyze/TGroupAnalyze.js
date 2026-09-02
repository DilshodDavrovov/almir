import API from '../../../services/cruds/TGroupService'
import Analyze from '../../components/Analyze/Analyze';
const TGroupAnalyze = () => {
    const title = "analyzes.tpg";
    return <Analyze
        title={title}
        API={API}
    />
}

export default TGroupAnalyze;