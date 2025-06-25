import React from 'react';
import PropTypes from 'prop-types';

const ClaroBreadcrumb = ({ children }) => (
  <div className="region region-breadcrumb">
    <nav
      className="breadcrumb"
      role="navigation"
      aria-labelledby="system-breadcrumb"
    >
      <h2 id="system-breadcrumb" className="visually-hidden">
        Breadcrumb
      </h2>
      <ol className="breadcrumb__list">{children}</ol>
    </nav>
  </div>
);

export default ClaroBreadcrumb;

ClaroBreadcrumb.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.element),
    PropTypes.element,
  ]).isRequired,
};
