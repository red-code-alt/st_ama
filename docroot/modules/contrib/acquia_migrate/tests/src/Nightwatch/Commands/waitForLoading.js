/**
 * Waits until the loading animation finished.
 *
 * @param {function} callback
 *   An optional callback which will be called when this command completes.
 *
 * @return {object}
 *   The NightwatchAPI instance.
 */
exports.command = function waitForLoading(callback) {
  if (typeof this.globals !== 'object') {
    return this;
  }

  const _self = this;

  this
    // Standard loader.
    .waitForElementNotPresent({
      locateStrategy: 'xpath',
      selector: "//*[contains(concat(' ', normalize-space(@class)), ' loading--pending')]"
    })

  if (typeof callback === 'function') {
    callback.call(_self);
  }

  return this;
};
