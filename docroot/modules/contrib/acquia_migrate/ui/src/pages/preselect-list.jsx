import React, { useContext } from 'react';

import PreselectView from '../components/preselect/preselect-view';
import LoadingPending from '../components/loading-pending';
import { PreselectContext } from '../contexts/preselect';
import { hasPreselectLink, hasInitialImportLink } from '../lib/utils';

/**
 * The list of available migrations. User will select which, if any to skip.
 *
 * @return {ReactNode}
 *   <PreselectList />
 */
const PreselectList = () => {
  const { migrations, links, isLoading } = useContext(PreselectContext);
  const isLoaded = !!migrations.length;
  if (!isLoaded) {
    return (
      <LoadingPending pending={isLoading} empty="No migrations available." />
    );
  }

  if (!hasPreselectLink(links)) {
    return (
      <div>
        <p>Preselections made successfully.</p>
        {hasInitialImportLink(links) ? (
          <p>
            Visit the dashboard to begin an initial import of supporting
            configuration. When that is complete, you may begin importing
            content.
          </p>
        ) : (
          <p>
            The initial import of your selected migrations is complete, you no
            longer need to visit this page.
          </p>
        )}
        <a
          className="button button--primary"
          href="/acquia-migrate-accelerate/migrations"
        >
          View Migrations Dashboard
        </a>
      </div>
    );
  }

  return (
    <div>
      <div>
        <p>
          Choose which parts of your source site you want to migrate into your
          new Drupal 9 site.
        </p>
        <p>
          <em>Don&apos;t worry</em>, you can still choose to bring over anything
          later that you skip now.
        </p>
      </div>
      <PreselectView />
    </div>
  );
};

export default PreselectList;
