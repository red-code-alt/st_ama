import React, { useContext, useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { formatRelative } from 'date-fns';

import Icon from '../icon';
import ClaroProgressBar from '../claro/progress-bar';
import { MigrationContext } from '../../contexts/migrations';
import { getMigrationById, progressDirection } from '../../lib/utils';
import { parseDate } from '../../lib/datetime';
import AnimatedNumber from '../animated-number';

const getProgressLabel = (operation) => {
  const ops = {
    import: 'Importing',
    rollback: 'Rolling back',
    'rollback-and-import': 'Rolling back and importing',
    refresh: 'Refreshing',
  };

  return ops.hasOwnProperty(operation) ? ops[operation] : '';
};

const getProgressStatus = (status, operation) => {
  switch (status) {
    case 'queued':
      return 'Queued';
    case 'started':
      // Refreshes often take a very short time, so immediately show the
      // appropriate label.
      if (operation === 'refresh') {
        return getProgressLabel(operation);
      }
      return 'Starting';
    case 'running':
      return getProgressLabel(operation);
    case 'paused':
      return window.navigator.onLine ? 'Paused' : 'Paused (offline)';
    case 'completed':
      return 'Completed';
    default:
      return '';
  }
};

const MigrationProgressImported = ({ imported, label, showLastImported }) => {
  if (!showLastImported) {
    return <span>{label}</span>;
  }

  if (!imported) {
    return <span>{label}</span>;
  }

  const { startTime, endTime, duration } = imported;
  if (endTime || duration) {
    return null;
  }

  const start = parseDate(startTime);

  return (
    <span>
      {label ? `${label}, ` : ''}started {formatRelative(start, Date.now())}
    </span>
  );
};

const MigrationProgressBar = ({ label, item, imported }) => {
  const { id, qid, operation, pending, status } = item;
  const { migrations, removeFromQueue } = useContext(MigrationContext);

  const migrationProgress = progressDirection(
    getMigrationById(id, migrations),
    operation,
  );

  const showLastImported = operation !== 'rollback';
  const showProgress =
    status === 'running' ||
    status === 'paused' ||
    status === 'completed' ||
    (status === 'started' && migrationProgress > 0);
  const progressLabel = getProgressStatus(status, operation);

  const progress = status === 'completed' ? 100 : migrationProgress * 100;
  const closeTitle = 'Cancel';

  const remove = () => {
    removeFromQueue(qid);
  };

  return (
    <div className="migration_progress">
      {showProgress ? (
        <ClaroProgressBar label={label} value={progress}>
          <MigrationProgressImported
            imported={imported}
            label={progressLabel}
            showLastImported={showLastImported}
          />
        </ClaroProgressBar>
      ) : (
        <div className="ajax-progress ajax-progress-bar progress--small">
          <div className="progress">
            <div className="progress__label" title={label}>
              {label}
            </div>
            <div className="progress__track"></div>
            <div className="progress__percentage">0%</div>
            <div className="progress__description">
              <span>{progressLabel}</span>
            </div>
          </div>
        </div>
      )}
      <div className="migration_progress__cancel">
        {pending ? (
          <span title="Pending response">
            <Icon icon="circle" />
          </span>
        ) : (
          <a
            rel="https://drupal.org/project/acquia_migrate#link-rel-dequeue-item"
            onClick={remove}
            title={closeTitle}
            role="button"
            aria-label={closeTitle}
          >
            <Icon icon="cancel" />
            <span className="visually-hidden">{closeTitle}</span>
          </a>
        )}
      </div>
    </div>
  );
};

export default MigrationProgressBar;

MigrationProgressImported.propTypes = {
  imported: PropTypes.shape({
    startTime: PropTypes.string,
    endTime: PropTypes.string,
    duration: PropTypes.number,
  }),
  label: PropTypes.string.isRequired,
  showLastImported: PropTypes.bool.isRequired,
};

MigrationProgressImported.defaultProps = {
  imported: null,
};

MigrationProgressBar.propTypes = {
  label: PropTypes.string,
  item: PropTypes.shape({
    id: PropTypes.string,
    qid: PropTypes.string,
    operation: PropTypes.string,
    pending: PropTypes.bool,
    status: PropTypes.string,
  }).isRequired,
  imported: PropTypes.shape({
    startTime: PropTypes.string,
    endTime: PropTypes.string,
    duration: PropTypes.number,
  }),
};

MigrationProgressBar.defaultProps = {
  label: '',
  imported: null,
};
