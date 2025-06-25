import React from 'react';
import PropTypes from 'prop-types';

const ClaroSelect = ({
  options,
  name,
  label,
  value,
  multiple = false,
  children,
  onChange,
}) => (
  <div
    className={`form-item form-type--select form-item--select-${
      multiple ? 'multiple' : 'single'
    }`}
  >
    {label && (
      <label htmlFor={name} className="form-item__label">
        {label}
      </label>
    )}
    <select
      name={name}
      className={`form-select form-element form-element--type-${
        multiple ? 'select-multiple' : 'select'
      }`}
      onChange={onChange}
      value={value}
      {...(multiple ? { multiple } : undefined)}
      {...(children
        ? { 'aria-describedby': `${name}--description` }
        : undefined)}
    >
      <option value="">--</option>
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
    {children && (
      <div id={`${name}--description`} className="form-item__description">
        {children}
      </div>
    )}
  </div>
);

export default ClaroSelect;

ClaroSelect.propTypes = {
  options: PropTypes.arrayOf(
    PropTypes.shape({
      label: PropTypes.string.isRequired,
      value: PropTypes.string.isRequired,
    }),
  ),
  name: PropTypes.string.isRequired,
  label: PropTypes.string,
  value: PropTypes.string.isRequired,
  multiple: PropTypes.bool,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]),
  onChange: PropTypes.func.isRequired,
};

ClaroSelect.defaultProps = {
  label: null,
  multiple: false,
  options: [],
  children: null,
};
