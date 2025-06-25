import React, { useContext } from 'react';

import MessageRow from './message-row';
import LoadingPending from '../loading-pending';
import { MessagesContext } from '../../contexts/messages';

const MessageView = () => {
  const { messages, isLoading } = useContext(MessagesContext);
  const isLoaded = !!messages.length;

  return (
    <div className="messages__list">
      {!isLoaded ? (
        <LoadingPending pending={isLoading} empty="No Messages found." />
      ) : (
        <div>
          {messages.map((message) => (
            <MessageRow key={message.id} message={message} />
          ))}
        </div>
      )}
    </div>
  );
};

export default MessageView;
