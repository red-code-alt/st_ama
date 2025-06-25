import React, { useContext, useEffect, useState } from 'react';
import PropTypes from 'prop-types';

import ExtLink from '../components/ext-link';
import { ErrorContext } from '../errors/try-catch';
import useResourceGet from '../hooks/use-resource-get';
import useCheckProject from '../hooks/use-check-project';
import { getDeep } from '../lib/utils';

const modulePageConfig = {
  found: {
    name: 'found',
    title: 'Found',
    path: '/',
    to: './',
    columns: ['source', 'destination', 'status'],
    modules: [],
    help: (
      <p>
        This page shows the Drupal 9 modules{' '}
        <strong>Acquia Migrate Accelerate</strong> automatically downloaded for
        you, based on your Drupal 7 code base. Modules will be automatically
        enabled if their migration path is marked as <em>vetted</em> by our
        team.
      </p>
    ),
  },
  unknown: {
    name: 'unknown',
    title: 'Unknown',
    path: 'unknown',
    to: 'unknown',
    columns: ['source'],
    modules: [],
    help: (
      <p>
        This page shows modules that are either custom code, or contributed
        modules without a known Drupal 9 replacement. For contributed projects,
        look to{' '}
        <ExtLink
          title="contrib_tracker"
          href="https://www.drupal.org/project/issues/contrib_tracker"
        >
          contrib_tracker
        </ExtLink>{' '}
        for guidance. For custom code,{' '}
        <ExtLink
          title="drupalmoduleupgrader"
          href="https://www.drupal.org/project/drupalmoduleupgrader"
        >
          drupalmoduleupgrader
        </ExtLink>{' '}
        can help get you started in the code porting process.
      </p>
    ),
  },
};

const getInstallLabel = (module) => {
  if (module.availableToInstall) {
    return module.installed
      ? {
          label: 'Installed',
          title: 'This module is installed',
          modifier: 'installed',
        }
      : {
          label: 'Not Enabled',
          title: 'This module is present in the codebase but is not installed',
          modifier: 'not-enabled',
        };
  }
  return {
    label: 'Not Installed',
    title:
      'This module is not available to install and must be downloaded using composer',
    modifier: 'not-installed',
  };
};

const getInstallInstructions = (module, category, requirePackage) => {
  if (module.availableToInstall) {
    return !module.installed
      ? {
          instruction: { href: '/admin/modules', title: 'Enable this module' },
        }
      : null;
  }
  return category !== 'core' && requirePackage
    ? {
        instruction: 'Install with Composer:',
        detail: `composer require ${requirePackage.packageName}:${requirePackage.versionConstraint}`,
      }
    : null;
};

const parseModule = (module) => ({
  id: module.id,
  title: module.attributes.humanName,
  version: module.attributes.version,
  recognitionState: module.attributes.recognitionState,
});

const parseRecommendation = (recommendation) => {
  const { id, type, attributes, links } = recommendation;
  const { modules, note, requirePackage, vetted } = attributes;
  const category =
    requirePackage && requirePackage.packageName === 'drupal/core'
      ? 'core'
      : 'other';

  return {
    id,
    type,
    vetted,
    category,
    note,
    links,
    modules: modules
      ? modules.map((module) => {
          return {
            ...module,
            installLabel: getInstallLabel(module),
            installInstructions: getInstallInstructions(
              module,
              category,
              requirePackage,
            ),
          };
        })
      : [],
  };
};

const parseModules = (data) => {
  const { sourceModules, destModules } = data.reduce(
    (resources, resource) => {
      resources[
        resource.type === 'sourceModule' ? 'sourceModules' : 'destModules'
      ].push(resource);

      return resources;
    },
    { sourceModules: [], destModules: [] },
  );

  const recommendationAppliesToModule = (module) => (recommendation) => {
    const recommendedFor =
      getDeep(recommendation, 'relationships.recommendedFor.data') || [];
    return recommendedFor.some((sourceModuleIdentifier) => {
      return (
        module.type === sourceModuleIdentifier.type &&
        module.id === sourceModuleIdentifier.id
      );
    });
  };

  return sourceModules.map((module) => ({
    ...parseModule(module),
    replacementCandidates: destModules
      .filter(recommendationAppliesToModule(module))
      .map(parseRecommendation),
  }));
};

/**
 * @type {React.Context<{}>}
 */
const ModulesContext = React.createContext();

const ModulesProvider = ({ basepathDashboard, source, children }) => {
  const [modules, setModules] = useState([]);
  const { throwError } = useContext(ErrorContext);
  const [{ isLoading, document }] = useResourceGet({
    href: source,
    handleError: throwError,
  });

  const { projectInfo } = useCheckProject(
    modules.filter((module) => module.recognitionState === 'Unknown'),
  );

  useEffect(() => {
    if (document) {
      setModules(parseModules(document.data));
    }
  }, [document]);

  return (
    <ModulesContext.Provider
      value={{
        isLoading,
        modules,
        lists: modulePageConfig,
        projectInfo,
        basepathDashboard,
      }}
    >
      {children}
    </ModulesContext.Provider>
  );
};

export { ModulesContext, ModulesProvider };

ModulesProvider.propTypes = {
  basepathDashboard: PropTypes.string.isRequired,
  source: PropTypes.string.isRequired,
  children: PropTypes.oneOfType([
    PropTypes.arrayOf(PropTypes.node),
    PropTypes.node,
  ]).isRequired,
};
