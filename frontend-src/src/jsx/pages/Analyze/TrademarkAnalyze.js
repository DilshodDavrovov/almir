import API from '../../../services/cruds/TradeMarkService'
import Analyze from '../../components/Analyze/Analyze';
const TrademarkAnalyze = () => {
    const title = "analyzes.td";
    return <Analyze
        title = {title}
        API = {API}
    />
}

export default TrademarkAnalyze;