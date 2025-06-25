import React from 'react';
import PropTypes from 'prop-types';

import Icon from './icon';
import ActionButton from './action-button';
import ClaroThrobber from './claro/throbber';

/**
 * Select from available buttons.
 *
 * @param {array} buttons
 *   A list of button props.
 * @param {boolean} pending
 *   Whether an action from this menu is pending.
 * @param {boolean} isOpen
 *   If this DropMenu is open.
 * @param {function} toggleOpen
 *   Toggle the DropMenu open/closed.
 *
 * @see {DropButton} for details.
 * @return {ReactNode}
 *   <DropMenu buttons={buttons} />
 */
const DropMenu = ({ buttons, pending, isOpen, toggleOpen }) => (
  <div className="dropdown dropleft">
    {pending ? (
      <ClaroThrobber />
    ) : (
      <a
        role="button"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded={isOpen}
        onClick={toggleOpen}
      >
        <Icon icon="more-vertical" />
      </a>
    )}
    <div className={`dropdown-menu ${isOpen ? 'show' : ''}`}>
      {buttons.map((button) => (
        <ActionButton
          key={button.key}
          button={button}
          className="dropdown-item"
          action={() => {
            toggleOpen();
            button.action();
          }}
        />
      ))}
    </div>
  </div>
);

export default DropMenu;

DropMenu.propTypes = {
  buttons: PropTypes.arrayOf(
    PropTypes.shape({
      key: PropTypes.string.isRequired,
      rel: PropTypes.string.isRequired,
      title: PropTypes.string.isRequired,
      action: PropTypes.func.isRequired,
    }),
  ),
  pending: PropTypes.bool,
  isOpen: PropTypes.bool.isRequired,
  toggleOpen: PropTypes.func.isRequired,
};

DropMenu.defaultProps = {
  buttons: [],
  pending: false,
};
