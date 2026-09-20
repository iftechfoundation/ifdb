# IFDB Selenium IDE Testing

This is the [Selenium IDE](https://www.selenium.dev/selenium-ide/) automated browser test suite for the IFDB.

## Preparing Your Own Testing Environment

The Selenium IDE addon is currently available for Firefox Browser only, though there are ways to run tests on Chrome Browser. Select one browser, or both browsers, depending on which browser you are interested in testing.

### Firefox Installation

1. Open Firefox and install the [Firefox Browser Selenium IDE Add-On](https://addons.mozilla.org/en-GB/firefox/addon/selenium-ide/).
2. You will have to install an extension that disables CSP, such as [Disable CSP and CORS](https://addons.mozilla.org/en-US/firefox/addon/disable-csp-and-cors/), for the tests to work. Be warned that having this extension enabled comes with security risks, but without it, you will get a "call to eval() blocked by CSP" error. You may want to create a [new Firefox profile](https://support.mozilla.org/en-US/kb/profile-manager-create-remove-switch-firefox-profiles) for Selenium testing.

#### Opening and Running Tests in Firefox

1. Start up your local development version of the IFDB.
2. Open the browser of your choice and click on the Selenium IDE icon in the upper right corner.
3. In the pop up dialog, select `Open an existing project`.
4. Open `./IFDB.side`.
5. Select `Test suites` from the drop down menu on the upper left corner of the application.
6. Select any of the available test suites and tests.
7. To run the currently selected test, click on the `Run current test` button.
8. To run all of the tests in the suite, click on the `Run all tests in suite button`.

For information about using the Selenium IDE, see: https://www.selenium.dev/selenium-ide/docs/en/introduction/getting-started.

### Chrome Installation

As of December 2025, the [Chrome Browser Selenium IDE Extension](https://chrome.google.com/webstore/detail/selenium-ide/mooikfkahbdckldjjndioackbalphokd) has been disabled by Google and can no longer be installed.

You can download an alternative extension such as [Ui.Vision](https://chromewebstore.google.com/detail/uivision/gcbalfbdmfieckjlnblleoemohcganoc).

For Ui.Vision:

1. Open the extension and click the settings gear icon in the upper right.
2. Click the `Selenium` tab, then click the `Import .SIDE projects` button.
3. Open `./IFDB.side`.
4. Find the IFDB folder on the left.
5. To run an individual test, select it in the left menu and click on the `Play macro` button in the upper right.
6. To run all of the tests in the suite, right click the IFDB folder on the left and click `Testsuite: Play all in folder`.
