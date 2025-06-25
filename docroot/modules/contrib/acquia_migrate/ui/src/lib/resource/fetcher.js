import ServerError from '../../errors/server-error';
import ClientError from '../../errors/client-error';
import APIError from '../../errors/api-error';
import ApplicationError from '../../errors/application-error';
import 'isomorphic-fetch';
import 'fetch-retry';

// Fetcher is used to constantly update collections (of migrations and messages). To be resilient against ephemeral
// infrastructure problems, retry 500 responses (caused by e.g. DB not being available temporarily) 9 times, 5 s apart.
var originalFetch = require('isomorphic-fetch');
var fetchWithRetry = require('fetch-retry')(originalFetch, {
  retries: 9,
  retryDelay: 5000,
  retryOn: [500],
});

/**
 * @param {Response} response
 */
const throwErrorForResponse = async (response) => {
  const isJsonApi = response.headers
    .get('Content-Type')
    .startsWith('application/vnd.api+json');
  const errors = isJsonApi ? (await response.json()).errors : [];
  const requestID = response.headers.get('X-Request-ID');
  if (response.status >= 500) {
    throw new ServerError(
      response.status,
      response.statusText,
      requestID,
      errors,
    );
  }
  if (response.status >= 400) {
    throw new ClientError(
      response.status,
      response.statusText,
      requestID,
      errors,
    );
  }
  if (isJsonApi) {
    throw new APIError(response.status, response.statusText, requestID);
  }

  throw new ApplicationError('Unrecognized response content type.');
};

/**
 * Class for handling on-demand and polling fetches.
 */
export default class Fetcher {
  /**
   * Creates a fetcher.
   *
   * @param {object} options
   *   The fetcher configuration:
   * @param {string} options.href
   *   The URL for the fetcher to fetch.
   * @param {updateFunction} options.updateFn
   *   A callback function that will be called whenever the fetcher resolves a
   *   request document.
   * @param {errorHandler} options.handleError
   *   A callback function that will be called whenever the fetcher encounters
   *   a fetch error.
   * @param {?Function} options.fetchFn
   *   Only used in tests.
   */
  constructor({ href, updateFn, handleError, fetchFn = null }) {
    this.href = href;
    this.updateFn = updateFn;
    this.handleError = handleError;
    this.fetchFn =
      fetchFn || ((href, options) => fetchWithRetry(href, options));

    this.lastDocument = null;
    this.inStasis = false;
    this.keepFetching = false;
    this.etag = '';
    // A timeout, in milliseconds, to wait before issuing a new request if the
    // last received response was different from the one before it.
    this.timeoutInStasis = 2000;
    // A timeout, in milliseconds, to wait before issuing a new request if the
    // last received response was the same as the one before it.
    this.timeoutInFlux = 500;
    // A timeout, in milliseconds, to wait before issuing a new request while
    // the user's browser is offline.
    this.timeOutWhileOffline = 5000;
  }

  /**
   * Gets a response document.
   *
   * @return {Promise<object>}
   *   A promise that resolves to a JSON:API response document object, unless
   *   an error occurred. If and error occurs, the promise will not resolve and
   *   the fetcher's error handler will called.
   */
  get() {
    const options = {
      method: 'GET',
      headers: {
        Accept: 'application/vnd.api+json',
      },
    };

    if (this.etag.length) {
      Object.assign(options.headers, {
        'If-None-Match': this.etag,
      });
    }

    return this.fetchFn(this.href, options)
      .then((response) => {
        // The notModified variable is necessary so that it can be in the scope
        // of the resolved response.json() promise below. If this.inStasis were
        // used instead, there would be a possibility for a race condition and
        // updates might get missed.
        const notModified = (this.inStasis = response.status === 304);

        if (notModified) {
          return this.lastDocument;
        }
        if (!response.ok) {
          this.stopPolling();
          return throwErrorForResponse(response);
        }
        if (response.headers.has('etag')) {
          this.etag = response.headers.get('etag');
        }

        return response.json().then((document) => {
          // If the fetcher is polling and the system is not in stasis, then
          // call the update function. Doing this in get() instead of poll()
          // means that if a get() was called directly its result can be used to
          // immediately update subscribers, even if the poll system is in the
          // middle of a timeout or if a poll request is not yet resolved.
          if (this.keepFetching && !notModified) {
            this.updateFn(document);
          }
          return (this.lastDocument = document);
        });
      })
      .catch(() => {
        if (window.navigator.onLine) {
          this.handleError();
        }
      });
  }

  /**
   * Starts the fetcher's polling cycle.
   */
  startPolling() {
    if (this.keepFetching) {
      // The fetcher is already polling.
      return;
    }
    this.keepFetching = true;
    const pollFn = () => {
      this.get().then(() => {
        // If polling has been stopped since the request started, don't start
        // another cycle.
        if (this.keepFetching) {
          // After a timeout, begin another polling cycle. The timeout length
          // can be configured to be more or less frequent depending on whether
          // the last received response was different than the last.
          let timeout = this.inStasis
            ? this.timeoutInStasis
            : this.timeoutInFlux;
          if (!window.navigator.onLine) {
            timeout = this.timeOutWhileOffline;
          }
          setTimeout(pollFn, timeout);
        }
      });
    };
    pollFn();
  }

  /**
   * Stops the fetcher's polling cycle.
   */
  stopPolling() {
    this.keepFetching = false;
  }
}
