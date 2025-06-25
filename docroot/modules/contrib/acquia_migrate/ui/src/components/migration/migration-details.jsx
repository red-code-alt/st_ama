import React from 'react';
import PropTypes from 'prop-types';

import MigrationLink from './migration-link';
import ClaroTwoColumn from '../claro/two-column';
import ClaroPanel from '../claro/panel';

const MigrationDetails = ({ dependencies, consistsOf }) => {
  const detailDependencies = () => (
    <ClaroPanel header="Dependencies">
      {dependencies.length ? (
        <ul className="migration__details_dependencies admin-list--panel admin-list">
          {dependencies.map((dependency) => (
            <li key={dependency.id} className="admin-item--panel">
              <h5>
                <MigrationLink id={dependency.id}>
                  {dependency.label}
                </MigrationLink>
              </h5>
              {dependency.dependencyReasons && (
                <div>
                  <label htmlFor={`${dependency.id}-migrations`}>
                    Because of:
                  </label>
                  <ul id={`${dependency.id}-migrations`}>
                    {dependency.dependencyReasons.map((reason) => (
                      <li key={`${dependency.id}-${reason}`}>
                        <code>{reason}</code>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </li>
          ))}
        </ul>
      ) : (
        <div className="messages messages--warning">
          <p>No dependencies found.</p>
        </div>
      )}
    </ClaroPanel>
  );

  const detailUnderlying = () => (
    <ClaroPanel header="Underlying Migrations">
      {consistsOf.length ? (
        <ul className="migration__details_underlying admin-list--panel admin-list">
          {consistsOf.map((item) => (
            <li key={item.id} className="admin-item--panel">
              <code>{item.id}</code>
            </li>
          ))}
        </ul>
      ) : (
        <p>
          <em>No underlying migrations listed.</em>
        </p>
      )}
    </ClaroPanel>
  );

  return <ClaroTwoColumn one={detailDependencies()} two={detailUnderlying()} />;
};

export default MigrationDetails;

MigrationDetails.propTypes = {
  dependencies: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.string,
    }),
  ),
  consistsOf: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.string,
    }),
  ),
};

MigrationDetails.defaultProps = {
  dependencies: [],
  consistsOf: [],
};
