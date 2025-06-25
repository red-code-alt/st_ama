import React from 'react';
import PropTypes from 'prop-types';

const ClaroThrobber = ({ message }) => (
  <div className="ajax-progress ajax-progress--throbber" role="status">
    <div className="ajax-progress__throbber" />
    {message && <span className="ajax-progress__message">{message}</span>}
  </div>
);

export default ClaroThrobber;

ClaroThrobber.propTypes = {
  message: PropTypes.oneOfType([PropTypes.string, PropTypes.node]),
};

ClaroThrobber.defaultProps = {
  message: null,
};
