import React from 'react';
import PropTypes from 'prop-types';

const ClaroNavTabs = ({ children }) => (
  <>
    <h2 id="primary-tabs-title" className="visually-hidden">
      Primary tabs
    </h2>
    <nav
      role="navigation"
      className="tabs-wrapper is-horizontal is-collapsible position-container is-horizontal-enabled"
      aria-labelledby="primary-tabs-title"
      data-drupal-nav-tabs=""
    >
      <ul
        className="tabs tabs--primary clearfix"
        data-drupal-nav-tabs-target=""
      >
        {children}
      </ul>
    </nav>
  </>
);

export default ClaroNavTabs;

ClaroNavTabs.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.element),
    PropTypes.element,
  ]).isRequired,
};
