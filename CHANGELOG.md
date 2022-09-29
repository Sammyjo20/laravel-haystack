# Changelog

All notable changes to `laravel-haystack` will be documented in this file.

## Version v0.8.0 - 2022-09-29

### New Features

- New `allowFailures` option added when building Haystacks to continue processing the next job even if jobs fail. @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/42

### Other Changes

- Switched to a new HaystackOptions class instead of multiple database options for better compatibility with future options.
- Fixed an issue where timestamps weren't being set when haystack bales are added to the queue

### Breaking Changes

- Added a new text "options" column to store the new HaystackOptions class.
- Removed old `return_data` column on the `haystacks` table
- Changed `value` column on the `haystack_data` table to a longText to support more data.

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.7.6...v0.8.0

## Version v0.7.6 - 2022-09-18

### What's Changed

- Feature | Chunkable Jobs by @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/40

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.7.5...v0.7.6

## Version v0.7.5 - 2022-09-11

### What's Changed

- Feature | Initial Data by @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/38

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.7.4...v0.7.5

## Version v0.7.4 - 2022-08-19

### What's Changed

- Use conditional clauses when building haystack by @faisuc in https://github.com/Sammyjo20/laravel-haystack/pull/32

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.7.3...v0.7.4

## Version v0.7.3 - 2022-08-18

### What's Changed

- Feature | Added declare strict types by @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/31

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.7.2...v0.7.3

## Version v0.7.2 - 2022-08-18

### What's Changed

- Add conditional objects when adding job to haystack by @faisuc in https://github.com/Sammyjo20/laravel-haystack/pull/30

### New Contributors

- @faisuc made their first contribution in https://github.com/Sammyjo20/laravel-haystack/pull/30

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.7.1...v0.7.2

## Version v0.7.1 - 2022-08-10

### What's Changed

- Add `haystacks:clear` and `haystacks:forget` commands by @viicslen in https://github.com/Sammyjo20/laravel-haystack/pull/29

### New Contributors

- @viicslen made their first contribution in https://github.com/Sammyjo20/laravel-haystack/pull/29

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.7.0...v0.7.1

## Version v0.7.0 - 2022-08-09

### What's Changed

- Names and appending multiple jobs by @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/26

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.6.0...v0.7.0

## Version v0.6.0 - 2022-08-08

### What's Changed

- Changed "appendToHaystackNext" to "prependToHaystack" by @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/25

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.5.0...v0.6.0

## Version v0.5.0 - 2022-08-08

### What's Changed

- Append to the next job in the haystack by @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/24

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.4.3...v0.5.0

## Version v0.4.3 - 2022-08-07

### What's Changed

- Cancelling haystacks by @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/20

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.4.2...v0.4.3

## Version v0.4.2 - 2022-08-06

### What's Changed

- Added closure check before running queries by @Sammyjo20 in https://github.com/Sammyjo20/laravel-haystack/pull/19

**Full Changelog**: https://github.com/Sammyjo20/laravel-haystack/compare/v0.4.1...v0.4.2
