import React, { useContext } from 'react';
import PropTypes from 'prop-types';
import { Link } from '@reach/router';

import { MigrationContext } from '../../contexts/migrations';

/**
 * Creates a link to a migration detail page.
 *
 * @param {string} id
 *   The migration ID which corresponds to its URL.
 * @param {boolean} previewable
 *   Whether this link should go directly to the migration preview.
 * @param {string} children
 *   The label for the link.
 * @return {ReactNode}
 *   <MigrationLink id={id} previewable={previewable} />
 */
const MigrationLink = ({ id, previewable, children }) => {
  const { basepath } = useContext(MigrationContext);

  return (
    <div className="migration__title">
      <Link
        title={children}
        to={`${basepath}/migration/${id}${previewable ? '/preview' : ''}`}
      >
        {children}
      </Link>
    </div>
  );
};

export default MigrationLink;

MigrationLink.propTypes = {
  id: PropTypes.string.isRequired,
  previewable: PropTypes.bool,
  children: PropTypes.string,
};

MigrationLink.defaultProps = {
  previewable: false,
  children: '',
};
