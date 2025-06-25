import React from 'react';
import PropTypes from 'prop-types';

const PreviewLive = ({ html }) => (
  <div className="migration_preview__live">
    <iframe srcDoc={html} title="Content preview" />
  </div>
);

export default PreviewLive;

PreviewLive.propTypes = {
  html: PropTypes.string.isRequired,
};
