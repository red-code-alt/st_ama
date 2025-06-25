import React, { useEffect, useContext } from 'react';
import PropTypes from 'prop-types';

import ClaroProgressBar from '../claro/progress-bar';
import useQueue from '../../hooks/use-queue';
import { MigrationContext } from '../../contexts/migrations';

const InitialItem = ({ item }) => {
  const { title, progressRatio } = item;
  const perc = progressRatio * 100;
  return (
    <ClaroProgressBar label={title} value={perc} type="full" modifier="large">
      <span>
        Please wait for this import to finish before leaving this window.
      </span>
    </ClaroProgressBar>
  );
};

const QueueInitial = () => {
  const { active, queue, addToQueue, isQueueCompleted, cleanupQueue } =
    useQueue();
  const activeID = active.qid || '';
  const { initialImportLink, setInitialImporting } =
    useContext(MigrationContext);

  useEffect(() => {
    if (initialImportLink) {
      const { title, href } = initialImportLink;
      // Initial import queue will set this false once complete.
      setInitialImporting(true);
      addToQueue({
        id: 'initialImport',
        title,
        start: { href },
        operation: 'import',
      });
    }
  }, [initialImportLink]);

  useEffect(() => {
    if (queue.length && isQueueCompleted) {
      setTimeout(() => {
        cleanupQueue();
        setInitialImporting(false);
      }, 1000);
    }
  }, [activeID]);

  useEffect(() => {
    return () => {
      // If the response throws an error the queue will not complete.
      // This is a backup to enable the app after this component unmounts.
      // If queue completes without errors this will already be false.
      setInitialImporting(false);
    };
  }, []);

  return (
    <div className="initial_import">
      {queue.map((item) => (
        <InitialItem key={item.qid} item={item} />
      ))}
    </div>
  );
};

export default QueueInitial;

InitialItem.propTypes = {
  item: PropTypes.shape({
    title: PropTypes.string,
    progressRatio: PropTypes.number,
  }).isRequired,
};
