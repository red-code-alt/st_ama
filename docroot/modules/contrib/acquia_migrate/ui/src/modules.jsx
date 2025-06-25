import React from 'react';
import { render } from 'react-dom';

import App from './modules-app';

document.addEventListener('DOMContentLoaded', () => {
  const domContainer = document.querySelector('#decoupled-page-root');
  const modulePath = domContainer.getAttribute('data-module-path');
  __webpack_public_path__ = `/${modulePath}/ui/dist/`;
  const basepath = domContainer.getAttribute('data-basepath');
  const basepathDashboard = domContainer.getAttribute(
    'data-basepath-dashboard',
  );
  const source = domContainer.getAttribute('data-source');

  if (domContainer) {
    render(
      <App
        basepath={basepath}
        basepathDashboard={basepathDashboard}
        source={source}
      />,
      domContainer,
    );
  }
});
