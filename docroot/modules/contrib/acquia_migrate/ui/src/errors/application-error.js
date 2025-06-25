export default class ApplicationError extends Error {
  /**
   * @type {string}
   */
  #description;

  /**
   * Describes an error caused by the application.
   *
   * @param {string} description
   */
  constructor(description) {
    super();
    this.#description = description;
  }

  get description() {
    return this.#description;
  }
}
