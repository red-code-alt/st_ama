import React, { useContext, useEffect, useState } from 'react';
import PropTypes from 'prop-types';

import { ErrorContext } from '../errors/try-catch';
import useBulkUpdate from '../hooks/use-bulk-update';
import useResourceGet from '../hooks/use-resource-get';
import { getPreselectLinks, hasPreselectLink } from '../lib/utils';

const parsePreselection = (migration) => {
  const { id, attributes, relationships, links } = migration;
  const { label, totalCount } = attributes;
  const { dependencies, consistsOf } = relationships;

  return {
    id,
    label,
    totalCount,
    dependencies: dependencies.data.map((item) => ({ id: item.id })),
    consistsOf: consistsOf.data.map((item) => ({ id: item.id })),
    links,
  };
};

/**
 * @type {React.Context<unknown>}
 */
const PreselectContext = React.createContext({});

const PreselectProvider = (props) => {
  const { source, children } = props;
  const [migrations, setMigrations] = useState([]);
  const [links, setLinks] = useState({});
  const { throwError } = useContext(ErrorContext);
  const { setLink, bulkUpdateMigrations } = useBulkUpdate(links);
  const [{ isLoading, document }, refreshResource] = useResourceGet({
    href: source,
    handleError: throwError,
  });

  useEffect(() => {
    if (document) {
      setLinks(document.links);
      setMigrations(document.data.map(parsePreselection));
    }
  }, [document]);

  useEffect(() => {
    if (Object.values(links).length && hasPreselectLink(links)) {
      setLink(getPreselectLinks(links)[0]);
    }
  }, [links]);

  return (
    <PreselectContext.Provider
      value={{
        links,
        migrations,
        isLoading,
        bulkUpdateMigrations,
        refreshResource,
      }}
    >
      {children}
    </PreselectContext.Provider>
  );
};

export { PreselectContext, PreselectProvider };

PreselectProvider.propTypes = {
  source: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
