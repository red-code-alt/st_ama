import React from 'react';
import PropTypes from 'prop-types';

const ClaroHeader = ({ title }) => (
  <div className="region region-header">
    <div className="block-page-title-block">
      <h1 className="page-title">
        {title + (window.navigator.onLine ? '' : ' — offline')}
      </h1>
    </div>
  </div>
);

export default ClaroHeader;

ClaroHeader.propTypes = {
  title: PropTypes.string.isRequired,
};
