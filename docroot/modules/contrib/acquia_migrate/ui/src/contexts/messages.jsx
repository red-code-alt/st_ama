import React, { useContext, useEffect, useState } from 'react';
import PropTypes from 'prop-types';

import useFilters from '../hooks/use-filters';
import { LocationContext } from './location';
import { parseQuery } from '../lib/uri';
import { getDeep, parseUnknownValue, getQueryLinks } from '../lib/utils';
import { useCollection } from '../hooks/use-resource';
import useLimiter from '../hooks/use-limiter';
import useLimited from '../hooks/use-limited';
import MessageTextLimiter from '../lib/limiter/message-text-limiter';
import MessageBoolLimiter from '../lib/limiter/message-bool-limiter';

/**
 * Get the relationship label.
 *
 * @param {object} related
 *   Message collection row.
 * @param {array} included
 *   Message collection included resource.
 * @return {string}
 *   Related content label.
 */
const getRelatedLabel = (related, included) => {
  return related.data.hasOwnProperty('meta')
    ? related.data.meta.label
    : included.find((item) => item.id === related.data.id).attributes.label;
};

/**
 * Select useful properties from migration.
 *
 * @param {object} message
 *   Message collection row.
 * @param {array} included
 *   Message collection included resources.
 * @return {object}
 *   Parsed message object.
 */
const parseMessage = (message, included) => {
  const { id, attributes, relationships, links } = message;
  const {
    datetime,
    severity: sev,
    message: text,
    messageCategory: type,
    solution,
  } = attributes;
  const { sourceMigration, sourceMigrationPlugin } = relationships;
  const migration = getRelatedLabel(sourceMigration, included);
  const plugin = sourceMigrationPlugin.data.id;
  const sourceId =
    links && links.hasOwnProperty('source')
      ? parseUnknownValue(getDeep(links.source, 'meta.source-identifiers'))
      : '';

  const severity = getDeep(links, 'severity.title') || sev;

  return {
    id,
    sourceId,
    datetime,
    migration,
    plugin,
    type,
    severity,
    text,
    solution,
  };
};

/**
 * @type {React.Context<{}>}
 */
const MessagesContext = React.createContext({});

/**
 * Provides global props to the Context.
 *
 * @param {string} basepathDashboard
 *   The base url of the dashboard app.
 * @param {string} source
 *   The API entrypoint.
 * @param {node} children
 *   React nodes passed into this Context.
 * @return {ReactNode}
 *   <MessagesContext.Provider value={value} />
 */
const MessagesProvider = ({ basepathDashboard, source, children }) => {
  const { windowURL } = useContext(LocationContext);
  const { isLoading, document } = useCollection({
    href: source,
    uriTemplateParams: parseQuery(windowURL),
    handleError: console.error,
  });
  const [allMessages, setAllMessages] = useState([]);
  const { limiters, updateLimiter } = useLimiter({
    searchText: new MessageTextLimiter({
      name: 'searchText',
      value: '',
    }),
    solutionOnly: new MessageBoolLimiter({
      name: 'solutionOnly',
      value: false,
    }),
  });
  const messages = useLimited(allMessages, limiters);
  const [queryLink, setQueryLink] = useState(null);
  const { filters, setFields, updateFilter, submitFilters, clearFilters } =
    useFilters();

  useEffect(() => {
    if (queryLink) {
      setFields(queryLink);
    }
  }, [queryLink]);

  useEffect(() => {
    if (document) {
      const { data, included } = document;
      const { links = {} } = document;
      const parsedQueryLink = getQueryLinks(links)[0];
      if (parsedQueryLink) {
        setQueryLink(parsedQueryLink);
      }
      setAllMessages(data.map((msg) => parseMessage(msg, included)));
    }
  }, [document]);

  return (
    <MessagesContext.Provider
      value={{
        basepathDashboard,
        messages,
        filters,
        updateFilter,
        submitFilters,
        clearFilters,
        limiters,
        updateLimiter,
        isLoading,
      }}
    >
      {children}
    </MessagesContext.Provider>
  );
};

export { MessagesContext, MessagesProvider };

MessagesProvider.propTypes = {
  basepathDashboard: PropTypes.string.isRequired,
  source: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
