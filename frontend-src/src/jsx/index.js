import React, { useContext } from "react";

/// React router dom
import { Switch, Route } from "react-router-dom";

/// Css
import "./index.css";
import "./filtering.css";
import "./chart.css";
import "./step.css";
import "./theme.css";

/// Layout
import Nav from "./layouts/nav";
/// Dashboard
import Home from "./pages/Home";
/// Pages
import { ThemeContext } from "../context/ThemeContext";
import TradeMark from './pages/Cruds/TradeMark';
import TGroup from './pages/Cruds/TGroup';
import Suppliers from './pages/Cruds/Suppliers';
import Manufacturers from './pages/Cruds/Manufacturers';
import INN from './pages/Cruds/INN';
import DTypes from './pages/Cruds/DTypes';
import Distributors from './pages/Cruds/Distributors';
import DFarmGroup from './pages/Cruds/DFarmGruop';
import Countries from './pages/Cruds/Countries';
import DForms from './pages/Cruds/DForms';
import Drugs from "./pages/Cruds/Drugs/Drugs";
import Drc from './pages/Cruds/Drc/Drc';
import DistributorAnalyze from "./pages/Analyze/DistributorAnalyze";
import NewsId from "./pages/NewsId";
import ManufacturerAnalyze from "./pages/Analyze/ManufacturerAnalyze";
import CompanyAnalyze from "./pages/Analyze/CompanyAnalyze";
import User from "./pages/Cruds/User/User";
import DFarmGroupAnalyze from "./pages/Analyze/DFarmGroupAnalyze";
import TGroupAnalyze from "./pages/Analyze/TGroupAnalyze";
import InnAnalyze from "./pages/Analyze/InnAnalyze";
import TrademarkAnalyze from './pages/Analyze/TrademarkAnalyze';
import DFormAnalyze from "./pages/Analyze/DFormAnalyze";
import DrugAnalyze from "./pages/Analyze/DrugAnalyze";
import 'react-toastify/dist/ReactToastify.css';
import News from "./pages/Cruds/News";
import Activity from './components/Activity';
import Setting from "./pages/Settings";
import Search from "./pages/Search";
import Footer from "./layouts/Footer";
import { connect } from "react-redux";
import { checkRole } from "../utils";
import { ToastContainer } from "react-toastify";
import SearchNew from "./pages/SearchNew";
import Counterparty from "./pages/Cruds/Counterparty";
import Sale from "./pages/Cruds/Sale/Sale";
import Region from "./pages/Cruds/Region";
import District from "./pages/Cruds/District";
import Analytics from "./pages/Analytics/Analytics";
import Pivot from "./pages/Pivot/Pivot";

const Routes = (props) => {
  const { role } = props;
  const { menuToggle } = useContext(ThemeContext);
  let path = window.location.pathname;
  path = path.split("/");
  path = path[path.length - 1];
  return (
    <>
      <div
        id="main-wrapper"
        className={`show ${menuToggle ? "menu-toggle" : ""}`}
      >
        <Nav />

        <div className="content-body">
          <div
            className="container-fluid"
            style={{ minHeight: window.screen.height - 60 }}
          >
            <Activity />
            <Switch>
              {/* Analytics (charts + geo) and Pivot */}
              <Route path={`/analytics`}> <Analytics /> </Route>
              <Route path={`/pivot`}> <Pivot /> </Route>
              {/* Analyze */}
              <Route path={`/analyze/drugs`}> <DrugAnalyze /> </Route>
              <Route path={`/analyze/d-form`}> <DFormAnalyze /> </Route>
              <Route path={`/analyze/companies`}> <CompanyAnalyze /> </Route>
              <Route path={`/analyze/distributors`}> <DistributorAnalyze /> </Route>
              <Route path={`/analyze/manufacturers`}> <ManufacturerAnalyze /> </Route>
              <Route path={`/analyze/trademark`}> <TrademarkAnalyze /> </Route>
              <Route path={`/analyze/inn`}> <InnAnalyze /> </Route>
              <Route path={`/analyze/d-farm-groups`}> <DFarmGroupAnalyze /> </Route>
              <Route path={`/analyze/t-groups`}> <TGroupAnalyze /> </Route>
              {/* Cruds */}
              {checkRole("2", role) ? <Route path={`/admin/drc`} render={props => <Drc {...props} />} /> : null}
              {checkRole("2", role) ? <Route path={`/admin/sale`} render={props => <Sale {...props} />} /> : null}
              {checkRole("2", role) ? <Route path={`/admin/drugs`} render={props => <Drugs {...props} />} /> : null}
              {checkRole("2", role) ? <Route path={`/admin/d-forms`}> <DForms /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/countries`}> <Countries /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/regions`}> <Region /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/districts`}> <District /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/d-farm-groups`}> <DFarmGroup /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/distributors`}> <Distributors /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/d-types`}> <DTypes /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/inn`}> <INN /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/manufacturers`}> <Manufacturers /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/suppliers`}> <Suppliers /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/counterparty`}> <Counterparty /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/t-groups`}> <TGroup /> </Route> : null}
              {checkRole("2", role) ? <Route path={`/admin/trade-marks`}> <TradeMark /> </Route> : null}

              {/*<Route path={`/search`}> <Search/> </Route>*/}
              <Route path={`/search-new`}> <SearchNew /> </Route>
              {/* <Route path={`/search2`}> <Search2/> </Route> */}
              {checkRole("1", role) ? <Route path={`/profile/settings`}> <Setting /> </Route> : null}
              {checkRole("1", role) ? <Route path={`/user`} render={props => <User {...props} />} /> : null}
              <Route exact path={`/news`}> <News /> </Route>
              <Route path={`/news/:id`}><NewsId /></Route>
              {/* Dashboard */}
              <Route exact key='' path={`/`}> <Home /> </Route>

            </Switch>
          </div>
          <ToastContainer />
          <Footer />
        </div>
      </div>
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    role: state.main.userInfo ? state.main.userInfo.user_role : null
  };
};

export default connect(mapStateToProps)(Routes);
