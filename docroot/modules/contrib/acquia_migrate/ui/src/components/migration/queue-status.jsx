import React, { useContext } from 'react';
import PropTypes from 'prop-types';

import { MigrationContext } from '../../contexts/migrations';
import { getMigrationById, progress, progressFormat } from '../../lib/utils';

const QueueInfo = ({
  activeLabel,
  activeProgress,
  isPending,
  isRunning,
  isStopped,
}) => (
  <div>
    {isRunning && !isStopped && activeLabel ? (
      <span>
        Currently active: <em>{activeLabel}</em> ({activeProgress})
      </span>
    ) : null}
    <span>
      {!isRunning &&
        (isPending ? 'Pausing queue, waiting on response' : 'Queue paused')}
      {isStopped &&
        (isPending ? 'Stopping queue, waiting on response' : 'Queue stopped')}
      {window.navigator.onLine ? '' : ' — offline'}
    </span>
  </div>
);

const NoQueueItems = () => {
  const { controllingSession } = useContext(MigrationContext);
  if (controllingSession === true || controllingSession === null) {
    return <span>No queued migrations</span>;
  } else if (controllingSession === 'drush') {
    return <span>🤖 Drush is running migrations</span>;
  } else {
    return <span>👩‍💻 A colleague is running migrations</span>;
  }
};

const QueueStatus = () => {
  const {
    active,
    queue,
    isActivePending,
    isQueueRunning,
    isQueueStopped,
    migrations,
  } = useContext(MigrationContext);

  const { label, importedCount, totalCount } = active.hasOwnProperty('id')
    ? getMigrationById(active.id, migrations)
    : { label: null, importedCount: 0, totalCount: 0 };

  const perc = `${progressFormat(progress(importedCount, totalCount))}%`;

  return (
    <div className="migration_info__queue_status">
      {!!queue.length ? (
        <QueueInfo
          activeLabel={label}
          activeProgress={perc}
          isPending={isActivePending}
          isRunning={isQueueRunning}
          isStopped={isQueueStopped}
        />
      ) : (
        <NoQueueItems />
      )}
    </div>
  );
};

export default QueueStatus;

QueueInfo.propTypes = {
  activeLabel: PropTypes.string,
  activeProgress: PropTypes.string,
  isPending: PropTypes.bool,
  isRunning: PropTypes.bool,
  isStopped: PropTypes.bool,
};

QueueInfo.defaultProps = {
  activeLabel: null,
  activeProgress: '0%',
  isPending: false,
  isRunning: false,
  isStopped: false,
};
