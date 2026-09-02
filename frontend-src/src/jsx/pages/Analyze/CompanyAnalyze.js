import API from '../../../services/cruds/CompanyService'
import Analyze from '../../components/Analyze/Analyze';
const CompanyAnalyze = () => {
    const title = "analyzes.companies";
    return <Analyze
        title={title}
        API={API}
    />

}
export default CompanyAnalyze;