import React from 'react';
import PropTypes from 'prop-types';
import { Router } from '@reach/router';

import ModulesList from './pages/modules-list';
import { ModulesProvider } from './contexts/modules';

/**
 * Modules Info UI App.
 *
 * @param {string} basepath
 *   The base url of the app.
 * @param {string} basepathDashboard
 *   The base url of the migration dashboard app.
 * @param {string} source
 *   The API entrypoint.
 * @return {ReactNode}
 *   <App basepathDashboard={basepathDashboard} source={source}>
 */
const App = ({ basepath, basepathDashboard, source }) => (
  <div className="migrate-ui">
    <ModulesProvider basepathDashboard={basepathDashboard} source={source}>
      <Router basepath={basepath} primary={false}>
        <ModulesList path="/*" />
      </Router>
    </ModulesProvider>
  </div>
);

export default App;

App.propTypes = {
  basepath: PropTypes.string.isRequired,
  basepathDashboard: PropTypes.string.isRequired,
  source: PropTypes.string.isRequired,
};
