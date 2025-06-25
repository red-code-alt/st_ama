exports.command = function waitForModal(title, action, callback) {
  if (typeof this.globals !== 'object') {
    return this;
  }

  const _self = this;

  const buttons = {
    ok: '#modal .ui-dialog-buttonset .button',
    cancel: '#modal .ui-dialog-titlebar-close',
  };

  this
    .waitForElementPresent('#modal .ui-dialog')
    .assert.containsText('#modal .ui-dialog-title', title)
    .click(buttons[action])
    .waitForElementNotPresent('#modal .ui-dialog');

  if (typeof callback === 'function') {
    callback.call(_self);
  }

  return this;
};
