import React from 'react';
import PropTypes from 'prop-types';

const ClaroMessage = ({ title, severity, dismiss, children }) => {
  return (
    <div
      className={`messages messages-list__item messages--${severity} ${
        dismiss ? 'has-dismiss' : ''
      }`}
    >
      <div role="alert">
        <div className="messages__header">
          <h2 className="messages__title">{title}</h2>
        </div>
        <div className="messages__content">{children}</div>
      </div>
      {dismiss && (
        <button
          onClick={dismiss}
          type="button"
          className="close"
          data-dismiss="alert"
          aria-label="Close"
        >
          <span aria-hidden="true">×</span>
        </button>
      )}
    </div>
  );
};

export default ClaroMessage;

ClaroMessage.propTypes = {
  title: PropTypes.string,
  severity: PropTypes.string,
  dismiss: PropTypes.func,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]),
};

ClaroMessage.defaultProps = {
  title: '',
  severity: 'status',
  dismiss: null,
  children: '',
};
