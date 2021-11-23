## Information

Releases use the following numbering system:
**{major}.{minor}.{incremental}**

* major: Major refactoring or rewrite - make sure you read and test very carefully!
* minor: Breaking change in some circumstances, or a new feature. Read carefully and make sure you understand the impact of the change.
* incremental: A "safe" change / improvement. Should *always* be safe to upgrade.

* **[BC]**: Items marked with [BC] indicate a breaking change that will require updates to your code if you are using that code in your extension.

## Release 1.12

* PHP7.4 compatibility.

## Release 1.11

* Fix populating town/city for multiple providers

## Release 1.10

* Add postcode lookup to "On Behalf of Organisation"
* Add caching to getAddressIO. Refactor tpl and javascript to improve maintainability.

## Release 1.9
* Cleanup the postcode selector that was being added multiple times in some cases.
* Only show a single loading "spinner" when searching for postcodes.

## Release 1.8
* Update jQuery and trigger change on all billing fields that we touch to trigger other functions (eg. billing address is the same). This fixes issues when "My Billing address is the same" is checked.
* Switch to \Civi::settings to get/set settings (removes a deprecated function warning).

## Release 1.7
* Fixed parsing of address.
