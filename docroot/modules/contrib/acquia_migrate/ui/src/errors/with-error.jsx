import React, { useContext } from 'react';
import { ErrorContext } from './try-catch';

export default function withError(WrappedComponent) {
  function WithError(props) {
    const { error } = useContext(ErrorContext);
    return <WrappedComponent {...{ error, ...props }} />;
  }

  const wrappedComponentName =
    WrappedComponent.displayName || WrappedComponent.name || 'Component';

  WithError.displayName = `withError(${wrappedComponentName})`;
  return WithError;
}
