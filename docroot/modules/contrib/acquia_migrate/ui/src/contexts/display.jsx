import React, { useState } from 'react';
import PropTypes from 'prop-types';

import useToggleValue from '../hooks/use-toggle-value';
/**
 * @type {React.Context<{}>}
 */
const DisplayContext = React.createContext();

const DisplayProvider = ({ children }) => {
  const [checkDependencyToggle, toggleDependency] = useToggleValue();
  const [checkOperationsToggle, toggleOperations] = useToggleValue();

  return (
    <DisplayContext.Provider
      value={{
        checkDependencyToggle,
        toggleDependency,
        checkOperationsToggle,
        toggleOperations,
      }}
    >
      {children}
    </DisplayContext.Provider>
  );
};

export { DisplayContext, DisplayProvider };

DisplayProvider.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
