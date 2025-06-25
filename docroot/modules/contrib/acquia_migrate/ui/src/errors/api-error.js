import ApplicationError from './application-error';

export default class APIError extends ApplicationError {
  /**
   * @typedef APIErrorObject
   * @type {object}
   * @property {string} status - the HTTP status code applicable to this problem, expressed as a string value.
   * @property {string} detail - a human-readable explanation specific to this occurrence of the problem.
   */

  /**
   * @type {APIErrorObject[]}
   */
  #errors = [];

  /**
   * @type {int}
   */
  #status;

  /**
   * @type {string}
   */
  #reason;

  /**
   * @type {?string}
   */
  #requestId;

  /**
   * @type {object}
   */
  #suggestion;

  /**
   * Creates a new APIError.
   *
   * @param {int} statusCode
   *   The HTTP status code.
   * @param {string} reasonPhrase
   *   The HTTP reason phrase.
   * @param {?string} requestId
   *   The request ID (X-Request-Id response header), if available.
   * @param errors
   *   The JSON:API errors object, if available.
   */
  constructor(statusCode, reasonPhrase, requestId = null, errors = []) {
    super(reasonPhrase);
    this.#status = statusCode;
    this.#reason = reasonPhrase;
    this.#requestId = requestId;
    this.#errors = errors;
  }

  get errors() {
    return this.#errors;
  }

  get status() {
    return this.#status;
  }

  get requestId() {
    return this.#requestId;
  }

  get reason() {
    return this.#reason;
  }

  /**
   * Offer a recommendation for an error response.
   *
   * @return {object|null}
   *   The recommendation if available.
   */
  get suggestion() {
    return this.#status === 500
      ? {
          text: 'A 500 error indicates that there is a problem with Drupal.',
          link: {
            title: 'Check recent log messages for more info.',
            href: '/admin/reports/dblog',
          },
        }
      : null;
  }
}
