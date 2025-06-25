import React, { useContext } from 'react';
import PropTypes from 'prop-types';
import { Link } from '@reach/router';

import ExtLink from '../ext-link';
import Icon from '../icon';
import { MigrationContext } from '../../contexts/migrations';

const MessageItem = ({ links }) => {
  if (!links) {
    return null;
  }

  const [messages, validation] = [
    'migration-messages',
    'migration-entity-validation-messages',
  ].map((key) => (links.hasOwnProperty(key) ? links[key] : null));

  if (!messages && !validation) {
    return null;
  }

  const [messageCount, validationCount] = [messages, validation].map((link) =>
    link ? link['data-count'] : '0',
  );

  return (
    <li>
      🐛 Review and debug the {messageCount}{' '}
      <ExtLink href={messages.href} title="Messages">
        messages
      </ExtLink>
      .{' '}
      {validation ? (
        <span>
          {validationCount} of those are{' '}
          <ExtLink
            href={validation.href}
            title="Messages in category Entity Validation"
          >
            validation errors
          </ExtLink>
          , which means they must be fixed on your Drupal 7 site. After you fix
          these issues, you must refresh the data on your Drupal 9 site. The
          remainder are migration issues. These typically need to be fixed on
          the Drupal 9 site after your migration.
        </span>
      ) : null}
    </li>
  );
};

const ModuleItem = ({ basepathModule }) => (
  <li>
    <span
      role="img"
      aria-label="Check the Installed Modules and Recommendations"
    >
      🤖
    </span>{' '}
    Enable modules from the{' '}
    <ExtLink href={basepathModule} title="Module Auditor">
      module auditor
    </ExtLink>{' '}
    to expose more data/configuration to migrate. There may be modules we do not
    have a migration path for, or custom code which will need special attention.
  </li>
);

const KnownIssuesItem = () => (
  <li>
    <span role="img" aria-label="Time for some elbow grease">
      💪
    </span>{' '}
    There are some{' '}
    <ExtLink
      href="https://www.drupal.org/docs/upgrading-drupal/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8"
      title="Known issues when upgrading from drupal 6 or 7 to Drupal 8"
    >
      important features that do not yet have a migration path
    </ExtLink>{' '}
    (e.g. Views). Those need to be reconfigured by hand.
  </li>
);

const ThemingItem = () => (
  <li>
    <span role="img" aria-label="Work on your site theme">
      🙈
    </span>{' '}
    Rebuild your custom theme.{' '}
    <ExtLink
      href="https://www.drupal.org/docs/theming-drupal/upgrading-classes-on-7x-themes-to-8x"
      title="Upgrading 7.x themes to 8.x"
    >
      Drupal 7 themes must be rebuilt for 9
    </ExtLink>
    , learn more about{' '}
    <ExtLink
      href="https://www.drupal.org/docs/theming-drupal"
      title="Theming Drupal"
    >
      theming Drupal
    </ExtLink>
    .
  </li>
);

const RefreshingItem = () => (
  <li>
    <span role="img" aria-label="Launching your Drupal 9 site.">
      🧐
    </span>{' '}
    You may want to refresh the data on your Drupal 9 site. Update the Drupal 7
    database on this environment with a copy of the latest production database.
  </li>
);

const LaunchingItem = () => (
  <li>
    <span role="img" aria-label="Launching your Drupal 9 site.">
      🚀
    </span>{' '}
    Once satisfied with the state of this Drupal 9 site, you will need to
    uninstall the Acquia Migrate Accelerate module (perhaps after creating a
    copy, your choice!) before you’ll be able to make any content changes to
    your new site.
  </li>
);

const Title = () => {
  const { basepathModule, migrations, links, lists } =
    useContext(MigrationContext);

  const { active, needsReview, completed, skipped, refresh } = lists.reduce(
    (counts, list) => {
      counts[list.name] = list.currentRows.length;
      return counts;
    },
    {},
  );

  // Lists not yet initialized (or everything is skipped).
  if (!active && !needsReview && !completed) {
    return null;
  }

  // Compute the fraction of migrations with messages.
  const distinctMigrationsWithMessages = links.hasOwnProperty(
    'migration-messages',
  )
    ? links['migration-messages']['data-distinct-migrations']
    : 0;
  const fraction = distinctMigrationsWithMessages / migrations.length;

  // Do not show a "everything needs review or better" or "everything completed or better" UI string if the customer has
  // migration messages for >=5% of their migrations.
  if (!active && fraction > 0.05) {
    const totalMessageCount = links['migration-messages']['data-count'];
    return (
      <div>
        <h2>
          😅 You’re well on your way accelerating your migration to Drupal 9,
          but there are issues you need to address
        </h2>
        <div className="messages messages--warning">
          {distinctMigrationsWithMessages} migrations have {totalMessageCount}{' '}
          unresolved migration messages.{' '}
          <a href={links['migration-messages'].href}>
            Please investigate these.
          </a>
        </div>
      </div>
    );
  }

  if (completed) {
    return (
      <h2>
        <span role="img" aria-label="Time to party!">
          🎉🎉
        </span>{' '}
        Hooray! You've accelerated your migration to Drupal 9!
      </h2>
    );
  } else {
    return (
      <h2>
        🎉🎉 Congrats, you're almost done accelerating your migration to Drupal
        9!
      </h2>
    );
  }
};

const MessageNeedsReview = () => (
  <div className="migration_overview">
    <Title></Title>
    <p>
      To help get the remaining work finished, get Drupal migration experts
      involved.
    </p>
  </div>
);

const MessageCompleted = () => (
  <div className="migration_overview">
    <Title></Title>
    <p>
      Migrating a Drupal site has never been easy, and we hope that using the{' '}
      <strong>
        Acquia Migrate: <em>Accelerate</em>
      </strong>{' '}
      has made this process go smoothly and gave you a big head start.
    </p>
    <p>
      We know that this is not the end of the work to have your site ready for
      production, but the fact that you got here is a real achievement!
    </p>
    <p>
      Now that your content is in Drupal 9, for future releases you will only
      need to update the codebase without any changes to the data models or any
      of your content.
    </p>
    <p>
      To help get the remaining work finished, get Drupal migration experts
      involved.
    </p>
  </div>
);

const OverviewActive = ({ active, needsReview, completed }) => {
  const { basepathModule, links, lists } = useContext(MigrationContext);
  const toNeedsReview = lists.find((list) => list.name === 'needsReview').to;

  // In progress still has migrations.
  if (active) {
    return null;
  }

  if (!active && needsReview) {
    return (
      <div className="migration_overview">
        <Title></Title>
        <p>
          Migrating a Drupal site is a lot of work, and we hope that using the{' '}
          <strong>
            Acquia Migrate: <em>Accelerate</em>
          </strong>{' '}
          has made this task less daunting.
        </p>
        <p>
          You are now well on your way with the move from Drupal 7 to Drupal 9,
          and remember you will never have to do a big migration like this one
          again!
          <br />
          When Drupal 10 releases you will only need to update the codebase
          without any changes to the data models or any of your content.
        </p>
        <ol>
          <li>
            <strong style={{ textDecoration: 'underline' }}>
              Review your migration
            </strong>
            <br />
            <ul>
              <MessageItem links={links} />
              <li>
                <span role="img" aria-label="Review any incomplete Migrations">
                  🔬
                </span>{' '}
                {needsReview} migrations{' '}
                <Link to={toNeedsReview}>Need Review</Link>. You may need to fix
                things related to those migrations.
              </li>
            </ul>
            <br />
          </li>
          <li>
            <strong style={{ textDecoration: 'underline' }}>
              Build the parts that can’t be migrated automatically
            </strong>
            <br />
            <ul>
              <ModuleItem basepathModule={basepathModule} />
              <KnownIssuesItem />
              <ThemingItem />
            </ul>
            <br />
          </li>
          <li>
            <strong style={{ textDecoration: 'underline' }}>
              Moving your site to Dev or Stage
            </strong>
            <br />
            <ul>
              <RefreshingItem />
              <LaunchingItem />
            </ul>
            <br />
          </li>
        </ol>
      </div>
    );
  }
  if (!active && !needsReview && completed) {
    return (
      <div className="migration_overview">
        <Title></Title>
        <h2>
          <span role="img" aria-label="Time to party!">
            🎉🎉
          </span>{' '}
          Hooray! All your migrations are completed!
        </h2>
        <p>
          Migrating a Drupal site has never been easy, and we hope that using
          the{' '}
          <strong>
            Acquia Migrate: <em>Accelerate</em>
          </strong>{' '}
          has made this process go smoothly and gave you a big head start.
        </p>
        <p>
          We know that this is not the end of the work to have your site ready
          for production, but the fact that you got here is a real achievement!
        </p>
        <p>
          Now that your content is in Drupal 9, for future releases you will
          only need to update the codebase without any changes to the data
          models or any of your content.
        </p>
        <ol>
          <li>
            <strong style={{ textDecoration: 'underline' }}>
              Review your migration
            </strong>
            <br />
            <ul>
              <MessageItem links={links} />
            </ul>
            <br />
          </li>
          <li>
            <strong style={{ textDecoration: 'underline' }}>
              Build the parts that can’t be migrated automatically
            </strong>
            <br />
            <ul>
              <ModuleItem basepathModule={basepathModule} />
              <KnownIssuesItem />
              <ThemingItem />
            </ul>
            <br />
          </li>
          <li>
            <strong style={{ textDecoration: 'underline' }}>
              Moving your site to Dev or Stage
            </strong>
            <br />
            <ul>
              <RefreshingItem />
              <LaunchingItem />
            </ul>
            <br />
          </li>
        </ol>
      </div>
    );
  }

  return null;
};

const OverviewNeedsReview = ({ active, needsReview, completed }) => {
  if (!active && needsReview) {
    return <MessageNeedsReview />;
  }
  if (!active && !needsReview && completed) {
    return <MessageCompleted />;
  }

  if (active && !needsReview) {
    return (
      <div>
        <p>
          No migrations currently <em>need review</em>.
        </p>
      </div>
    );
  }

  return null;
};

const OverviewCompleted = ({ active, needsReview, completed }) => {
  if (!active && needsReview) {
    return <MessageNeedsReview />;
  }
  if (!active && !needsReview && completed) {
    return <MessageCompleted />;
  }

  if (active && !completed) {
    return (
      <div>
        <p>
          No migrations currently <em>completed</em>.
        </p>
      </div>
    );
  }

  return null;
};

const OverviewSkipped = ({ skipped }) => {
  if (!skipped) {
    return (
      <div>
        <p>
          No migrations currently <em>skipped</em>.
        </p>
      </div>
    );
  }

  return null;
};

const OverviewRefresh = ({ refresh }) => {
  if (!refresh) {
    return (
      <div>
        <p>There are currently no migrations to refresh.</p>
        <p>
          If the Drupal 7 source application has new data, please refresh the
          source database.
        </p>
      </div>
    );
  }

  return null;
};

const MigrationOverview = ({ name }) => {
  const { migrations, lists } = useContext(MigrationContext);

  // Currently only showing a finished message for "In Progress".
  if (!migrations.length) {
    return null;
  }

  const { active, needsReview, completed, skipped, refresh } = lists.reduce(
    (counts, list) => {
      counts[list.name] = list.currentRows.length;
      return counts;
    },
    {},
  );

  // Lists not yet initialized (or everything is skipped).
  if (!active && !needsReview && !completed) {
    return null;
  }

  switch (name) {
    case 'active':
      return (
        <OverviewActive
          active={active}
          needsReview={needsReview}
          completed={completed}
        />
      );
    case 'needsReview':
      return (
        <OverviewNeedsReview
          active={active}
          needsReview={needsReview}
          completed={completed}
        />
      );
    case 'completed':
      return (
        <OverviewCompleted
          active={active}
          needsReview={needsReview}
          completed={completed}
        />
      );
    case 'skipped':
      return <OverviewSkipped skipped={skipped} />;
    case 'refresh':
      return <OverviewRefresh refresh={refresh} />;
    default:
      return null;
  }
};

export default MigrationOverview;

MessageItem.propTypes = {
  links: PropTypes.objectOf(
    PropTypes.shape({
      href: PropTypes.string,
      title: PropTypes.string,
      rel: PropTypes.string,
      type: PropTypes.string,
    }),
  ),
};

MessageItem.defaultProps = {
  links: null,
};

Title.propTypes = {};

Title.defaultProps = {};

RefreshingItem.propTypes = {};

RefreshingItem.defaultProps = {};

LaunchingItem.propTypes = {};

LaunchingItem.defaultProps = {};

OverviewActive.propTypes = {
  active: PropTypes.number.isRequired,
  needsReview: PropTypes.number.isRequired,
  completed: PropTypes.number.isRequired,
};

OverviewNeedsReview.propTypes = {
  active: PropTypes.number.isRequired,
  needsReview: PropTypes.number.isRequired,
  completed: PropTypes.number.isRequired,
};

OverviewCompleted.propTypes = {
  active: PropTypes.number.isRequired,
  needsReview: PropTypes.number.isRequired,
  completed: PropTypes.number.isRequired,
};

OverviewSkipped.propTypes = {
  skipped: PropTypes.number.isRequired,
};

OverviewRefresh.propTypes = {
  refresh: PropTypes.number.isRequired,
};

OverviewRefresh.defaultProps = {};

MigrationOverview.propTypes = {
  name: PropTypes.string.isRequired,
};
