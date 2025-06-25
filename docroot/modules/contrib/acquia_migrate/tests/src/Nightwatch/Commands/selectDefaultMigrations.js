/**
 * Visit the preselect page and continue with all defaults selected.
 */
exports.command = function selectDefaultMigrations(preselectUrl = null) {
  if (typeof this.globals !== 'object') {
    return this;
  }
  preselectUrl = preselectUrl || this.globals.acquiaMigrateUrls.preselect;
  this
    .drupalRelativeURL(preselectUrl)
    .waitForLoading()
    .waitForElementPresent('.preselect__list', 1000)
    .click('.preselect__list button')
    .expect.element('body')
      .text.to.contain('Preselections made successfully')
      .before(5000);

  return this;
};
