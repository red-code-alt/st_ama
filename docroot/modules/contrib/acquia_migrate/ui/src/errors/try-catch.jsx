import React from 'react';

const defaultErrorContextValue = {
  hasError: false,
  error: null,
  errorInfo: null,
  throwError: console.error,
  clearError: () => {},
};

export const ErrorContext = React.createContext(defaultErrorContextValue);

const isTryComponent = (component) => component.type === Try;

const isCatchComponent = (component) => component.type === Catch;

const createTestFunctionFromCatchComponent = (catchComponent) => {
  const { catches = null, prototype = Error } = catchComponent.props;
  return catches || ((error) => error instanceof prototype);
};

const Try = ({ children }) => <>{children}</>;

const Catch = ({ children }) => <>{children}</>;

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      hasError: false,
      error: null,
      errorInfo: null,
    };
  }

  getTry = () => {
    return this.props.children.filter(isTryComponent);
  };

  getCatches = () => {
    return this.props.children.filter(isCatchComponent);
  };

  throwError = (error) =>
    this.setState({ ...this.state, hasError: true, error });

  clearError = () =>
    this.setState({ hasError: false, error: null, errorInfo: null });

  render() {
    const { hasError, error, errorInfo } = this.state;
    return (
      <ErrorContext.Provider
        value={{
          error,
          errorInfo,
          throwError: this.throwError,
          clearError: this.clearError,
        }}
      >
        {!hasError
          ? this.getTry()
          : this.getCatches().find((component) => {
              const catches = createTestFunctionFromCatchComponent(component);
              return catches(error, errorInfo);
            })}
      </ErrorContext.Provider>
    );
  }

  componentDidCatch(error, errorInfo) {
    const boundaryShouldCatchError = this.getCatches()
      .map(createTestFunctionFromCatchComponent)
      .some((catches) => catches(error, errorInfo));
    if (boundaryShouldCatchError) {
      // Eventually, this console log should be enhanced to send the error and
      // application state to a centralized logging service.
      console.error(error);
      this.setState({ hasError: true, error, errorInfo });
    } else {
      throw error;
    }
  }
}

ErrorBoundary.propTypes = {
  children: (props, propName) => {
    if (!Array.isArray(props[propName])) {
      return new Error(
        'An ErrorBoundary must contain a Try element and at least one Catch element.',
      );
    }
    let hasTry = false;
    for (const child of props[propName]) {
      if (isTryComponent(child)) {
        if (!hasTry) {
          hasTry = true;
        } else {
          return new Error(
            'An ErrorBoundary must not contain more than one Try element.',
          );
        }
      } else if (!isCatchComponent(child)) {
        return new Error(
          'An ErrorBoundary must not contain any elements that are not Try or Catch elements.',
        );
      }
    }
    if (!hasTry) {
      return new Error('An ErrorBoundary must contain one Try element.');
    }
  },
};

export { ErrorBoundary, Try, Catch };
