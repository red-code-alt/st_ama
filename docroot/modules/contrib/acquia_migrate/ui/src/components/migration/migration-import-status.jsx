import React from 'react';
import PropTypes from 'prop-types';

import AnimatedNumber from '../animated-number';

const MigrationImportStatus = ({ status, importedCount, totalCount }) => {
  const { code, message } = status;
  const prefix = 'migration__import-status migration';
  const warnings = ['IMPORTED_WARN', 'IMPORTING_ERR', 'IMPORTED_ERR'];
  const modifier = warnings.includes(code) ? `${prefix}__warning` : '';

  return (
    <span title={message} className={modifier}>
      <AnimatedNumber value={importedCount} />/
      <span className="tabular">{totalCount}</span>
    </span>
  );
};

export default MigrationImportStatus;

MigrationImportStatus.propTypes = {
  status: PropTypes.shape({
    code: PropTypes.string,
    message: PropTypes.string,
  }).isRequired,
  importedCount: PropTypes.number.isRequired,
  totalCount: PropTypes.number.isRequired,
};
