import React, { useContext } from 'react';

import ClaroCheckbox from '../claro/checkbox';
import { MessagesContext } from '../../contexts/messages';
import ClaroTextInput from '../claro/text-input';

const MessageLimiter = () => {
  const {
    limiters: { searchText, solutionOnly },
    updateLimiter,
  } = useContext(MessagesContext);

  // Add a form to let the clientsided validation appear.
  const handleSubmit = (e) => {
    e.preventDefault();
  };

  const handleTextChange = (e) => {
    updateLimiter(searchText.name, e.target.value);
  };

  return (
    <div className="message_limiter">
      <form onSubmit={handleSubmit}>
        <ClaroTextInput
          name={searchText.name}
          label="Search for text"
          value={searchText.value}
          onChange={handleTextChange}
          type="search"
          validation={{ minLength: '3' }}
        >
          Limit messages to those that have matching text in any of these
          fields: <em>Migration</em>, <em>Migration Plugin</em>,{' '}
          <em>Message</em>, or <em>Solution</em>.
        </ClaroTextInput>
        <ClaroCheckbox
          name={solutionOnly.name}
          label="Hide messages without a solution"
          checked={solutionOnly.value}
          toggle={() => {
            updateLimiter(solutionOnly.name, !solutionOnly.value);
          }}
        />
      </form>
    </div>
  );
};

export default MessageLimiter;
