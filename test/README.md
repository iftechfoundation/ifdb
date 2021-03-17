# IFDB Selenium IDE Testing

This is the [Selenium IDE](https://www.selenium.dev/selenium-ide/) automated browser test suite for the IFDB.

## Preparing Your Own Testing Environment

Selenium IDE is currently available as a Firefox Browser Add-On and as a Chrome Browser Extension. Select one, or both, depending on which browser you are interested in testing.

### Firefox Installation

1. Open Firefox and navigate to the download page for the [Firefox Browser Selenium IDE Add-On](https://addons.mozilla.org/en-GB/firefox/addon/selenium-ide/).
1. Follow the instructions for installing the add-on.

### Chrome Installation

1. Open Chrome and navigate to the Chrome Web Store for the [Chrome Browser Selenium IDE Extension](https://chrome.google.com/webstore/detail/selenium-ide/mooikfkahbdckldjjndioackbalphokd).
1. Follow the instructions for installing the extension.

### Opening and Running Tests

1. Start up your local development version of the IFDB.
1. Open the browser of your choice and click on the Selenium IDE icon in the upper right corner.
1. In the pop up dialog, select `Open an existing project`.
1. Open `./IFDB.side`.
1. Select `Test suites` from the drop down menu on the upper left corner of the application.
1. Select any of the available test suites and tests.
1. To run the currently selected test, click on the `Run current test` button.
1. To run all of the tests in the suite, click on the `Run all tests in suite button`.

For information about using the Selenium IDE, see: https://www.selenium.dev/selenium-ide/docs/en/introduction/getting-started.