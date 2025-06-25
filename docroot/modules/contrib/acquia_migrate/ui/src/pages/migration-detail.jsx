import React, { useContext } from 'react';
import PropTypes from 'prop-types';
import { Router, Link, useNavigate } from '@reach/router';

import RegionHeader from '../regions/header';
import RegionDialog from '../regions/dialog';
import MigrationDetails from '../components/migration/migration-details';
import MigrationPreview from '../components/migration/migration-preview';
import MigrationMapping from '../components/migration/migration-mapping';
import LoadingPending from '../components/loading-pending';
import ClaroHeader from '../components/claro/header';
import ClaroBreadcrumb from '../components/claro/breadcrumb';
import ClaroBreadcrumbItem from '../components/claro/breadcrumb-item';
import ExtLink from '../components/ext-link';
import ClaroNavTabs from '../components/claro/nav-tabs';
import ClaroNavItem from '../components/claro/nav-item';
import ClaroDialog from '../components/claro/dialog';
import MigrationInfo from '../components/migration/migration-info';
import { MigrationContext } from '../contexts/migrations';
import { getPreview } from '../lib/utils';
import useBeforeUnload from '../hooks/use-beforeunload';

/**
 * Single migration page for details, mapping and preview.
 *
 * @param {string} id
 *   The migration id.
 * @return {ReactNode}
 *   <MigrationDetail id={id} />
 */
const MigrationDetail = ({ id }) => {
  const navigate = useNavigate();
  const { migrations, isLoading, modal, safeToCloseApp, basepath } =
    useContext(MigrationContext);
  const warn = useBeforeUnload(safeToCloseApp, () => {
    // Navigate back to the dashboard, this ensures that when the user sees the warning they actually see the activity.
    navigate(basepath);
  });
  const migration = migrations.find((m) => m.id === decodeURIComponent(id));

  const isLoaded = !!migration;

  if (!isLoaded) {
    return <LoadingPending pending={isLoading} empty="No migration found." />;
  }

  const { label, processedCount, totalCount, dependencies, consistsOf, links } =
    migration;

  const mapping = Object.values(links).find(
    (link) =>
      link.rel ===
      'https://drupal.org/project/acquia_migrate#link-rel-migration-mapping',
  );

  // Checking if there are any previewable links.
  const { preview, previewable } = getPreview(links);

  const dependencyList = dependencies.map((dependency) => ({
    ...dependency,
    label: migrations.find((m) => m.id === dependency.id).label,
  }));

  return (
    <div className="migrate-ui__migration_detail region-content">
      <RegionHeader>
        <ClaroBreadcrumb>
          <ClaroBreadcrumbItem>
            <ExtLink href="../../../../" title="Home">
              Home
            </ExtLink>
          </ClaroBreadcrumbItem>
          <ClaroBreadcrumbItem>
            <Link to="../../">Migrations</Link>
          </ClaroBreadcrumbItem>
          <ClaroBreadcrumbItem>
            <span>{label}</span>
          </ClaroBreadcrumbItem>
        </ClaroBreadcrumb>
        <ClaroHeader title={`Migration: ${label}`} />
        <MigrationInfo migrations={[migration]} />
        <ClaroNavTabs>
          {previewable ? (
            <ClaroNavItem to="preview">Preview</ClaroNavItem>
          ) : null}
          <ClaroNavItem to="./">Mapping</ClaroNavItem>
          <ClaroNavItem to="details">Details</ClaroNavItem>
        </ClaroNavTabs>
      </RegionHeader>
      <Router primary={false}>
        {previewable && (
          <MigrationPreview
            path="preview"
            preview={preview}
            completed={processedCount === totalCount}
          />
        )}
        <MigrationMapping path="/" mapping={mapping} />
        <MigrationDetails
          path="details"
          dependencies={dependencyList}
          consistsOf={consistsOf}
        />
      </Router>
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

export default MigrationDetail;

MigrationDetail.propTypes = {
  id: PropTypes.string,
};

MigrationDetail.defaultProps = {
  id: '',
};
