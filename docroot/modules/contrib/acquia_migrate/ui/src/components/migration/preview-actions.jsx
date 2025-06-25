import React, { useState } from 'react';
import PropTypes from 'prop-types';

import { formatURL, getQuery } from '../../lib/uri';
import ClaroTextInput from '../claro/text-input';

const PreviewOffset = ({ id, href, title, setHref, setEvent }) => (
  <a
    className={`${id} button button--small`}
    role="button"
    href="#"
    onClick={(e) => {
      e.preventDefault();
      setHref(href);
      setEvent({
        type: 'User previewed row',
        migrationPreviewQuery: getQuery(href),
      });
    }}
  >
    {title}
  </a>
);

const PreviewActions = ({ link, setHref, setEvent, offsetLinks }) => {
  const [value, setValue] = useState('');
  const { label, variable } = link['uri-template:suggestions'][0];
  const submit = () => {
    setEvent({
      type: 'User previewed by search',
      migrationPreviewSearchTerm: value,
    });
    setHref(formatURL({ [variable]: value }, link['uri-template:href']));
  };

  const handleChange = (e) => {
    setValue(e.target.value);
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    submit(value);
  };

  return (
    <form
      className="exposed-form exposed-form--labeled"
      onSubmit={handleSubmit}
    >
      <ClaroTextInput
        name="preview_search"
        label={label}
        size="40"
        type="search"
        value={value}
        validation={{ pattern: '(https?://|^/)(.*)' }}
        onChange={handleChange}
      >
        Source site URL should be either <em>relative</em> (begin with{' '}
        <code>/</code>)<br />
        or <em>absolute</em> (begin with <code>http://</code>)
      </ClaroTextInput>
      <div className="form-actions form-item exposed-form__item--actions">
        <input type="submit" className="button form-submit" value="Go" />
      </div>
      <div className="form-item exposed-form__item--divider">
        <strong>or</strong>
      </div>
      <div className="form-item">
        <label className="form-item__label">Browse available rows</label>
        <div
          className="migration__preview__links"
          role="group"
          aria-label="Offset rows"
        >
          {offsetLinks.map(({ id, href, title }) => (
            <PreviewOffset
              key={`offset-${id}`}
              id={id}
              href={href}
              title={title}
              setHref={setHref}
              setEvent={setEvent}
            />
          ))}
        </div>
      </div>
    </form>
  );
};

export default PreviewActions;

PreviewOffset.propTypes = {
  id: PropTypes.string.isRequired,
  href: PropTypes.string.isRequired,
  title: PropTypes.oneOfType([PropTypes.string, PropTypes.node]).isRequired,
  setHref: PropTypes.func.isRequired,
  setEvent: PropTypes.func,
};

PreviewOffset.defaultProps = {
  setEvent: () => {},
};

PreviewActions.propTypes = {
  link: PropTypes.shape({
    rel: PropTypes.string,
    href: PropTypes.string,
    title: PropTypes.string,
    'uri-template:href': PropTypes.string,
    'uri-template:suggestions': PropTypes.arrayOf(PropTypes.object),
  }).isRequired,
  offsetLinks: PropTypes.arrayOf(PropTypes.object),
  setHref: PropTypes.func.isRequired,
  setEvent: PropTypes.func,
};

PreviewActions.defaultProps = {
  offsetLinks: [],
  setEvent: () => {},
};
