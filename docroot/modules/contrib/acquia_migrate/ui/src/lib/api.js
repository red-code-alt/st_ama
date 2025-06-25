import ClientError from '../errors/client-error';
import ApplicationError from '../errors/application-error';
import 'isomorphic-fetch';
import 'fetch-retry';

// process() is used to process migrations using batch. To be resilient against ephemeral infrastructure problems, retry
// 500/503/504 responses (caused by e.g. DB not being available temporarily) 9 times, 5 s apart.
var originalFetch = require('isomorphic-fetch');
var fetchWithRetry = require('fetch-retry')(originalFetch, {
  retries: 9,
  retryDelay: 5000,
  retryOn: [500, 503, 504],
});

export const update = ({ href, method, payload }) =>
  fetch(href, {
    method,
    headers: { 'Content-Type': 'application/vnd.api+json' },
    body: JSON.stringify(payload),
  }).then((response) => {
    if (response.status !== 204) {
      console.error(response);
    }
    return response;
  });
export const request = ({ href }) =>
  fetch(href, {
    method: 'GET',
    headers: { 'Content-Type': 'application/vnd.api+json' },
  }).then((response) => {
    if (response.status !== 200) {
      // Not catching this error, returning the response to complete promise.
      console.error(response.statusText);
      return response;
    }
    return response.json().then((document) => document);
  });

export const atomicTransaction = ({ href, type, payload }) =>
  fetch(href, {
    method: 'POST',
    headers: { 'Content-Type': type },
    body: JSON.stringify({
      'atomic:operations': payload,
    }),
  }).then((response) => {
    if (response.status !== 204) {
      console.error(response);
      throw new Error(response.statusText);
    }
    return response.ok;
  });

/**
 * Fetch the batch responses.
 *
 * @param {object} options
 *   The API URL, method, qid and a method to throw an async error.
 * @param {string} options.href
 * @param {string} options.method
 * @param {string} options.qid
 * @param {Function} options.throwError
 *
 * @return {object}
 *   Queue object with current process properties.
 */
export const process = ([options]) => {
  const { href, method, qid, throwError } = options;
  return fetchWithRetry(href, {
    method,
    Accept: 'application/vnd.api+json',
  }).then((response) => {
    if (
      response.headers
        .get('Content-Type')
        .startsWith('application/vnd.api+json')
    ) {
      return response.json().then((document) => {
        const base = {
          qid,
          changed: Date.now(),
        };
        if (response.ok) {
          return {
            ...base,
            progressRatio: document.data.attributes.progressRatio,
            ...document.links,
          };
        }
        if (response.status >= 500) {
          return { ...base, errors: document.errors };
        }
        if (response.status >= 400 && response.status < 500) {
          throwError(
            new ClientError(
              response.status,
              response.statusText,
              response.headers.get('X-Request-ID'),
              document.errors,
            ),
          );
        }
      });
    }
    throwError(new ApplicationError('Unrecognized response content type.'));
  });
};
