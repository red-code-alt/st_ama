import { formatURL } from '../uri';
import Fetcher from './fetcher';
import Resource from './resource';

/**
 * A JSON:API collection resource.
 *
 * @extends Resource
 */
export default class CollectionResource extends Resource {
  /**
   * @inheritDoc
   */
  get(refresh = false) {
    return super.get().then((document) => {
      const { links = {} } = document;
      const queryLink = Object.values(links).find(({ rel = null }) => {
        return (
          rel === 'https://drupal.org/project/acquia_migrate#link-rel-query'
        );
      });
      if (queryLink && Object.entries(this.uriTemplateParams).length) {
        const { 'uri-template:href': uriTemplate } = queryLink;
        const templatedHref = formatURL(this.uriTemplateParams, uriTemplate);
        if (this.fetcher.href !== templatedHref) {
          this.fetcher = new Fetcher({
            href: templatedHref,
            updateFn: this.fetcher.updateFn,
            handleError: this.fetcher.handleError,
          });
          return super.get(true);
        }
      }
      return document;
    });
  }

  /**
   * @inheritDoc
   */
  poll({ updateFn }) {
    let aborted = false;
    let stopPolling = () => {
      aborted = true;
    };
    this.get().then(() => {
      if (!aborted) {
        stopPolling = super.poll({ updateFn });
      }
    });
    return () => {
      stopPolling();
    };
  }
}
