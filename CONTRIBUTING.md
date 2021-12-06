# Contributing

* Suggestions for enhancements should go in our separate [suggestion tracker](https://github.com/iftechfoundation/ifdb-suggestion-tracker/issues).
* When creating pull requests, target the `main` branch. Please rebase your PRs and keep each commit small and focused. It's OK to have a big PR, as long as each commit in the PR is small, but smaller PRs are better, if feasible.
* It's OK to file a PR that only partially works, just to begin discussion about the code.
* We have a few Selenium tests in the `tests` directory. Please don't break the tests!
* This is PHP, so we need to be particularly careful about [XSS](https://owasp.org/www-community/attacks/xss/) bugs. (A PR to adopt a [really strict Content Security Policy](https://github.com/iftechfoundation/ifdb-suggestion-tracker/issues/99) would be extremely welcome!)
* [SQL injections](https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html) are also a major risk.
