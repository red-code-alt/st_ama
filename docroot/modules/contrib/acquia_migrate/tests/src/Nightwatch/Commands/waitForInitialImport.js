/**
 * Waits until Acquia Migrate Accelerate's initial import is completed.
 *
 * @param {string} selector
 *   The selector inside the initial import element. Optional, defaults to
 *   '.initial_import .progress-bar'.
 * @param {number|null} appearTimeout
 *   The timeout in milliseconds we should wait till the initial import element
 *   shows up. Optional, defaults to the value of `waitForConditionTimeout`.
 * @param {number} disappearTimeout
 *   The timeout in milliseconds we should wait till the initial import element
 *   disappears. Optional, defaults to 60000 (60 seconds).
 * @param {function} callback
 *   An optional callback which will be called when this command completes.
 *
 * @return {object}
 *   The NightwatchAPI instance.
 *
 * @see \Drupal\Tests\acquia_migrate\Traits\MigrateJsUiTrait::visitMigrationDashboard()
 */
exports.command = function waitForInitialImport(
  selector = '.initial_import .progress',
  appearTimeout,
  disappearTimeout = 120 * 1000,
  callback
) {
  if (typeof this.globals !== 'object') {
    return this;
  }

  const _self = this;

  this
    .waitForLoading()
    .waitForElementPresent(selector, appearTimeout)
    .waitForElementNotPresent(selector, disappearTimeout);

  if (typeof callback === 'function') {
    callback.call(_self);
  }

  return this;
};
