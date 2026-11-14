# Change Log

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning v2.0.0](https://semver.org/spec/v2.0.0.html).

## [0.2.1] - 2026-11-14

### Fixed
- Bug in the handling of validation errors for arrays of primitives.

## [0.2.0] - 2026-10-11

### Added
- Unbinding properties from request parameters.

### Fixed
- Bug in the handling of associative arrays containing objects.

## [0.1.0] - 2026-09-13

Initial release.

### Added
- Automatic creation and validation of request DTOs.
- Validation of raw request data before object hydration.
- Support for multiple request data sources and per-property binding.
- Support for nested objects, arrays, nullable fields, and default values.
- Automatic conversion of scalar values, backed enums, and date-time objects.
- Support for Symfony constraints and validation groups.
- Customizable value processing through casters and patchers.
- Flexible validation error handling through exceptions or controller arguments.

[0.1.0]: https://github.com/cryonighter/valid-request-bundle/tree/v0.1.0
[0.2.0]: https://github.com/cryonighter/valid-request-bundle/tree/v0.2.0
[0.2.1]: https://github.com/cryonighter/valid-request-bundle/tree/v0.2.1
