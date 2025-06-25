import React from 'react';
import { render } from 'react-dom';

import App from './preselect-app';
import { ErrorBoundary, Try, Catch } from './errors/try-catch';
import { ErrorAlert } from './components/alerts';
import withError from './errors/with-error';

const DisplayError = withError(ErrorAlert);

document.addEventListener('DOMContentLoaded', () => {
  const domContainer = document.querySelector('#decoupled-page-root');
  const modulePath = domContainer.getAttribute('data-module-path');
  __webpack_public_path__ = `/${modulePath}/ui/dist/`;

  const basepath = domContainer.getAttribute('data-basepath');
  const source = domContainer.getAttribute('data-source');

  if (domContainer) {
    render(
      <ErrorBoundary>
        <Try>
          <App basepath={basepath} source={source} />
        </Try>
        <Catch>
          <DisplayError />
        </Catch>
      </ErrorBoundary>,
      domContainer,
    );
  }
});
