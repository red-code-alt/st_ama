import React from 'react';
import PropTypes from 'prop-types';

const ClaroBreadcrumbItem = ({ children }) => (
  <li className="breadcrumb__item">
    {React.cloneElement(children, { className: 'breadcrumb__link' })}
  </li>
);

export default ClaroBreadcrumbItem;

ClaroBreadcrumbItem.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
