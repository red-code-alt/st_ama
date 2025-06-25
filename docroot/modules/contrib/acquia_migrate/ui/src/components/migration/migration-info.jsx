import React, { useContext, useEffect, useState } from 'react';
import PropTypes from 'prop-types';

import SwooshyWooshy from './swooshy-wooshy';
import useOffset from '../../hooks/use-offset';
import QueueStatus from './queue-status';
import QueueControls from './queue-controls';
import Icon from '../icon';
import { MigrationContext } from '../../contexts/migrations';
import { progressFormat, progressAll } from '../../lib/utils';

const sumCounts = (migrations, attr) =>
  migrations.reduce((acc, cur) => acc + cur[attr], 0);

const getDetailInfo = (migration) => {
  const { label, links: infoLinks } = migration;
  return { label, infoLinks };
};

const overrideLink = (link) => {
  const overrides = {
    'https://drupal.org/project/acquia_migrate#link-rel-migration-messages': {
      title: `Total errors: ${link.title}`,
    },
  };
  return overrides.hasOwnProperty(link.rel)
    ? overrides[link.rel]
    : { title: link.title };
};

const InfoLink = ({ href, title }) => (
  <a href={href} title={title} className="admin-item__link">
    {title}
  </a>
);

const MigrationInfo = ({ migrations }) => {
  const { active, links, controllingSession } = useContext(MigrationContext);
  const [isExpanded, setExpanded] = useState(true);
  const offset = useOffset();

  const progress = progressAll(migrations);
  const importedCount = sumCounts(migrations, 'importedCount');
  const totalCount = sumCounts(migrations, 'totalCount');

  const isDetail = migrations.length === 1;
  const { label, infoLinks } = isDetail
    ? getDetailInfo(migrations[0])
    : { label: null, infoLinks: links };

  const anchors = infoLinks
    ? Object.entries(infoLinks)
        .filter(([, value]) => value.type === 'text/html')
        .map(([key, value]) => {
          const { title } = overrideLink(value);
          return {
            id: key,
            ...value,
            title: isDetail ? title : value.title,
          };
        })
    : [];

  const totalMessagesAnchor = anchors.find(
    (anchor) => anchor.id === 'migration-messages',
  );

  const isActive = active.hasOwnProperty('id');
  const title = `Data ${isActive ? 'importing' : 'imported'}`;

  const toggle = () => {
    setExpanded(!isExpanded);
  };

  useEffect(() => {
    document.documentElement.style.setProperty('--offset-top', `${offset}px`);
  }, [offset]);

  return (
    <div
      className={`migration_info ${
        isExpanded ? 'is-expanded' : 'is-collapsed'
      }`}
    >
      <button className="migration_info__toggle" onClick={toggle}>
        <Icon icon="expander" fill="none" stroke="none" />
      </button>
      <SwooshyWooshy
        active={controllingSession !== null}
        amount={progressFormat(progress)}
      />
      <div className="migration_info__data">
        <h5 className="migration_info__title">
          {title}
          {label ? ` (${label})` : ''}
          <span>
            :{' '}
            <span className="tabular">
              {importedCount}/{totalCount}
            </span>
          </span>
        </h5>
        <ul className="migration_info__list">
          {anchors.map((anchor) => (
            <li key={anchor.id}>
              <InfoLink title={anchor.title} href={anchor.href} />
            </li>
          ))}
          {totalMessagesAnchor && (
            <li>
              <em>
                <small>
                  ↪ across {totalMessagesAnchor['data-distinct-migrations']}{' '}
                  migrations
                </small>
              </em>
            </li>
          )}
        </ul>
        {!isDetail && (
          <div className="migration_info__queue">
            <QueueStatus />
            <QueueControls />
          </div>
        )}
      </div>
    </div>
  );
};

export default MigrationInfo;

InfoLink.propTypes = {
  title: PropTypes.string.isRequired,
  href: PropTypes.string.isRequired,
};

MigrationInfo.propTypes = {
  migrations: PropTypes.arrayOf(
    PropTypes.shape({
      id: PropTypes.string,
      index: PropTypes.number,
      label: PropTypes.string,
      consistsOf: PropTypes.arrayOf(PropTypes.object),
      dependencies: PropTypes.arrayOf(PropTypes.object),
      lastImported: PropTypes.object,
      links: PropTypes.object,
      importedCount: PropTypes.number,
      processedCount: PropTypes.number,
      totalCount: PropTypes.number,
      skipped: PropTypes.bool,
      completed: PropTypes.bool,
      status: PropTypes.object,
    }),
  ),
};

MigrationInfo.defaultProps = {
  migrations: [],
};
