import React from 'react';
import PropTypes from 'prop-types';

const Selector = ({ id, options, toggle }) => {
  const { checked, disabled } = options;
  const name = `select-${encodeURI(id)}`;
  const handleChange = () => {
    toggle();
  };

  return (
    <input
      type="checkbox"
      className="form-boolean form-boolean--type-checkbox"
      name={name}
      id={name}
      onChange={handleChange}
      checked={checked}
      disabled={disabled}
    />
  );
};

export default Selector;

Selector.propTypes = {
  id: PropTypes.string.isRequired,
  options: PropTypes.objectOf(PropTypes.bool).isRequired,
  toggle: PropTypes.func.isRequired,
};
