<?php

namespace Drupal\Tests\acquia_migrate\Traits;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Session;
use Drupal\Component\Assertion\Inspector;
use Drupal\Core\Url;

/**
 * Common tasks for the JavaScript UI for WebDriver based tests.
 *
 * We prefer FunctionalJavascript test to NightWatchJS tests.
 *  - With FunctionalJavascript tests we can easily reuse those kind of
 *    assertion helper traits that for example the Paragraphs module provides.
 *    With
 *    \Drupal\Tests\paragraphs\Traits\ParagraphsNodeMigrationAssertionsTrait, we
 *    are able to repeat exactly the same assertions that are used in Paragraphs
 *    Kernel and Functional migration tests.
 *  - Right now it seems to be impossible to use dataproviders in Nightwatch
 *    tests.
 */
trait MigrateJsUiTrait {

  use InitialImportAssertionTrait;

  /**
   * {@inheritdoc}
   */
  public function initFrontPage(): void {
    parent::initFrontPage();

    $this->getSession()->resizeWindow(1200, 700);
  }

  /**
   * Returns the CSS selector of a swooshing swooshy.
   *
   * @return string
   *   The CSS selector of a swooshing swooshy.
   */
  protected function swooshingCssSelector() {
    return '.swooshy.swooshy--is-swooshing';
  }

  /**
   * Returns the CSS selector for an active queue.
   *
   * @return string
   *   The CSS selector for an active queue.
   */
  protected function activeQueueCssSelector(): string {
    return '.migration_info__queue_controls button:first-child:enabled';
  }

  /**
   * Returns the CSS selector of the migrate action execute button.
   *
   * @return string
   *   The CSS selector of the action button.
   */
  protected function migrateActionButtonCssSelector() {
    return '.migrate__exposed-form button';
  }

  /**
   * Submits the content selection screen with its initial state.
   */
  protected function submitMigrationContentSelectionScreen() {
    $this->drupalGet(Url::fromRoute('acquia_migrate.get_started'));
    $this->assertSession()->responseContains('Welcome to Acquia Migrate Accelerate');
    $this->assertNotNull($choose_link = $this->assertSession()->waitForLink('Choose which data to import from your source site.'));
    $choose_link->click();
    $this->assertSession()->responseContains('Select data to migrate');
    $this->assertNotNull($start_migration_button = $this->assertSession()->waitForElement('xpath', '//form[contains(concat(" ", normalize-space(@class), " "), " preselect__list ")]//button', 60 * 1000));
    $start_migration_button->click();
    $this->assertSession()->waitForLink('View Migrations Dashboard');
  }

  /**
   * Visits the migration dashboard and waits for the initial import if needed.
   */
  protected function visitMigrationDashboard() {
    $current_url = $this->getUrl();
    $dashboard_url = Url::fromRoute('acquia_migrate.migrations.dashboard', [], ['absolute' => TRUE])->toString();
    if ($current_url !== $dashboard_url) {
      $this->drupalGet(Url::fromRoute('acquia_migrate.migrations.dashboard'));
    }

    $this->assertSession()->waitForElement('css', '.initial_import .progress', 2000);

    // The CI performance is highly variable; the initial import tends to take
    // ~240 seconds on more complex source database fixtures (f.e. the
    // \Drupal\Tests\acquia_migrate\FunctionalJavascript\ParagraphsMigrationTest
    // fixture).
    $this->assertSession()->assertNoElementAfterWait('css', '.initial_import .progress', 6 * 60 * 1000);
    $this->assertSession()->assertNoElementAfterWait('css', $this->swooshingCssSelector(), 120 * 1000);
  }

  /**
   * Runs a single migration.
   *
   * @param string $cluster_name
   *   The "human" readable name of the migration cluster.
   */
  protected function runSingleMigration(string $cluster_name) {
    $this->runMigrations((array) $cluster_name);
  }

  /**
   * Runs the specified migrations.
   *
   * @param string[] $cluster_names
   *   The "human" readable name of the migration clusters.
   */
  protected function runMigrations(array $cluster_names) {
    $this->visitMigrationDashboard();
    $this->activateTab('In Progress');
    $this->selectOperation('Import');

    foreach ($cluster_names as $cluster_name) {
      $checkbox_name_end = $this->encodeUri("-$cluster_name");
      $this->assertNotNull($cluster_checkbox = $this->assertSession()->waitForElement('css', "input[type='checkbox'][name$='$checkbox_name_end']"), sprintf('The "%s" cluster\'s checkbox which name ends with "%s" cannot be found.', $cluster_name, $checkbox_name_end));
      $this->assertFalse(
        $cluster_checkbox->hasAttribute('disabled'),
        sprintf(
          "The '%s' cluster's checkbox which name ends with '%s' is disabled. This means that the 'Import' operation isn't available for this migration.",
          $cluster_name,
          $checkbox_name_end
        )
      );
      $this->checkFieldWithJs($cluster_checkbox);
    }

    $this->assertNotNull($run_button = $this->assertSession()->waitForElement('css', $this->migrateActionButtonCssSelector()));
    $this->scrollToElement($run_button);
    $run_button->click();
    $this->waitForMigrationProcessFinished(120, array_merge($cluster_names, ['test assertions']));
  }

  /**
   * Activates a tab on the dashboard.
   *
   * @param string $tab_label
   *   The label of the tab.
   */
  protected function activateTab(string $tab_label): void {
    $tab = $this->assertSession()
      ->waitForElement(
        'xpath',
        "//ul[@data-drupal-nav-tabs-target]//li//a[contains(., '$tab_label')]"
      );
    $this->assertNotNull(
      $tab,
      sprintf('The "%s" tab cannot be found.', $tab_label)
    );
    if ($tab->hasClass('is-active')) {
      return;
    }

    $tab->click();

    $this->assertNotNull(
      $this->assertSession()->waitForElement(
        'xpath',
        "//ul[@data-drupal-nav-tabs-target]//li//a[contains(., '$tab_label')][contains(concat(' ', normalize-space(@class), ' '), ' is-active ')]"
      )
    );
  }

  /**
   * Select the specified migration operation on the dashboard.
   *
   * @param string $operation
   *   The human-readable text of the option.
   */
  protected function selectOperation(string $operation): void {
    $operation_selector = $this->assertSession()->waitForField('migration__operations_selector');
    $this->assertNotNull($operation_selector);
    $option_to_select = $operation_selector->find('xpath', "//option[contains(., '$operation')]");
    $this->assertNotNull(
      $option_to_select,
      sprintf(
        "The migration operation '%s' is not available, thus cannot be selected.",
        $operation
      )
    );
    $raw_value = $option_to_select->getValue();

    $operation_selector->setValue($raw_value);

    $this->assertEquals(
      $raw_value,
      $operation_selector->getValue(),
      sprintf(
        "The migration operation '%s' with value '%s' wasn't selected, but it is present.",
        $operation,
        $raw_value
      )
    );
  }

  /**
   * Encodes the given string with JavaScript's encodeURI() function.
   *
   * The front end application uses "encodeURI(id)" for creating the checkbox
   * names, where "id" is the table row's "id" attribute value. In PHP,
   * "rawurlencode()" produces the closest output, but we still should have
   * to modify the result for being compatible with "encodeUri()". But it is lot
   * easier to use Javascript to get the same result.
   *
   * @param string $string_to_encode
   *   The string to encode.
   *
   * @return string
   *   The encoded string.
   *
   * @see selector.jsx
   */
  protected function encodeUri(string $string_to_encode): string {
    $session = $this->getSession();
    assert($session instanceof Session);
    $encode_script = <<<JS
encodeURI("$string_to_encode")
JS;
    return (string) $session->evaluateScript($encode_script);
  }

  /**
   * Runs all unblocked migrations.
   */
  protected function runAllUnblockedMigrations() {
    $this->assertNotNull($select_all_checkbox = $this->assertSession()->waitForField('select-allMigrations'));
    $this->scrollToElement($select_all_checkbox);
    $this->checkField($select_all_checkbox);
    $this->assertNotNull($run_button = $this->assertSession()->waitForElement('css', $this->migrateActionButtonCssSelector()));
    $checked_checkbox_labels = $this->getMigrationRowLabels(TRUE);
    $this->scrollToElement($run_button);
    $run_button->click();
    $this->waitForMigrationProcessFinished(120, $checked_checkbox_labels);
  }

  /**
   * Runs all unblocked migrations, repeatedly, until there's none left to run.
   *
   * @param int $max_try
   *   The maximal number of the iterations.
   */
  protected function runAllMigrations(int $max_try = 10) {
    $this->visitMigrationDashboard();

    for ($i = 0; $i < $max_try; $i++) {
      $unprocessed_rows = $this->countMigrationsCheckboxes();
      if ($unprocessed_rows === 0) {
        break 1;
      }
      $this->runAllUnblockedMigrations();
    }
  }

  /**
   * Waits for all the current migrations to be finished.
   *
   * @param int $timeout_in_seconds
   *   Maximum time to wait, in microseconds.
   * @param string[]|null $expected_disappeared_rows
   *   The labels of the migration rows we expect to have disappeared when the
   *   migration process is finished. Optional, defaults to NULL.
   */
  protected function waitForMigrationProcessFinished(int $timeout_in_seconds, ?array $expected_disappeared_rows = NULL): void {
    // Wait for the queue to activate (should be instantaneous, because 100%
    // client side).
    $this->assertSession()->waitForElement('css', $this->activeQueueCssSelector(), 1);

    // Then wait for the queue to finish (within the allotted time).
    $this->assertSession()->waitForElementRemoved('css', $this->activeQueueCssSelector(), $timeout_in_seconds * 1000);

    // Optionally, assert the remaining migrations.
    if ($expected_disappeared_rows) {
      assert(Inspector::assertAllStrings($expected_disappeared_rows));
      // Ensure that the swooshy wooshy stopped swooshing before checking the
      // expected remaining migrations: that is how we can ensure that the queue
      // on the client side has not just finished, but the UI has actually been
      // refreshed with server-side data to correctly indicate the remaining
      // migrations. This should happened within 10 seconds.
      $this->assertSession()->waitForElementRemoved('css', $this->swooshingCssSelector(), 10 * 1000);
      // Allow for the rows to animate away to a different tab.
      sleep(3);
      $this->assertEmpty(array_intersect($this->getMigrationRowLabels(), $expected_disappeared_rows));
    }
  }

  /**
   * Scroll to an element, trying to position it to the center of the window.
   *
   * This is very useful if you have the Toolbar module enabled.
   *
   * @param \Behat\Mink\Element\NodeElement $target
   *   The element to scroll to.
   */
  protected function scrollToElement(NodeElement $target) {
    $target_xpath = preg_replace('/\\n\w?/', '', $target->getXpath());
    $scroll_script = <<<JS
      document.evaluate("$target_xpath", document, null, XPathResult.ANY_TYPE, null).iterateNext().scrollIntoView({block: 'center'});
JS;
    $this->getSession()->executeScript($scroll_script);
  }

  /**
   * Returns the number of the selected migrations.
   */
  protected function countMigrationsCheckboxes(bool $checked_only = FALSE) {
    $count_script = <<<JS
(function() {
  var count = 0;
  var items = document.evaluate("//html//*[contains(concat(' ', normalize-space(@class), ' '), ' tabbed_page__view ')]//table//tbody//input[@type='checkbox' and not(@disabled)]", document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
  if ("$checked_only") {
    for (let i=0, lenght = items.snapshotLength; i< lenght; i++) {
      if (items.snapshotItem(i).checked) {
        count += 1;
      }
    }
    return count;
  }
  return items.snapshotLength;
})()
JS;
    return (int) $this->getSession()->evaluateScript($count_script);
  }

  /**
   * Returns the migration row labels on the page.
   */
  protected function getMigrationRowLabels(bool $checked_only = FALSE): array {
    $migration_labels_script = <<<JS
(function() {
  var labels = [];
  var items = document.evaluate("//html//*[contains(concat(' ', normalize-space(@class), ' '), ' tabbed_page__view ')]//table//tbody//input[@type='checkbox']", document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
  for (let i=0, lenght = items.snapshotLength; i< lenght; i++) {
    if ("$checked_only" && !items.snapshotItem(i).checked) {
      continue;
    }
    labels.push(decodeURIComponent(items.snapshotItem(i).name.split('-').pop()));
  }
  return labels;
})()
JS;
    return $this->getSession()->evaluateScript($migration_labels_script);
  }

  /**
   * Returns the number of the visible migration rows.
   *
   * @return int
   *   The number of the visible migration rows.
   */
  protected function countMigrationRows() {
    return count($this->getSession()->getPage()->findAll('css', '.tabbed_page__view table tbody tr'));
  }

  /**
   * Checkes a checkbox field even if it overlaps with its label.
   *
   * @param \Behat\Mink\Element\NodeElement|string $locator_or_element
   *   The locator or the NodeElement of the checkbox.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function checkField($locator_or_element) {
    if (is_string($locator_or_element)) {
      $locator_or_element = $this->getSession()->getPage()->findField($locator_or_element);
    }

    if (!($locator_or_element instanceof NodeElement)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value', $locator_or_element->getXpath());
    }

    try {
      $locator_or_element->check();
    }
    catch (\Exception $e) {
      if (strpos($e->getMessage(), 'is not clickable at point') === FALSE) {
        throwException($e);
      }
      $label_for = $locator_or_element->getAttribute('id') ?? $locator_or_element->getAttribute('name');

      if (empty($label_for)) {
        throwException($e);
      }

      $this->assertNotNull($label = $this->getSession()->getPage()
        ->find('css', 'label[for="' . $label_for . '"]'));

      $label->click();
    }
  }

  /**
   * Checks a checkbox field with JavaScript.
   *
   * @param \Behat\Mink\Element\NodeElement|string $locator_or_element
   *   The locator or the NodeElement of the checkbox.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function checkFieldWithJs($locator_or_element) {
    $node_element = is_string($locator_or_element)
      ? $this->getSession()->getPage()->findField($locator_or_element)
      : $locator_or_element;

    if (!($node_element instanceof NodeElement)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value', $node_element->getXpath());
    }

    $checkbox_id = $node_element->getAttribute('id');

    if ($checkbox_id !== NULL) {
      $checkbox_checker_script = <<<JS
(function() {
  var checkbox = document.getElementById("$checkbox_id");
  if (checkbox.checked) {
    checkbox.checked = false;
  }
  checkbox.click();
})();
JS;
      $this->getSession()->evaluateScript($checkbox_checker_script);
    }
  }

  /**
   * Asserts that no warning or error messages are printed onto the page.
   */
  protected function assertNoWarningOrErrorMessages() {
    $warning_messages_wrapper = $this->xpath('//div[contains(concat(" ", normalize-space(@class), " "), " messages--error ")]');
    $error_messages_wrapper = $this->xpath('//div[contains(concat(" ", normalize-space(@class), " "), " messages--warning ")]');
    $elements = array_merge($warning_messages_wrapper, $error_messages_wrapper);
    $markup = '';
    foreach ($elements as $element) {
      $markup .= $element->getOuterHtml();
    }
    return $this->assertSame('', $markup);
  }

}
