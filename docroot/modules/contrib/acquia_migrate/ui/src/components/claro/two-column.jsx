import React from 'react';
import PropTypes from 'prop-types';

const ClaroTwoColumn = ({ one, two }) => (
  <div className="layout-row clearfix">
    <div className="layout-column layout-column--half">{one}</div>
    <div className="layout-column layout-column--half">{two}</div>
  </div>
);

export default ClaroTwoColumn;

ClaroTwoColumn.propTypes = {
  one: PropTypes.oneOfType([PropTypes.arrayOf(PropTypes.node), PropTypes.node]),
  two: PropTypes.oneOfType([PropTypes.arrayOf(PropTypes.node), PropTypes.node]),
};

ClaroTwoColumn.defaultProps = {
  one: null,
  two: null,
};
