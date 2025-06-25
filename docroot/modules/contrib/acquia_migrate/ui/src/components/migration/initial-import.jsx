import React from 'react';

import { Catch, ErrorBoundary, Try } from '../../errors/try-catch';
import QueueInitial from './queue-initial';
import ClientError from '../../errors/client-error';
import withError from '../../errors/with-error';
import { ErrorAlert } from '../alerts';

const DisplayError = withError(ErrorAlert);

const InitialImport = () => (
  <ErrorBoundary>
    <Try>
      <QueueInitial />
    </Try>
    <Catch prototype={ClientError}>
      <DisplayError />
    </Catch>
  </ErrorBoundary>
);

export default InitialImport;
