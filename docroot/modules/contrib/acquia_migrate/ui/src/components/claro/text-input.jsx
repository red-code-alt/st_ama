import React from 'react';
import PropTypes from 'prop-types';

/**
 * Claro text style input with optional validation.
 *
 * @param {string} name
 *  Field name
 * @param {string} label
 *   Optional label
 * @param {string} value
 *   Field value
 * @param {string} type
 *   text|email|tel|number|search|password
 * @param {string} size
 *   Input length
 * @param {string} placeholder
 *   Input length
 * @param {object} validation
 *  HTML5 form validation attribute e.g. { pattern: '[Bb]anana' }
 * @param {node} children
 *   Field description
 * @param {function} onChange
 *   The update function
 * @return {ReactNode}
 *   <ClaroTextInput
 *     name={name}
 *     label={label}
 *     value={value}
 *     type={type}
 *     size={size}
 *     placeholder={placeholder}
 *     validation={validation}
 *     onChange={onChange}
 *   />
 */
const ClaroTextInput = ({
  name,
  label,
  value,
  type,
  size,
  placeholder,
  validation,
  children,
  onChange,
}) => (
  <div className={`form-item form-type--${type}`}>
    {label ? (
      <label htmlFor={name} className="form-item__label">
        {label}
      </label>
    ) : null}
    <input
      name={name}
      value={value}
      type={type}
      size={size}
      className={`form-element form-element--type-${type}`}
      onChange={onChange}
      placeholder={placeholder}
      {...validation}
      {...(children ? { 'aria-describedby': `${name}--description` } : null)}
    />
    {children && (
      <div id={`${name}--description`} className="form-item__description">
        {children}
      </div>
    )}
  </div>
);

export default ClaroTextInput;

ClaroTextInput.propTypes = {
  name: PropTypes.string.isRequired,
  label: PropTypes.string,
  value: PropTypes.string.isRequired,
  type: PropTypes.string,
  size: PropTypes.string,
  placeholder: PropTypes.string,
  validation: PropTypes.objectOf(PropTypes.string),
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]),
  onChange: PropTypes.func.isRequired,
};

ClaroTextInput.defaultProps = {
  label: null,
  type: 'text',
  size: '60',
  placeholder: '',
  validation: null,
  children: null,
};
