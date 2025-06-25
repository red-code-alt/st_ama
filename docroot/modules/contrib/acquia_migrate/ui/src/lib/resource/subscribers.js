export default class Subscribers {
  /**
   * @callback subscriberFunction
   * @param {*} argument
   *   A single argument.
   */

  /**
   * @callback unsubscribeFunction
   * @param {*} argument
   *   A single argument.
   */

  /**
   * Creates a subscribers object.
   */
  constructor() {
    this.pollers = [];
  }

  /**
   * Adds a new subscribing function.
   *
   * @param {subscriberFunction} subscriberFn
   *   A new subscriber. Whenever an update is sent to all subscribers, this
   *   method will be called.
   *
   * @return {unsubscribeFunction}
   *   An unsubscribe function. Once this function is called, the given
   *   subscriber function will not be called again.
   */
  add(subscriberFn) {
    const newItem = { fn: subscriberFn };
    this.pollers.push(newItem);
    return () => {
      this.pollers = this.pollers.filter((item) => {
        return item !== newItem;
      });
    };
  }

  /**
   * Sends an update to all subscribing functions.
   *
   * @param {*} update
   *   A single argument that will be passed once to all subscribing functions.
   */
  send(update) {
    this.pollers.forEach((subscriber) => subscriber.fn(update));
  }

  /**
   * Gets the current subscriber count.
   *
   * @return {int}
   *   The current number of subscribing functions.
   */
  count() {
    return this.pollers.length;
  }
}
