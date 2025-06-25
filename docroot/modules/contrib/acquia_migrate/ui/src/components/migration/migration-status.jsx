import React, { useContext } from 'react';
import PropTypes from 'prop-types';

import Icon from '../icon';
import { FocuserContext } from '../../contexts/focuser';
import { DisplayContext } from '../../contexts/display';
import { MigrationContext } from '../../contexts/migrations';

const Dependencies = ({ id, dependencies }) => {
  const { checkDependencyToggle, toggleDependency } =
    useContext(DisplayContext);
  const { focusChild } = useContext(FocuserContext);

  const isOpen = checkDependencyToggle(id);
  const toggleOpen = () => {
    toggleDependency(id);
  };
  const handleClick = (dependentID) => {
    toggleOpen();
    focusChild(dependentID);
  };

  return (
    <div className="dropdown dropleft">
      <a
        className="dropdown-toggle"
        role="button"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded={isOpen}
        onClick={toggleOpen}
      >
        <Icon icon="chevron-down" />
      </a>
      <div className={`dropdown-menu ${isOpen ? 'show' : ''}`}>
        {dependencies.map((item) => (
          <div key={item.id} className="dropdown-item">
            {item.incomplete ? (
              <a
                title={`jump to ${item.label} migration`}
                className={`migration__${
                  item.incomplete ? 'danger' : 'success'
                } migration__import-status`}
                onClick={() => handleClick(item.id)}
              >
                {item.label}
              </a>
            ) : (
              item.label
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

const MigrationStatus = ({
  id,
  dependencies,
  activity,
  hasImported,
  showDependencies,
  link,
}) => {
  const { controllingSession } = useContext(MigrationContext);

  let title = 'Not available';
  let status = 'muted';

  if (activity !== 'idle') {
    status = 'working';
    if (activity === 'importing') {
      title = 'Importing …';
    } else if (activity === 'rollingBack') {
      title = 'Rolling back …';
    } else if (activity === 'refreshing') {
      title = 'Refreshing …';
    } else if (activity === 'stuck') {
      title = 'Migration is frozen';
    } else {
      title = 'Unknown activity …';
    }
    // Indicate on the live activity which other controlling session is doing it.
    if (controllingSession == 'drush') {
      title = '🤖 ' + title;
    } else if (controllingSession === false) {
      title = '👩‍💻 ' + title;
    }
  }

  if (!link && !showDependencies) {
    return (
      <span className={`migration__${status} migration__import-status`}>
        {title}
      </span>
    );
  }

  const hasDependencies = dependencies.length > 0;

  if (activity === 'stuck') {
    status = 'warning';
  } else if (activity !== 'idle') {
    // Nothing else to compute.
  } else if (showDependencies && hasDependencies) {
    const unmetDependencies =
      hasDependencies &&
      dependencies.some((dependency) => dependency.incomplete);
    title = unmetDependencies ? 'Unmet dependencies' : 'No unmet dependencies';
    status = unmetDependencies ? 'danger' : 'success';
    // Handle the case of all dependencies having been met yet no
    // "import" link being available.
    if (
      !hasImported &&
      !unmetDependencies &&
      link === null &&
      (controllingSession === true || controllingSession === null)
    ) {
      title = 'Dependencies need review';
      status = 'warning';
    }
  } else if (link) {
    title = `Ready for ${link.title}`;
    status = 'success';
  } else {
    if (hasImported) {
      title = 'Already imported';
    } else {
      const { controllingSession } = useContext(MigrationContext);
      title =
        controllingSession === true || controllingSession === null
          ? 'Not ready'
          : controllingSession == 'drush'
          ? '🤖'
          : '👩‍💻';
    }
  }

  return (
    <div className="migration__status">
      <span className={`migration__${status} migration__import-status`}>
        {title}
      </span>
      {showDependencies && hasDependencies && (
        <Dependencies id={id} dependencies={dependencies} />
      )}
    </div>
  );
};

export default MigrationStatus;

Dependencies.propTypes = {
  id: PropTypes.string.isRequired,
  dependencies: PropTypes.arrayOf(PropTypes.object),
};

Dependencies.defaultProps = {
  dependencies: [],
};

MigrationStatus.propTypes = {
  id: PropTypes.string.isRequired,
  activity: PropTypes.string.isRequired,
  dependencies: PropTypes.arrayOf(PropTypes.object),
  hasImported: PropTypes.bool.isRequired,
  showDependencies: PropTypes.bool.isRequired,
  link: PropTypes.shape({
    rel: PropTypes.string,
    title: PropTypes.string,
    href: PropTypes.string,
  }),
};

MigrationStatus.defaultProps = {
  dependencies: [],
  link: null,
};
