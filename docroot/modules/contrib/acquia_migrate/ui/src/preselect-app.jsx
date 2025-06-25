import React from 'react';
import PropTypes from 'prop-types';

import PreselectList from './pages/preselect-list';
import RegionHeader from './regions/header';
import ClaroHeader from './components/claro/header';
import { PreselectProvider } from './contexts/preselect';

/**
 * Preselect UI App.
 *
 * @param {string} basepath
 *   The base url of the app.
 * @return {ReactNode}
 *   <App basepath={basepath} source={source}>
 */
const App = ({ basepath, source }) => (
  <PreselectProvider basepath={basepath} source={source}>
    <div className="migrate-ui">
      <RegionHeader>
        <ClaroHeader title="Select data to migrate" />
      </RegionHeader>
      <div>
        <PreselectList />
      </div>
    </div>
  </PreselectProvider>
);

export default App;

App.propTypes = {
  basepath: PropTypes.string.isRequired,
  source: PropTypes.string.isRequired,
};
