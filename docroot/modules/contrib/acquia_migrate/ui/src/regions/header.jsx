import React from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

const tag = `header`;
const selector = '.content-header .layout-container';
const el = document.querySelector(`${tag}${selector}`);
/**
 * Injects children the page element or creates a default.
 *
 * @param {element} children
 *   The components or markup to render in the header.
 * @return {ReactPortal|ReactNode}
 *   ReactPortal in the Claro header element or a fallback div.
 */
const RegionHeader = ({ children }) =>
  el
    ? ReactDOM.createPortal(children, el)
    : React.createElement(
        tag,
        { className: 'content-header' },
        <div className="layout-container">{children}</div>,
      );

export default RegionHeader;

RegionHeader.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
