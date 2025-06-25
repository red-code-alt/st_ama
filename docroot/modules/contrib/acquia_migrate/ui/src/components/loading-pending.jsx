import React from 'react';
import PropTypes from 'prop-types';
import ClaroThrobber from './claro/throbber';

/**
 * Default display with loading message.
 *
 * @param {boolean} pending
 *   Whether the response has completed.
 * @param {string} empty
 *   The message to display if not pending and no results.
 * @return {ReactNode}
 *   <ModuleLoading pending={pending} />
 */
const LoadingPending = ({ pending, empty }) =>
  pending ? (
    <div className="loading--pending">
      <ClaroThrobber message="Loading…" />
    </div>
  ) : (
    <p className="loading--empty">
      <em>{empty}</em>
    </p>
  );

export default LoadingPending;

LoadingPending.propTypes = {
  pending: PropTypes.bool.isRequired,
  empty: PropTypes.string,
};

LoadingPending.defaultProps = {
  empty: 'No content to display.',
};
