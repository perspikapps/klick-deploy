<!-- @format -->

# Changelog

All notable changes to `klick-deploy` will be documented in this file.

## [Unreleased]

### Added

- **Automatic Crontab Management**: Added support for automatic crontab job configuration using Deployer's crontab contrib package
    - `php artisan queue:restart` scheduled every hour to prevent memory leaks
    - `php artisan schedule:run` scheduled every minute for Laravel's task scheduler
    - Crontab setup runs automatically just before deployment unlock
    - Jobs are properly configured to use the current deployment path
- New platform task `platform:crontab` for manual crontab configuration

### Changed

- Updated platform.php to include crontab task file
- Enhanced documentation with crontab features

## [1.0.0] - Initial Release

### Features

- Initial klick-deploy package with URL-based deployment configuration
- Support for Laravel deployment strategies
- cPanel integration tasks
- Platform management tasks
- Module activation/deactivation support
- Asset upload functionality
- Environment variable management
- Version setting capabilities
