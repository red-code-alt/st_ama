import React from 'react';
import PropTypes from 'prop-types';

import MessagesList from './pages/messages-list';
import { MessagesProvider } from './contexts/messages';
import { LocationProvider } from './contexts/location';

/**
 * Migration UI Messages App.
 *
 * @param {string} basepath
 *   The base url of the app.
 * @param {string} basepathDashboard
 *   The base url of the dashboard app.
 * @param {string} source
 *   The API entrypoint.
 * @return {ReactNode}
 *   <App basepathDashboard={basepathDashboard} source={source}>
 */
const App = ({ basepathDashboard, source }) => {
  return (
    <div className="migrate-ui__messages">
      <LocationProvider>
        <MessagesProvider source={source} basepathDashboard={basepathDashboard}>
          <MessagesList />
        </MessagesProvider>
      </LocationProvider>
    </div>
  );
};

export default App;

App.propTypes = {
  basepathDashboard: PropTypes.string.isRequired,
  source: PropTypes.string.isRequired,
};
