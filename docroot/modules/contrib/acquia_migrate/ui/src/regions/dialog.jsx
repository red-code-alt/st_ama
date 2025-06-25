import { useEffect, useRef } from 'react';
import ReactDOM from 'react-dom';
import PropTypes from 'prop-types';

const RegionDialog = ({ children }) => {
  const el = useRef(null);
  useEffect(() => {
    el.current = document.createElement('div');
    el.current.setAttribute('id', 'modal');
    document.body.appendChild(el.current);

    return () => {
      document.body.removeChild(el.current);
    };
  }, []);

  return el.current ? ReactDOM.createPortal(children, el.current) : null;
};

export default RegionDialog;

RegionDialog.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]),
};

RegionDialog.defaultProps = {
  children: null,
};
