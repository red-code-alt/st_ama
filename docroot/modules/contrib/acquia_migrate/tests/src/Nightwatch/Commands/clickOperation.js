exports.command = function clickOperation(row, operation, action, callback) {
  if (typeof this.globals !== 'object') {
    return this;
  }

  const _self = this;

  row
    .assert.elementPresent('@dropdownToggle')
    .click('@dropdownToggle')
    .waitForElementVisible('@dropdownMenu')
    .click('@opComplete');

  if (typeof callback === 'function') {
    callback.call(_self);
  }

  return this;
};
