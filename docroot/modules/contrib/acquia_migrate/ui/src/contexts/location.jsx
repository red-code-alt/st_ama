import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';

const extractLocation = (windowLocation) => {
  return {
    url: windowLocation.href,
    path: windowLocation.pathname,
  };
};

/**
 * @type {React.Context<{}>}
 */
const LocationContext = React.createContext({});

/**
 * Provide window location context.
 *
 * @param {node} children
 *   React nodes passed into this Context.
 * @return {ReactNode}
 *   <LocationContext.Provider />
 */
const LocationProvider = ({ children }) => {
  const [windowLocation, setWindowLocation] = useState(
    extractLocation(window.location),
  );

  useEffect(() => {
    window.addEventListener('popstate', () => {
      setWindowLocation(extractLocation(window.location));
    });
  });

  const setWindowURL = (search, title = '') => {
    window.history.pushState(
      null,
      title.length || document.title,
      search || window.location.pathname,
    );
    setWindowLocation(extractLocation(window.location));
  };

  const values = {
    windowURL: windowLocation.url,
    windowPath: windowLocation.path,
    setWindowURL,
  };

  return (
    <LocationContext.Provider value={values}>
      {children}
    </LocationContext.Provider>
  );
};

export { LocationContext, LocationProvider };

LocationProvider.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
