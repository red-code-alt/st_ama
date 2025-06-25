import React from 'react';
import PropTypes from 'prop-types';

const ClaroCheckbox = ({
  name,
  checked,
  disabled,
  label,
  description,
  toggle,
}) => {
  const onChange = () => {
    toggle();
  };

  return (
    <div className="form-item form-type--checkbox form-type--boolean">
      <input
        type="checkbox"
        className="form-checkbox form-boolean form-boolean--type-checkbox"
        id={name}
        name={name}
        checked={checked}
        disabled={disabled}
        onChange={onChange}
      />
      {label ? (
        <label
          htmlFor={name}
          className={`form-item__label option ${disabled ? 'is-disabled' : ''}`}
        >
          {label}
        </label>
      ) : null}
      {description ? (
        <div
          className={`form-item__description ${disabled ? 'is-disabled' : ''}`}
        >
          {description}
        </div>
      ) : null}
    </div>
  );
};

export default ClaroCheckbox;

ClaroCheckbox.propTypes = {
  name: PropTypes.string.isRequired,
  checked: PropTypes.bool.isRequired,
  disabled: PropTypes.bool,
  label: PropTypes.string,
  description: PropTypes.oneOfType([PropTypes.string, PropTypes.node]),
  toggle: PropTypes.func.isRequired,
};

ClaroCheckbox.defaultProps = {
  label: null,
  description: null,
  disabled: false,
};
