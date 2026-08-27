# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.1] - 2026-08-27

### Fixed

- Syncing an empty `phone_numbers` array now clears the collection instead of silently doing nothing. Two independent guards treated `[]` as "no instruction given": `attachPhoneNumber()` returned early on `empty()` after the `has()` check had already covered the absent key, and `PhoneNumberService::sync()` skipped `deleteWhereNotIn()` whenever nothing was kept. Removing one of several phone numbers worked; removing the last one reported success and left the row in place. The payload states are now distinct — an absent key leaves the collection alone, a populated array makes the collection exactly that, and `[]` removes them all. An explicit `null` is still ignored rather than reaching `sync()`.

  **Behaviour change:** anything that has been sending `phone_numbers: []` and relying on the previous no-op will now see the phone number rows deleted. Omit the key instead for a partial update.

### Tests

- Added coverage for all three payload states through the controller hook and for `sync($parent, [])` called directly on the service, so each guard is exercised independently. Also asserts that clearing one parent's collection leaves other parents' rows intact.

## [1.2.0] - 2026-08-20

### Added

- Laravel 13 support: the `illuminate/*` constraints now allow `^13.0` and `orchestra/testbench` accepts `^11.0`. Laravel 12 remains fully supported — the suite passes against both.

### Changed

- CI now runs the test suite against Laravel 12 and Laravel 13.
