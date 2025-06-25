import React, { useContext, useEffect } from 'react';

import RegionHeader from '../regions/header';
import RegionDialog from '../regions/dialog';
import InitialImport from '../components/migration/initial-import';
import StaleData from '../components/migration/stale-data';
import PreselectWarning from '../components/migration/preselect-warning';
import MigrationView from '../components/migration/migration-view';
import MigrationInfo from '../components/migration/migration-info';
import ClaroBreadcrumb from '../components/claro/breadcrumb';
import ClaroBreadcrumbItem from '../components/claro/breadcrumb-item';
import ClaroHeader from '../components/claro/header';
import ClaroNavTabs from '../components/claro/nav-tabs';
import ClaroNavItem from '../components/claro/nav-item';
import ClaroDialog from '../components/claro/dialog';
import ExtLink from '../components/ext-link';
import useResize from '../hooks/use-resize';
import useBeforeUnload from '../hooks/use-beforeunload';
import TabbedPage from '../components/tabbed-page';
import { MigrationContext } from '../contexts/migrations';
import { hasPreselectLink } from '../lib/utils';

/**
 * Display the dashboard page with the current migrations.
 *
 * @return {ReactNode}
 *   <MigrationList />
 */
const MigrationList = () => {
  const { lists, links, migrations, modal, safeToCloseApp } =
    useContext(MigrationContext);

  const headerEl = document.querySelector('.content-header');
  const tabsEl = document.querySelector('.content-header .tabs-wrapper');
  const headerHeight = useResize(headerEl, 'height');
  const tabsHeight = useResize(tabsEl, 'height');
  const warn = useBeforeUnload(safeToCloseApp);

  useEffect(() => {
    document.documentElement.style.setProperty(
      '--page-height',
      `calc((100vh - var(--offset-top)) + (var(--header-height) - var(--tabs-height)))`,
    );
    document.documentElement.style.setProperty(
      '--migration-list-height',
      'calc(100vh - var(--header-height))',
    );

    return () => {
      document.documentElement.style.setProperty('--page-height', 'none');
    };
  }, []);

  useEffect(() => {
    document.documentElement.style.setProperty(
      '--header-height',
      `${headerHeight}px`,
    );
    document.documentElement.style.setProperty(
      '--tabs-height',
      `${tabsHeight}px`,
    );
  }, [headerHeight, tabsHeight]);

  return (
    <div className="region-content">
      <RegionHeader>
        <ClaroBreadcrumb>
          <ClaroBreadcrumbItem>
            <ExtLink href="../../" title="Home">
              Home
            </ExtLink>
          </ClaroBreadcrumbItem>
          <ClaroBreadcrumbItem>
            <span>Migrations</span>
          </ClaroBreadcrumbItem>
        </ClaroBreadcrumb>
        <ClaroHeader title="Migrations" />
        {migrations ? (
          <MigrationInfo
            migrations={[
              ...migrations.filter((migration) => !migration.skipped),
            ]}
          />
        ) : null}
        <ClaroNavTabs>
          {lists.map((item) => (
            <ClaroNavItem key={`tab-${item.name}`} to={item.to}>
              {item.title}
              <span
                className={`badge ${
                  item.showNew(item) ? 'badge--has-new' : null
                }`}
              >
                {item.activeCount}
              </span>
            </ClaroNavItem>
          ))}
        </ClaroNavTabs>
      </RegionHeader>
      <InitialImport />
      <StaleData />
      {links && hasPreselectLink(links) ? (
        <PreselectWarning />
      ) : (
        <TabbedPage>
          {lists.map((list) => (
            <MigrationView
              key={`migration-list-${list.name}`}
              list={list}
              name={list.name}
              path={list.path}
            />
          ))}
        </TabbedPage>
      )}
      <RegionDialog>
        {modal ? (
          <ClaroDialog
            title={modal.title}
            message={modal.message}
            action={modal.action}
            cancel={modal.cancel}
          />
        ) : null}
      </RegionDialog>
    </div>
  );
};

export default MigrationList;
