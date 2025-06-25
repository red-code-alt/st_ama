import React, { useContext } from 'react';

import LoadingPending from '../components/loading-pending';
import ModuleViewFound from '../components/module/module-view-found';
import ModuleViewUnknown from '../components/module/module-view-unknown';
import ClaroBreadcrumb from '../components/claro/breadcrumb';
import ClaroBreadcrumbItem from '../components/claro/breadcrumb-item';
import ClaroHeader from '../components/claro/header';
import ClaroNavTabs from '../components/claro/nav-tabs';
import ClaroNavItem from '../components/claro/nav-item';
import RegionHeader from '../regions/header';
import ExtLink from '../components/ext-link';
import TabbedPage from '../components/tabbed-page';
import { ModulesContext } from '../contexts/modules';

/**
 * List of source modules and available destination module replacements.
 *
 * @return {ReactNode}
 *   <ModulesList />
 */
const ModulesList = () => {
  const { isLoading, lists, modules, basepathDashboard } =
    useContext(ModulesContext);
  const isLoaded = !!modules.length;

  if (!isLoaded) {
    return <LoadingPending pending={isLoading} empty="No Module info." />;
  }

  const { found, unknown } = lists;
  const foundModules = modules.filter(
    (module) => module.recognitionState === 'Found',
  );
  const unknownModules = modules.filter(
    (module) => module.recognitionState === 'Unknown',
  );
  const hasFound = !!foundModules.length;
  const hasUnknown = !!unknownModules.length;

  return (
    <div>
      <RegionHeader>
        <ClaroBreadcrumb>
          <ClaroBreadcrumbItem>
            <ExtLink href="../../" title="Home">
              Home
            </ExtLink>
          </ClaroBreadcrumbItem>
          <ClaroBreadcrumbItem>
            <ExtLink href={basepathDashboard} title="Migrations">
              Migrations
            </ExtLink>
          </ClaroBreadcrumbItem>
          <ClaroBreadcrumbItem>
            <span>Modules</span>
          </ClaroBreadcrumbItem>
        </ClaroBreadcrumb>
        <ClaroHeader title="Modules" />
        <ClaroNavTabs>
          {hasFound ? (
            <ClaroNavItem to={found.to}>{found.title}</ClaroNavItem>
          ) : null}
          {hasUnknown ? (
            <ClaroNavItem to={unknown.to}>{unknown.title}</ClaroNavItem>
          ) : null}
        </ClaroNavTabs>
      </RegionHeader>
      <TabbedPage>
        {hasFound ? (
          <ModuleViewFound
            path={found.path}
            list={found}
            modules={foundModules}
          />
        ) : null}
        {hasUnknown ? (
          <ModuleViewUnknown
            path={unknown.path}
            list={unknown}
            modules={unknownModules}
          />
        ) : null}
      </TabbedPage>
    </div>
  );
};

export default ModulesList;
