import React from 'react';
import PropTypes from 'prop-types';
import { format, parseISO } from 'date-fns';

import { dateFormatString } from '../../lib/datetime';

// Compare ids for memoization, message contents never change.
const compareMsgId = (prevMsg, nextMsg) => prevMsg.id === nextMsg.id;

const MessageCol = ({ className, children }) => (
  <div className={`message_grid__col ${className}`}>{children}</div>
);

const MessageRow = ({ message, className }) => {
  const {
    datetime,
    migration,
    plugin,
    sourceId,
    type,
    severity,
    text,
    solution,
  } = message;
  const formattedDate = format(parseISO(datetime), dateFormatString);

  return (
    <div className="message_grid">
      <MessageCol className="message_row__datetime">{formattedDate}</MessageCol>
      <MessageCol className="message_row__severity">{severity}</MessageCol>
      <MessageCol className="message_row__title">
        {migration}
        <br />
        <code>{plugin}</code>
      </MessageCol>
      <MessageCol className="message_row__source">
        <label className="form-item__label">Source ID:</label>
        <code>{sourceId}</code>
      </MessageCol>
      <MessageCol className="message_row__type">
        <label className="form-item__label">Type:</label>
        <code>{type}</code>
      </MessageCol>
      <MessageCol className="message_row__text message_row__message">
        <label className="form-item__label">Message:</label>
        <div className="message__text">{text}</div>
      </MessageCol>
      <MessageCol className="message_row__text message_row__solution">
        <label className="form-item__label">Solution:</label>
        <div className="message__text">
          {solution || 'No solution currently available'}
        </div>
      </MessageCol>
    </div>
  );
};

export default React.memo(MessageRow, compareMsgId);

MessageCol.propTypes = {
  className: PropTypes.string,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]),
};

MessageCol.defaultProps = {
  className: '',
  children: '',
};

MessageRow.propTypes = {
  message: PropTypes.shape({
    datetime: PropTypes.string,
    migration: PropTypes.string,
    plugin: PropTypes.string,
    sourceId: PropTypes.string,
    type: PropTypes.string,
    severity: PropTypes.string,
    text: PropTypes.string,
    solution: PropTypes.string,
  }),
  className: PropTypes.string,
};

MessageRow.defaultProps = {
  message: PropTypes.objectOf(null),
  className: '',
};
