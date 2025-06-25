/**
 * Class for keeping track of subscribing functions.
 */
import { formatURL } from '../uri';
import Fetcher from './fetcher';
import Subscribers from './subscribers';

/**
 * A JSON:API resource.
 */
export default class Resource {
  /**
   * @callback updateFunction
   * @param {object} document
   *   A JSON:API response document object.
   */

  /**
   * @callback errorHandler
   * @param {object} error
   *   A fetch error object.
   */

  /**
   * Creates a new resource.
   *
   * @param {object} options
   *   The resource configuration:
   * @param {errorHandler} options.handleError
   *   A callback function that will be called if an HTTP error occurs.
   * @param {?string} options.href
   *   The URL of the resource to construct. Required unless a URI template is
   *   given.
   * @param {?string} options.uriTemplate
   *   A URI template for the resource to construct. Required unless an href is
   *   given.
   * @param {?object} options.uriTemplateParams
   *   A parameter object to be used in a URI template expansion. Optional.
   */
  constructor({
    handleError,
    href = null,
    uriTemplate = null,
    uriTemplateParams = {},
  }) {
    if (!href && !uriTemplate) {
      throw new Error('An href or a URI template is required.');
    }
    this.subscribers = new Subscribers();
    this.uriTemplateParams = uriTemplateParams;
    this.fetcher = new Fetcher({
      href: uriTemplate ? formatURL(this.uriTemplateParams, uriTemplate) : href,
      updateFn: (document) => {
        this.document = document;
        this.subscribers.send(document);
      },
      handleError,
    });
    this.document = null;
  }

  /**
   * Gets a response document.
   *
   * @param {boolean} refresh
   *   Whether to fetch a new response. If false and a response is already
   *   available, it will be reused.
   *
   * @return {Promise<Object>}
   */
  get(refresh = false) {
    if (!this.document || refresh) {
      return this.fetcher.get().then((document) => {
        return (this.document = document);
      });
    }
    return Promise.resolve(this.document);
  }

  /**
   * Begins polling the resource.
   *
   * @param {updateFunction} updateFn
   *   A callback function that will be called whenever the resource is updated.
   *
   * @return {Function}
   *   A function that stops polling. After this function is called, the given
   *   update function will not be called again.
   */
  poll({ updateFn }) {
    this.fetcher.startPolling();
    if (this.document) {
      updateFn(this.document);
    }
    const unsubscribe = this.subscribers.add(updateFn);
    return () => {
      unsubscribe();
      // If the subscriber count has reached zero, tell the fetcher to stop
      // fetching.
      if (this.subscribers.count() === 0) {
        this.fetcher.stopPolling();
      }
    };
  }
}
