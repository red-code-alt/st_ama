import React from 'react';
import PropTypes from 'prop-types';

import Icon from '../icon';
import ExtLink from '../ext-link';
import useToggle from '../../hooks/use-toggle';
import { getExtLinks } from '../../lib/utils';

// Compare ids for memoization, module contents never change.
const compareModuleId = (prev, next) => prev.id === next.id;

const SourceModule = ({ title, version }) => (
  <div className="datagrid__item">
    <span className="source-module__title">
      {title} {version}
    </span>
  </div>
);

const RecommendationModule = ({ title, version, link, icon, help }) => (
  <div className="datagrid__item">
    <span className="recommendation-module__title">
      {link ? (
        <ExtLink href={link.href} title={title}>
          {title} {version}
        </ExtLink>
      ) : (
        <span>
          {title} {version}
        </span>
      )}
      {icon ? (
        <span title={help} className="title__badge">
          <Icon icon={icon} stroke="none" size="18" />
        </span>
      ) : null}
    </span>
  </div>
);

const RecommendationInstruction = ({ instruction }) =>
  typeof instruction === 'object' ? (
    <ExtLink title={instruction.title} href={instruction.href}>
      {instruction.title}
    </ExtLink>
  ) : (
    <span>{instruction}</span>
  );

const RecommendationStatus = ({ installLabel, instructions }) => (
  <div className="datagrid__item">
    <span
      className={`module-recommendations__install-status module-recommendations__install-status--${installLabel.modifier}`}
      title={installLabel.title}
    >
      {installLabel.label}
    </span>
    {instructions ? (
      <div className="module-recommendations__install-instructions">
        {instructions.instruction ? (
          <RecommendationInstruction instruction={instructions.instruction} />
        ) : null}
        {instructions.detail ? (
          <>
            <br />
            <code>{instructions.detail}</code>
          </>
        ) : null}
      </div>
    ) : null}
  </div>
);

const RecommendationModules = ({ id, modules, link, vetted }) => (
  <ul className="module-recommendations__module-list">
    {modules.map((module) => (
      <li
        className="module-recommendations__row-inner module-recommendations__module-list-item"
        key={`${id}-${encodeURIComponent(module.displayName).toLowerCase()}`}
      >
        <RecommendationModule
          title={module.displayName}
          version={module.version}
          link={link}
          icon={vetted ? 'vetted' : null}
          help={vetted ? 'Migration path vetted by Acquia' : null}
        />
        <RecommendationStatus
          installLabel={module.installLabel}
          instructions={module.installInstructions}
        />
      </li>
    ))}
  </ul>
);

const Recommendation = ({ recommendation }) => {
  const { id, type, modules, note, vetted, links } = recommendation;
  const extLinks = links ? getExtLinks(links) : [];
  const link = extLinks[0] || null;

  return (
    <div className="module-recommendations__recommendation">
      {modules.length || type === 'abandonmentRecommendation' ? (
        <div className="module-recommendations__recommendation-header">
          {modules.length ? (
            <RecommendationModules
              id={id}
              modules={modules}
              link={link}
              vetted={vetted}
            />
          ) : null}
          {type === 'abandonmentRecommendation' ? (
            <RecommendationModule
              title="Module obsolete or not recommended"
              icon="abandoned"
            />
          ) : null}
        </div>
      ) : null}
      {note ? (
        <div className="datagrid__item">
          <p className="module-recommendations__recommendation-note">{note}</p>
        </div>
      ) : null}
    </div>
  );
};

const ModuleRow = ({ module, columns }) => {
  const [expanded, toggle] = useToggle(false);
  const { title, version, replacementCandidates } = module;
  const hasDetails =
    columns.includes('status') &&
    replacementCandidates.some(
      (replacement) =>
        replacement.modules &&
        replacement.modules.some(
          (replacementModule) => !!replacementModule.installInstructions,
        ),
    );

  return (
    <div
      className={`module-recommendations__row datagrid__row ${
        expanded ? 'is-expanded' : 'is-collapsed'
      }`}
    >
      {columns.includes('source') ? (
        <SourceModule title={title} version={version} />
      ) : null}
      {columns.includes('destination') ? (
        <div className="datagrid__inner">
          {replacementCandidates.map((candidate) => (
            <Recommendation
              key={`${candidate.category}_${candidate.id}_${
                candidate.modules
                  ? candidate.modules
                      .map((candidateModule) => candidateModule.machineName)
                      .join('_')
                  : `${title}-${version}`
              }`}
              recommendation={candidate}
            />
          ))}
        </div>
      ) : null}
      {hasDetails ? (
        <a
          className="dropdown-toggle"
          role="button"
          data-toggle="dropdown"
          aria-haspopup="true"
          aria-expanded={expanded}
          onClick={toggle}
        >
          <Icon icon="chevron-down" />
        </a>
      ) : null}
    </div>
  );
};

export default React.memo(ModuleRow, compareModuleId);

SourceModule.propTypes = {
  title: PropTypes.string.isRequired,
  version: PropTypes.string,
};

SourceModule.defaultProps = {
  version: null,
};

RecommendationModule.propTypes = {
  title: PropTypes.string.isRequired,
  version: PropTypes.string,
  link: PropTypes.shape({
    href: PropTypes.string,
    type: PropTypes.string,
  }),
  icon: PropTypes.string,
  help: PropTypes.string,
};

RecommendationModule.defaultProps = {
  version: null,
  link: null,
  icon: null,
  help: null,
};

RecommendationInstruction.propTypes = {
  instruction: PropTypes.oneOfType([
    PropTypes.string,
    PropTypes.shape({
      href: PropTypes.string,
      title: PropTypes.string,
    }),
  ]).isRequired,
};

RecommendationStatus.propTypes = {
  installLabel: PropTypes.shape({
    label: PropTypes.string,
    title: PropTypes.string,
    modifier: PropTypes.string,
  }).isRequired,
  instructions: PropTypes.shape({
    instruction: PropTypes.oneOfType([
      PropTypes.string,
      PropTypes.shape({
        href: PropTypes.string,
        title: PropTypes.string,
      }),
    ]),
    detail: PropTypes.string,
  }),
};

RecommendationStatus.defaultProps = {
  instructions: null,
};

RecommendationModules.propTypes = {
  id: PropTypes.string.isRequired,
  modules: PropTypes.arrayOf(
    PropTypes.shape({
      displayName: PropTypes.string,
      installLabel: PropTypes.shape({
        label: PropTypes.string,
        title: PropTypes.string,
        version: PropTypes.string,
        modifier: PropTypes.string,
      }),
      machineName: PropTypes.string,
    }),
  ).isRequired,
  link: PropTypes.shape({
    href: PropTypes.string,
    type: PropTypes.string,
  }),
  vetted: PropTypes.bool.isRequired,
};

RecommendationModules.defaultProps = {
  link: null,
};

Recommendation.propTypes = {
  recommendation: PropTypes.shape({
    note: PropTypes.string,
    vetted: PropTypes.bool,
    modules: PropTypes.arrayOf(
      PropTypes.shape({
        displayName: PropTypes.string,
        installed: PropTypes.bool,
        installLabel: PropTypes.shape({
          label: PropTypes.string,
          title: PropTypes.string,
          modifier: PropTypes.string,
        }),
        installInstructions: PropTypes.oneOfType([
          PropTypes.string,
          PropTypes.object,
        ]),
        machineName: PropTypes.string,
      }),
    ),
    requirePackage: PropTypes.shape({
      packageName: PropTypes.string,
      versionConstraint: PropTypes.string,
    }),
    id: PropTypes.string,
    links: PropTypes.shape({
      about: PropTypes.shape({
        href: PropTypes.string,
        type: PropTypes.string,
      }),
    }),
    type: PropTypes.string,
  }).isRequired,
};

ModuleRow.propTypes = {
  module: PropTypes.shape({
    id: PropTypes.string,
    title: PropTypes.string,
    version: PropTypes.string,
    replacementCandidates: PropTypes.arrayOf(PropTypes.object),
  }).isRequired,
  columns: PropTypes.arrayOf(PropTypes.string).isRequired,
};
