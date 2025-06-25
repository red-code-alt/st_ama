import React, { useContext } from 'react';

import MessageFilter from './message-filter';
import ClaroThrobber from '../claro/throbber';
import { MessagesContext } from '../../contexts/messages';

const MessageFilters = () => {
  const { filters, updateFilter, clearFilters } = useContext(MessagesContext);
  const { fields } = filters;

  const handleClear = (e) => {
    e.preventDefault();
    clearFilters(e);
  };

  return fields ? (
    <form className="messages__filters exposed-form">
      {fields.map((field) => (
        <MessageFilter key={field.field} field={field} update={updateFilter} />
      ))}
      <div className="form-actions form-item exposed-form__item--actions">
        <input
          onClick={handleClear}
          className="button form-submit"
          value="Clear filters"
          type="button"
        />
      </div>
    </form>
  ) : (
    <ClaroThrobber message="Loading…" />
  );
};

export default MessageFilters;
