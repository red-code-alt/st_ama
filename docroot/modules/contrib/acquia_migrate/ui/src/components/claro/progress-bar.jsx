import React from 'react';
import PropTypes from 'prop-types';

import AnimatedNumber from '../animated-number';

const ClaroProgressBar = ({ label, value, children, type, modifier }) => {
  return (
    <div
      className={`${
        type === 'inline' ? 'ajax-progress ajax-progress-bar' : ''
      } ${modifier === 'small' ? 'progress--small' : ''}`}
    >
      <div className="progress" aria-live="polite">
        <div className="progress__label" title={label}>
          {label}
        </div>
        <div className="progress__track">
          <div className="progress__bar" style={{ width: `${value}%` }} />
        </div>
        <div className="progress__percentage">
          <AnimatedNumber value={value} />%
        </div>
        <div className="progress__description">{children}</div>
      </div>
    </div>
  );
};

export default ClaroProgressBar;

ClaroProgressBar.propTypes = {
  label: PropTypes.string.isRequired,
  value: PropTypes.number.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
  type: PropTypes.string,
  modifier: PropTypes.string,
};

ClaroProgressBar.defaultProps = {
  type: 'inline',
  modifier: 'small',
};
