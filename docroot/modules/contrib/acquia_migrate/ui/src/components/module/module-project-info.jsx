import React from 'react';
import PropTypes from 'prop-types';
import { format } from 'date-fns';

import { dateFormatString } from '../../lib/datetime';

const ModuleProjectInfo = ({ projectInfo }) => {
  const { status, timestamp, reset } = projectInfo;

  return (
    <div>
      <p>{status}</p>
      {timestamp ? (
        <p>
          Last updated on <em>{format(timestamp, dateFormatString)}</em>
        </p>
      ) : null}
      {reset ? (
        <button
          className="button button--primary button--small"
          onClick={reset}
        >
          Refresh project list
        </button>
      ) : null}
    </div>
  );
};

export default ModuleProjectInfo;

ModuleProjectInfo.propTypes = {
  projectInfo: PropTypes.shape({
    status: PropTypes.string,
    timestamp: PropTypes.number,
    reset: PropTypes.func,
  }).isRequired,
};
