import React from 'react';
import { render } from 'react-dom';

import App from './messages-app';

/**
 * Dynamically import the mirage server for development.
 * None of this code will be in the production bundle.
 */
if (process.env.NODE_ENV === 'development') {
  import(/* webpackChunkName: "messagesServer" */ './messages-server').then(
    ({ makeServer }) => {
      makeServer();
    },
  );
}

document.addEventListener('DOMContentLoaded', () => {
  const domContainer = document.querySelector('#decoupled-page-root');
  const modulePath = domContainer.getAttribute('data-module-path');
  __webpack_public_path__ = `/${modulePath}/ui/dist/`;
  const basepathDashboard = domContainer.getAttribute(
    'data-basepath-dashboard',
  );
  const source = domContainer.getAttribute('data-source');

  if (domContainer) {
    render(
      <App basepathDashboard={basepathDashboard} source={source} />,
      domContainer,
    );
  }
});
