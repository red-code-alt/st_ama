import React, { useContext } from 'react';
import PropTypes from 'prop-types';

import ModuleData from './module-data';
import ModuleProjectInfo from './module-project-info';
import ClaroThrobber from '../claro/throbber';
import { ModulesContext } from '../../contexts/modules';

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
 *   <ModuleViewUnknown modules={modules} />
 */
const ModuleViewUnknown = ({ list, modules }) => {
  const { columns, help } = list;
  const { projectInfo } = useContext(ModulesContext);
  const { status, completed, projects } = projectInfo;

  const { contrib, custom } = modules.reduce(
    (grouped, module) => {
      if (projects.length) {
        grouped[projects.includes(module.id) ? 'contrib' : 'custom'].push(
          module,
        );
      }

      return grouped;
    },
    { contrib: [], custom: [] },
  );

  if (!completed) {
    return <ClaroThrobber message={status} />;
  }

  return (
    <div>
      {help}
      <ModuleProjectInfo projectInfo={projectInfo} />
      {contrib ? (
        <ModuleData
          title="Contributed modules"
          modules={contrib}
          columns={columns}
        />
      ) : null}
      {custom ? (
        <ModuleData
          title="Modules of unknown origin (usually custom, sometimes submodules)"
          modules={custom}
          columns={columns}
        />
      ) : null}
    </div>
  );
};

export default ModuleViewUnknown;

ModuleViewUnknown.propTypes = {
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
