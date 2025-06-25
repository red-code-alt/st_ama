import React from 'react';
import PropTypes from 'prop-types';

import ModuleData from './module-data';
import ModuleLimiter from './module-limiter';
import useLimiter from '../../hooks/use-limiter';
import useLimited from '../../hooks/use-limited';
import ModuleTextLimiter from '../../lib/limiter/module-text-limiter';
import ModuleVettedLimiter from '../../lib/limiter/module-vetted-limiter';
import ModuleInstalledLimiter from '../../lib/limiter/module-installed-limiter';

/**
 * The loaded list of modules.
 *
 * @param {object} list
 * @param {string} list.title
 *   The list title
 * @param {array} list.columns
 *   The columns this datagrid should display.
 * @param {ReactNode} help
 *   The help text for this tab.
 * @param {array} modules
 *   List returned from MigrationContext @see parseModules.
 * @return {ReactNode}
 *   <ModuleViewFound modules={modules} />
 */
const ModuleViewFound = ({ list, modules }) => {
  const { title, columns, help } = list;
  let sourceVersion = null;
  let destVersion = null;
  const { limiters, updateLimiter } = useLimiter({
    searchText: new ModuleTextLimiter({
      name: 'searchText',
      value: '',
    }),
    vettedOnly: new ModuleVettedLimiter({
      name: 'vettedOnly',
      value: false,
    }),
    installedOnly: new ModuleInstalledLimiter({
      name: 'installedOnly',
      value: false,
    }),
  });
  const { otherAll, coreAll } = modules.reduce(
    (grouped, module) => {
      if (
        module.replacementCandidates &&
        module.replacementCandidates.some(
          (replacement) => replacement.category === 'core',
        )
      ) {
        // Remove version from core modules.
        module.replacementCandidates.forEach((replacementCandidate) => {
          if (replacementCandidate.category === 'core') {
            replacementCandidate.modules = replacementCandidate.modules.map(
              (destModule) => {
                const { version: dstVersion, ...withoutDstVersion } =
                  destModule;
                // Set the coreVersion from the first core dest module.
                if (!destVersion) {
                  destVersion = dstVersion;
                }
                return withoutDstVersion;
              },
            );
          }

          return replacementCandidate;
        });

        // Put row in core group if any of the replacementCandidates are core.
        const { version: srcVersion, ...withoutSrcVersion } = module;
        grouped.coreAll.push(withoutSrcVersion);

        // Set the coreVersion from the first core source module.
        if (!sourceVersion) {
          sourceVersion = srcVersion;
        }

        return grouped;
      }

      grouped.otherAll.push(module);
      return grouped;
    },
    {
      otherAll: [],
      coreAll: [],
    },
  );

  const core = useLimited(coreAll, limiters);
  const other = useLimited(otherAll, limiters);

  return (
    <div>
      {help}
      <ModuleLimiter limiters={limiters} update={updateLimiter} />
      {!core.length && !other.length ? (
        <div>
          No <em>{title}</em> modules to display.
        </div>
      ) : (
        <div>
          {other.length ? (
            <ModuleData title={null} modules={other} columns={columns} />
          ) : null}
          {core.length ? (
            <ModuleData
              title="Migration path to Drupal 9 core"
              modules={core}
              columns={columns}
              sourceVersion={sourceVersion}
              destVersion={destVersion}
            />
          ) : null}
        </div>
      )}
    </div>
  );
};

export default ModuleViewFound;

ModuleViewFound.propTypes = {
  list: PropTypes.shape({
    title: PropTypes.string,
    columns: PropTypes.arrayOf(PropTypes.string),
    help: PropTypes.node,
  }).isRequired,
  modules: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.string,
      title: PropTypes.string,
      version: PropTypes.string,
    }),
  ).isRequired,
};
