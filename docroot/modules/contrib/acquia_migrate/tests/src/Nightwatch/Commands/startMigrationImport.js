/**
 * Starts the import process of a migration.
 *
 * A "migration" in this sense corresponds to a row of the migration dashboard.
 *
 * @param {string} selector
 *   The (CSS) selector for the migration's (table) row.
 * @param {function} callback
 *   An optional callback which will be called when this command completes.
 *
 * @return {object}
 *   The NightwatchAPI instance.
 */
exports.command = function startMigrationImport(selector, callback) {
  if (typeof this.globals !== 'object') {
    return this;
  }

  const _self = this;

  this
    .assert.elementPresent(`${selector}`)
    .setValue('#migration__operations_selector', 'import')
    .assert.elementPresent(`${selector} [type="checkbox"]`)
    .click(`${selector} [type="checkbox"]`)
    .click('.migrate__exposed-form button');

  if (typeof callback === 'function') {
    callback.call(_self);
  }

  return this;
};
