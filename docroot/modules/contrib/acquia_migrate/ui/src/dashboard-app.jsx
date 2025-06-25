import React from 'react';
import { Router } from '@reach/router';
import PropTypes from 'prop-types';

import MigrationDetail from './pages/migration-detail';
import MigrationList from './pages/migration-list';
import { MigrationProvider } from './contexts/migrations';
import ClaroDialog from './components/claro/dialog';
import RegionDialog from './regions/dialog';

/**
 * Migration UI App.
 *
 * @param {string} basepath
 *   The base url of the app.
 * @param {string} basepathPreselect
 *   The base url of the module auditor app.
 * @param {string} basepathPreselect
 *   The base url of the preselect app.
 * @param {string} source
 *   The API entrypoint.
 * @return {ReactNode}
 *   <App basepath={basepath} source={source}>
 */
const App = ({ basepath, basepathModule, basepathPreselect, source }) => {
  return (
    <div className="migrate-ui">
      <MigrationProvider
        basepath={basepath}
        basepathModule={basepathModule}
        basepathPreselect={basepathPreselect}
        source={source}
      >
        <Router basepath={basepath} primary={false}>
          <MigrationList path="/*" />
          <MigrationDetail path="migration/:id/*" />
        </Router>
      </MigrationProvider>
    </div>
  );
};

export default App;

App.propTypes = {
  basepath: PropTypes.string.isRequired,
  basepathModule: PropTypes.string.isRequired,
  basepathPreselect: PropTypes.string.isRequired,
  source: PropTypes.string.isRequired,
};
