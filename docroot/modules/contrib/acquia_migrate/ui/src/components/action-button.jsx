import React from 'react';
import PropTypes from 'prop-types';

/**
 * Un-opinionated button.
 *
 * @param {string} button.title
 *   The button title.
 * @param {string} button.rel
 *   The button rel.
 * @param {string} className
 *   Optional modifier.
 * @param {function} action
 *   The function called onClick.
 * @return {ReactNode}
 *   <ActionButton button={button} className={className} action={action} />
 */
const ActionButton = ({ button, className, action }) => {
  const { title, rel } = button;
  const doAction = (e) => {
    e.preventDefault();
    action();
  };

  return (
    <a
      title={title}
      rel={rel}
      className={className}
      role="button"
      onClick={doAction}
    >
      {title}
    </a>
  );
};

export default ActionButton;

ActionButton.propTypes = {
  button: PropTypes.shape({
    title: PropTypes.string.isRequired,
    rel: PropTypes.string.isRequired,
  }).isRequired,
  className: PropTypes.string,
  action: PropTypes.func,
};

ActionButton.defaultProps = {
  className: '',
  action: () => {},
};
