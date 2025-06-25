import React from 'react';
import PropTypes from 'prop-types';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import { Location, Router } from '@reach/router';

/**
 * The loaded list of modules.
 *
 * @param {object} lists
 *   List returned from MigrationContext @see parseModules.
 * @return {ReactNode}
 *   <ModuleViews lists={lists} />
 */
const TabbedPage = ({ children }) => (
  <Location>
    {({ location }) => (
      <TransitionGroup>
        <CSSTransition
          key={location.key}
          timeout={500}
          classNames="tabbed_page__view"
        >
          <Router
            location={location}
            primary={false}
            className="tabbed_page__view"
          >
            {children}
          </Router>
        </CSSTransition>
      </TransitionGroup>
    )}
  </Location>
);

export default TabbedPage;

TabbedPage.propTypes = {
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
