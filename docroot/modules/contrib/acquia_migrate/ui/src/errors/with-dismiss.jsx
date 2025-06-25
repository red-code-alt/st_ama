import React, { useContext } from 'react';

import { ErrorContext } from './try-catch';

export default function withDismiss(WrappedComponent) {
  function WithDismiss(props) {
    const { clearError } = useContext(ErrorContext);
    return <WrappedComponent {...{ dismiss: clearError, ...props }} />;
  }

  const wrappedComponentName =
    WrappedComponent.displayName || WrappedComponent.name || 'Component';

  WithDismiss.displayName = `withDismiss(${wrappedComponentName})`;
  return WithDismiss;
}
