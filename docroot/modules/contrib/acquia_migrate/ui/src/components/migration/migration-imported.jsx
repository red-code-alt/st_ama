import React, { useState } from 'react';
import PropTypes from 'prop-types';
import {
  format,
  formatDistanceToNow,
  formatRelative,
  formatDuration,
  differenceInSeconds,
} from 'date-fns';

import { dateFormatString, parseDate, parseDuration } from '../../lib/datetime';
import useInterval from '../../hooks/use-interval';

const MigrationInProgress = ({ start }) => (
  // This is not important to update since it shows a relative time.
  <span>
    started {formatRelative(start, Date.now())}
    <br />
    still in progress
  </span>
);

const MigrationCompleted = ({ startTime, endTime, start, end, duration }) => {
  const [visibility, setVisibility] = useState(false);
  const [currentTime, setCurrentTime] = useState(Date.now());
  const startAgo = formatDistanceToNow(start, {
    addSuffix: true,
    includeSeconds: true,
  });

  const startLocal = format(start, dateFormatString);
  const endLocal = format(end, dateFormatString);
  const startRelative = formatRelative(start, currentTime);
  const interval = differenceInSeconds(currentTime, start) > 60 ? 30000 : 2000;
  const formattedDuration = formatDuration(parseDuration(duration));

  useInterval(() => setCurrentTime(Date.now()), interval);

  return (
    <div className="migration__last_imported">
      <a onClick={() => setVisibility(!visibility)} title={startRelative}>
        {startAgo}
        <br />
        took {formattedDuration || `less than a second`}
      </a>
      <div
        className={`migration__last_imported_details ${
          visibility ? 'migration__last_imported_details--is-visible' : ''
        }`}
      >
        Started: <span title={startTime}>{startLocal}</span>
        <br />
        Finished: <span title={endTime}>{endLocal}</span>
      </div>
    </div>
  );
};

const MigrationImported = ({ imported }) => {
  if (!imported) {
    return <span>Not imported</span>;
  }

  const { startTime, endTime, duration } = imported;
  const start = parseDate(startTime);

  if (!endTime) {
    return <MigrationInProgress start={start} />;
  }

  const end = parseDate(endTime);

  return (
    <MigrationCompleted
      startTime={startTime}
      endTime={endTime}
      start={start}
      end={end}
      duration={duration}
    />
  );
};

export default MigrationImported;

MigrationInProgress.propTypes = {
  start: PropTypes.instanceOf(Date).isRequired,
};

MigrationCompleted.propTypes = {
  startTime: PropTypes.string.isRequired,
  endTime: PropTypes.string.isRequired,
  start: PropTypes.instanceOf(Date).isRequired,
  end: PropTypes.instanceOf(Date).isRequired,
  duration: PropTypes.number.isRequired,
};

MigrationImported.propTypes = {
  imported: PropTypes.shape({
    startTime: PropTypes.string,
    endTime: PropTypes.string,
    duration: PropTypes.number,
  }),
};

MigrationImported.defaultProps = {
  imported: null,
};
