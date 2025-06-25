import React, { useContext } from 'react';
import PropTypes from 'prop-types';
import { useAsync } from 'react-async';

import ActionButton from '../action-button';
import ClaroThrobber from '../claro/throbber';
import { MigrationContext } from '../../contexts/migrations';
import {
  getResource,
  parseUnknownValue,
  getUpdateLinks,
  getOverrideLinks,
  getUnmetLinks,
} from '../../lib/utils';
import { parseOverrides } from '../../lib/uri';

const EmptyMapping = () => <td>&mdash; &mdash;</td>;

const Mapping = ({ mapping }) => {
  const {
    sourceFieldName,
    destinationFieldName,
    destinationFieldType,
    destinationFieldLabel,
    destinationFieldIsRequired,
    migrationProcessPlugins_THIS_WILL_CHANGE,
    overrides,
  } = mapping;
  const sourceFields = sourceFieldName ? (
    <td>
      <code>{sourceFieldName}</code>
    </td>
  ) : (
    <EmptyMapping />
  );

  const destinationFields = destinationFieldName ? (
    <React.Fragment>
      <td className="migration__mapping_destination_fields">
        <code>{destinationFieldName}</code>
        <p>
          {destinationFieldLabel}
          {destinationFieldIsRequired ? (
            <abbr title="Required Field">*</abbr>
          ) : (
            ''
          )}
        </p>
      </td>
      <td>
        <em>{destinationFieldType}</em>
      </td>
    </React.Fragment>
  ) : (
    <React.Fragment>
      <EmptyMapping />
      <EmptyMapping />
    </React.Fragment>
  );

  const processPlugins = migrationProcessPlugins_THIS_WILL_CHANGE ? (
    <td>
      <code>{parseUnknownValue(migrationProcessPlugins_THIS_WILL_CHANGE)}</code>
    </td>
  ) : (
    <EmptyMapping />
  );

  const overrideLinks = overrides.length ? (
    <td>
      {overrides.map((override) => (
        <ActionButton
          key={`$override-{override.label}`}
          button={{
            title: override.title,
            rel: override.rel,
          }}
          className="button"
          action={override.action}
        />
      ))}
    </td>
  ) : null;

  return (
    <tr>
      {sourceFields}
      {destinationFields}
      {processPlugins}
      {overrideLinks}
    </tr>
  );
};

const MappingUpdates = ({ links, unmet, update }) => (
  <div className="mapping__updates">
    <ul className="menu">
      {links.map((link) => (
        <li key={link.title}>
          <ActionButton
            button={{
              title: link.title,
              rel: link.rel,
            }}
            className="button"
            action={() => update(link)}
          />
        </li>
      ))}
    </ul>
    {unmet.map((reason) => (
      <div key={reason.href} className="messages messages--warning">
        <p>{reason.title}</p>
      </div>
    ))}
  </div>
);

const MigrationMapping = ({ mapping }) => {
  const { updateMigration } = useContext(MigrationContext);

  const {
    data: response,
    isPending,
    reload,
  } = useAsync({
    promiseFn: getResource,
    href: mapping.href,
  });

  if (!mapping.href) {
    return (
      <div className="migration__mapping migration__mapping--no-mapping">
        <div className="messages messages--warning">
          No Mapping information available.
        </div>
      </div>
    );
  }

  if (isPending) {
    return <ClaroThrobber message="Loading…" />;
  }

  if (response instanceof Error) {
    return (
      <div className="migration__mapping migration__mapping--has-error">
        <div className="messages messages--error">
          <p>Error: {response.message}</p>
          <p>{mapping.href}</p>
        </div>
      </div>
    );
  }

  const updateMapping = (link) => {
    updateMigration({ link, callback: reload });
  };

  const { attributes, links } = response.data;
  const updateLinks = getUpdateLinks(links);
  const unmetLinks = getUnmetLinks(links);
  const overrideLinks = getOverrideLinks(links);
  const mappings = Object.entries(attributes)
    .filter(([, mappingList]) => typeof mappingList === 'object')
    .reduce((list, [listKey, mappingList]) => {
      const mapped = Object.entries(mappingList).map(([mappingKey, value]) => {
        const key = `${listKey}-${mappingKey}`;
        return overrideLinks
          ? {
              key,
              value: {
                ...value,
                overrides: parseOverrides(value, overrideLinks, updateMapping),
              },
            }
          : { key, value };
      });

      return [...list, ...mapped];
    }, []);

  return (
    <div className="migration__mapping">
      <MappingUpdates
        links={updateLinks}
        unmet={unmetLinks}
        update={updateMapping}
      />
      <div>
        <table>
          <thead>
            <tr>
              <th>Source Field</th>
              <th>Destination Field</th>
              <th>Field Type</th>
              <th>Process Plugins</th>
              <th>{!!overrideLinks.length && 'Operations'}</th>
            </tr>
          </thead>
          <tbody>
            {mappings.map(({ key, value }) => (
              <Mapping key={key} mapping={value} />
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default MigrationMapping;

Mapping.propTypes = {
  mapping: PropTypes.shape({
    sourceFieldName: PropTypes.string,
    destinationFieldName: PropTypes.string,
    destinationFieldType: PropTypes.string,
    destinationFieldLabel: PropTypes.string,
    destinationFieldIsRequired: PropTypes.bool,
    migrationProcessPlugins_THIS_WILL_CHANGE: PropTypes.array,
    overrides: PropTypes.array,
  }).isRequired,
};

MappingUpdates.propTypes = {
  links: PropTypes.arrayOf(PropTypes.object),
  unmet: PropTypes.arrayOf(PropTypes.object),
  update: PropTypes.func.isRequired,
};

MappingUpdates.defaultProps = {
  links: [],
  unmet: [],
};

MigrationMapping.propTypes = {
  mapping: PropTypes.shape({
    href: PropTypes.string,
    rel: PropTypes.string,
    title: PropTypes.string,
  }),
};

MigrationMapping.defaultProps = {
  mapping: {
    href: '',
    rel: '',
    title: '',
  },
};
