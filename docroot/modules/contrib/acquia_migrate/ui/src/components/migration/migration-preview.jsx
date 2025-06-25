import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { useAsync } from 'react-async';

import PreviewRaw from './preview-raw';
import PreviewLive from './preview-live';
import ClaroTwoColumn from '../claro/two-column';
import ClaroPanel from '../claro/panel';
import ClaroThrobber from '../claro/throbber';
import PreviewActions from './preview-actions';
import { getQuery } from '../../lib/uri';
import { getResource } from '../../lib/utils';

const getPreview = (_, { href }) => getResource({ href });

const Previewable = ({ links }) => {
  const offsetLink = { ...links['preview-by-offset'], id: 'next' };
  const urlLink = links['preview-by-url'];
  const [offsetLinks, setOffsetLinks] = useState([offsetLink]);
  const [attributes, setAttributes] = useState({ raw: [], html: '' });
  const [href, setHref] = useState(offsetLink.href);
  // Set offsetLink event on load.
  const [event, setEvent] = useState({
    type: 'User previewed row',
    migrationPreviewQuery: getQuery(offsetLink.href),
  });

  const { data: response, run } = useAsync({
    deferFn: getPreview,
    href,
  });

  useEffect(() => {
    if (href) {
      run();
    }
  }, [href]);

  useEffect(() => {
    if (response) {
      let status = '';
      if (response instanceof Error) {
        status = response.message;
        console.error(response);
        if (href !== offsetLink.href) {
          setHref(offsetLink.href);
        }
      } else {
        status = 'OK';
        const { data } = response;
        const { attributes, links } = data;
        if (links) {
          setOffsetLinks(
            Object.entries(links).map(([key, value]) => ({
              ...value,
              id: key,
            })),
          );
        } else {
          setOffsetLinks([offsetLink]);
        }
        setAttributes(attributes);
      }
    }
  }, [response]);

  if (attributes) {
    const panelRawPreview = () => (
      <ClaroPanel padding={false}>
        <PreviewRaw list={attributes.raw} />
      </ClaroPanel>
    );

    const panelLivePreview = () => (
      <ClaroPanel header="Content Preview" padding={false}>
        <PreviewLive html={attributes.html} />
      </ClaroPanel>
    );

    return (
      <React.Fragment>
        <p>
          Preview source site content in Drupal 9 by searching for a URL or by
          viewing the next available row.
        </p>
        <PreviewActions
          link={urlLink}
          setHref={setHref}
          setEvent={setEvent}
          offsetLinks={offsetLinks}
        />
        <ClaroTwoColumn one={panelRawPreview()} two={panelLivePreview()} />
      </React.Fragment>
    );
  }
};

const NotPreviewable = ({ preview }) => (
  <div className="migration__preview--no-preview">
    {Object.entries(preview).map(([key, reason]) => (
      <div key={key} className="messages messages--warning">
        <p>{reason.title}</p>
      </div>
    ))}
  </div>
);

const MigrationPreview = ({ preview }) => (
  <div className="migration__preview">
    {Object.values(preview.links).length > 0 ? (
      <Previewable links={preview.links} />
    ) : (
      <NotPreviewable preview={preview.unmet} />
    )}
  </div>
);
export default MigrationPreview;

Previewable.propTypes = {
  links: PropTypes.shape({
    'preview-by-offset': PropTypes.object,
    'preview-by-url': PropTypes.object,
  }).isRequired,
};

NotPreviewable.propTypes = {
  preview: PropTypes.objectOf(PropTypes.object).isRequired,
};

MigrationPreview.propTypes = {
  preview: PropTypes.shape({
    links: PropTypes.object,
    unmet: PropTypes.object,
  }).isRequired,
};

Previewable.defaultProps = {};

MigrationPreview.defaultProps = {};
