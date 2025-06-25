import React from 'react';
import PropTypes from 'prop-types';

const ExtLink = ({ href, title, children, className }) => (
  <a href={href} title={title} aria-label={title} className={className}>
    {children}
  </a>
);

export default ExtLink;

ExtLink.propTypes = {
  href: PropTypes.string.isRequired,
  title: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
  className: PropTypes.string,
};

ExtLink.defaultProps = {
  className: '',
};
