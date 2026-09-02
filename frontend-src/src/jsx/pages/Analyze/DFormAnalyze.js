import API from '../../../services/cruds/DFormService'
import Analyze from '../../components/Analyze/Analyze';
const DFormAnalyze = () => {
    const title = "analyzes.df";
    return <Analyze
        title = {title}
        API = {API}
    />
}

export default DFormAnalyze;