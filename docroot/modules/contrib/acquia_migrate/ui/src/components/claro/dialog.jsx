import React, { useEffect, useRef } from 'react';
import PropTypes from 'prop-types';

import ClaroThrobber from './throbber';
import useToggle from '../../hooks/use-toggle';

/**
 * Create a Claro style dialog, using the css found in core/drupal.dialog
 *
 * @param {string} title
 *   Modal title
 * @param {string} message
 *   Message body text.
 * @param {function} action
 *   "OK" function.
 * @param {function} cancel
 *   "X" action.
 * @param {boolean} isModal
 *   Whether this dialog should have the modal background.
 *
 * @return {ReactNode}
 *  <ClaroDialog title={title} action={action} cancel={cancel} />
 */
const ClaroDialog = ({ title, message, action, cancel, isModal }) => {
  const modalRef = useRef(null);
  const [pending, setPending] = useToggle(false);
  const handleAction = () => {
    setPending();
    action();
  };
  const handleEsc = (e) => {
    if (e.keyCode === 27) {
      cancel();
    }
  };

  useEffect(() => {
    document.addEventListener('keydown', handleEsc);
    return () => {
      document.removeEventListener('keydown', handleEsc);
    };
  }, []);

  useEffect(() => {
    if (modalRef.current) {
      modalRef.current.focus();
    }
  }, [modalRef]);

  return (
    <>
      <div tabIndex="-1" role="dialog" className="ui-dialog" ref={modalRef}>
        <div className="ui-dialog-titlebar">
          <span className="ui-dialog-title">{title}</span>
          <button
            type="button"
            title="Close"
            className="ui-dialog-titlebar-close ui-button-icon-only"
            onClick={cancel}
          >
            <span className="ui-icon ui-icon-closethick" />
            Close
          </button>
        </div>
        <div className="ui-dialog-content ui-widget-content">
          <p>{message}</p>
        </div>
        <div className="ui-dialog-buttonpane ui-widget-content">
          <div className="ui-dialog-buttonset">
            <button
              type="button"
              className="button"
              disabled={pending}
              onClick={handleAction}
            >
              {pending ? <ClaroThrobber /> : title}
            </button>
          </div>
        </div>
      </div>
      {isModal ? <div className="ui-widget-overlay" onClick={cancel} /> : null}
    </>
  );
};

export default ClaroDialog;

ClaroDialog.propTypes = {
  title: PropTypes.string.isRequired,
  message: PropTypes.string,
  action: PropTypes.func.isRequired,
  cancel: PropTypes.func.isRequired,
  isModal: PropTypes.bool,
};

ClaroDialog.defaultProps = {
  message: null,
  isModal: true,
};
