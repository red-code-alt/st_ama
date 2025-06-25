import React from 'react';
import PropTypes from 'prop-types';

const ClaroPanel = ({ header, padding, children }) => (
  <div className="panel">
    {header && <h3 className="panel__title">{header}</h3>}
    {padding ? <div className="panel__content">{children}</div> : children}
  </div>
);

export default ClaroPanel;

ClaroPanel.propTypes = {
  header: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]),
  padding: PropTypes.bool,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};

ClaroPanel.defaultProps = {
  header: null,
  padding: true,
};
