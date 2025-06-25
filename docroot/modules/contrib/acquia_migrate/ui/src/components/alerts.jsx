import React from 'react';
import PropTypes from 'prop-types';

import ClaroMessage from './claro/message';
import ExtLink from './ext-link';
import APIError from '../errors/api-error';
import useToggle from '../hooks/use-toggle';
import ApplicationError from '../errors/application-error';

const APISuggestion = ({ suggestion }) => {
  const { text, link } = suggestion;
  const { href, title } = link;
  return (
    <p>
      {text}
      <br />
      <ExtLink href={href} title={title}>
        {title}
      </ExtLink>
    </p>
  );
};

const ToggleDetails = ({ children }) => {
  const [expanded, toggleFn] = useToggle(false);

  return (
    <div>
      <em>
        {expanded ? (
          <a onClick={toggleFn}>Hide developer details.</a>
        ) : (
          <a onClick={toggleFn}>Show developer details.</a>
        )}
      </em>
      {expanded ? children : null}
    </div>
  );
};

/**
 * @param {APIError} error
 * @return {ReactNode}
 *   <APIErrorDetails error={error} />
 */
const APIErrorDetails = ({ error }) => {
  const { status, reason, suggestion } = error;
  return (
    <div>
      <p>
        Status code: <em>{status}</em>
      </p>
      <p>
        Reason phrase: <em>{reason}</em>
      </p>
      {suggestion ? <APISuggestion suggestion={suggestion} /> : null}
    </div>
  );
};

/**
 * @param {APIError} props.error
 * @return {ReactNode}
 *   <APIErrorContent />
 */
const APIErrorContent = ({ error }) => {
  const message =
    (error.errors[0] || {}).detail || 'An unrecognized API error occurred.';
  const requestId = error.requestId;
  return (
    <div>
      <p>{message}</p>
      {requestId ? <p>Request ID: {requestId}</p> : null}
      <ToggleDetails>
        <APIErrorDetails {...{ error }} />
      </ToggleDetails>
    </div>
  );
};

const AppErrorContent = ({ error }) => (
  <ToggleDetails>
    <p>{error.stack}</p>
  </ToggleDetails>
);

const ErrorContent = ({ error }) => {
  if (error instanceof APIError) {
    return <APIErrorContent {...{ error }} />;
  }
  if (error instanceof ApplicationError) {
    return <AppErrorContent {...{ error }} />;
  }
  return (
    <p>
      <span>{error.description || 'An unrecognized error occurred.'}</span>
    </p>
  );
};

const Alert = ({ children, title, severity }) => (
  <ClaroMessage title={title} severity={severity}>
    {children}
  </ClaroMessage>
);

const DismissableAlert = ({ children, dismiss, title, severity }) => {
  const [dismissed, toggleDismissed] = useToggle(false);
  return !dismissed ? (
    <ClaroMessage
      title={title}
      severity={severity}
      dismiss={() => {
        toggleDismissed();
        dismiss();
      }}
    >
      {children}
    </ClaroMessage>
  ) : null;
};

const ErrorAlert = ({ error }) => {
  return (
    <Alert title={error.message || error.description} severity="error">
      <ErrorContent {...{ error }} />
    </Alert>
  );
};

const DismissableErrorAlert = ({ error, dismiss }) => (
  <DismissableAlert severity="error" title={error.message} dismiss={dismiss}>
    <ErrorContent {...{ error }} />
  </DismissableAlert>
);

export { Alert, DismissableAlert, ErrorAlert, DismissableErrorAlert };

const errorProps = {
  error: PropTypes.oneOfType([
    PropTypes.instanceOf(APIError),
    PropTypes.instanceOf(Error),
  ]).isRequired,
};

APISuggestion.propTypes = {
  suggestion: PropTypes.shape({
    text: PropTypes.string,
    link: PropTypes.shape({
      href: PropTypes.string,
      title: PropTypes.string,
    }),
  }).isRequired,
};

ToggleDetails.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

Alert.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
  title: PropTypes.string.isRequired,
  severity: PropTypes.string.isRequired,
};

DismissableAlert.propTypes = {
  children: PropTypes.element.isRequired,
  title: PropTypes.string.isRequired,
  severity: PropTypes.string.isRequired,
  dismiss: PropTypes.func.isRequired,
};

DismissableErrorAlert.propTypes = {
  error: PropTypes.oneOfType([
    PropTypes.instanceOf(APIError),
    PropTypes.instanceOf(Error),
  ]).isRequired,
  dismiss: PropTypes.func.isRequired,
};

ErrorAlert.propTypes = errorProps;
ErrorContent.propTypes = errorProps;
APIErrorContent.propTypes = errorProps;
APIErrorDetails.propTypes = errorProps;
