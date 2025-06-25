import React, { useContext, useEffect } from 'react';

import Icon from '../icon';
import useOffline from '../../hooks/use-offline';
import { MigrationContext } from '../../contexts/migrations';

const QueueControls = () => {
  const {
    activeStopLink,
    updateMigration,
    queue,
    isQueueRunning,
    setQueueRunning,
    setQueueClear,
    isQueueStopped,
  } = useContext(MigrationContext);

  const isActive = !!queue.length;
  const offline = useOffline();

  const toggle = () => {
    setQueueRunning();
  };

  useEffect(() => {
    // If the queue is active: auto-pause and auto-resume when regaining/losing connection.
    if (isActive) {
      if (offline) {
        toggle();
      } else {
        updateMigration({
          link: activeStopLink,
          callback: () => {
            console.log(
              'Successfully stopped after auto-pausing due to going offline.',
            );
            toggle();
          },
          onError: () => {
            console.log(
              'Failed to stop after auto-pausing due to going offline; this most likely means enough time had passed that it had automatically stopped.',
            );
            toggle();
          },
        });
      }
    }
  }, [offline]);

  const stop = () => {
    setQueueClear();
  };
  return (
    <div className="migration_info__queue_controls">
      <button
        className="button button--small"
        title="Stop and clear the queue"
        type="button"
        onClick={stop}
        disabled={isQueueStopped || !isActive || offline}
      >
        <Icon icon="square" size="16" />
      </button>
      <button
        className="button button--small"
        title={isQueueRunning ? 'Pause queue' : 'Continue queue'}
        type="button"
        disabled={!isActive || offline}
        onClick={toggle}
      >
        <Icon icon={isQueueRunning ? 'pause' : 'play'} size="16" />
      </button>
    </div>
  );
};

export default QueueControls;
