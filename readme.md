<!-- @format -->

# Easy Deployer

[![Packagist](https://img.shields.io/packagist/v/perspikapps/klick-deploy.svg)](https://packagist.org/packages/perspikapps/klick-deploy)
[![Packagist](https://poser.pugx.org/perspikapps/klick-deploy/d/total.svg)](https://packagist.org/packages/perspikapps/klick-deploy)
[![Packagist](https://img.shields.io/packagist/l/perspikapps/klick-deploy.svg)](https://packagist.org/packages/perspikapps/klick-deploy)

[![Commitizen friendly](https://img.shields.io/badge/commitizen-friendly-brightgreen.svg)](http://commitizen.github.io/cz-cli/) [![semantic-release](https://img.shields.io/badge/%20%20%F0%9F%93%A6%F0%9F%9A%80-semantic--release-e10079.svg)](https://github.com/semantic-release/semantic-release)

[![Buy me a coffee](https://badgen.net/badge/buymeacoffe/tomgrv/yellow?icon=buymeacoffee)](https://buymeacoffee.com/tomgrv)

This package handles deployement configuration via a `deploy.yaml` file to define deploy strategy and a `.hostmap` file to define target strategy

## Installation

Install via composer

```bash
composer require perspikapps/klick-deploy
```

## Usage

Deployment is handled by [deployphp/deployer](https://github.com/deployphp/deployer) package.

### deploy.yaml

_See [deployer](https://github.com/deployphp/deployer) config for details_

```yaml
import:
    - vendor/perspikapps/klick-deploy/src/main.php

config:
    source_path: './'
    shared_dirs:
        - storage
    shared_files:
        - .env
    writable_dirs:
        - bootstrap/cache
        - storage
        - storage/app
        - storage/app/public
        - storage/framework
        - storage/framework/cache
        - storage/framework/sessions
        - storage/framework/views
        - storage/logs
    log_files:
        - storage/logs/*.log

hosts:
    example.com:
        hostname: ssh.example.com
        labels:
            env: production
        secrets:
            APP_KEY: ENCRYPTED_APP_KEY
            DB_PASSWORD: ENCRYPTED_DB_PASSWORD
            MAIL_PASSWORD:
```

Host entries may define a `secrets` map. Each key is the environment variable name that should be written to the remote `.env` file.

Supported value formats are:

- `APP_KEY: %APP_KEY%` uses the current process environment variable `APP_KEY` without decryption.
- `APP_KEY: ENCRYPTED_APP_KEY` decrypts the inline value remotely with the `decrypt` helper.
- `APP_KEY: { secret: ENCRYPTED_APP_KEY, env: APP_KEY }` uses explicit encrypted value with optional env fallback when `secret` is empty.

This keeps secret values out of the repository while still letting each host declare exactly which secrets it needs.

### .hostname

Specify ONE deployement target per line as url:

- url scheme = strategies to activate (`+` separated, each must be loaded in `import` section of `deploy.yaml` file)
- url user/host/port = server to deploy to
- url path = path on server to deploy to
- url query = deploy options to use
- url anchor = variables to set in .env file after deployment

```ini
upload+laravel://user@dev.exemple.com/var/home/{{hostname}}?bin/php=/opt/plesk/php/7.4/bin/php&writable_mode=chmod#debug=true&env=staging
upload+laravel://user@beta.exemple.com/var/home/{{hostname}}?bin/php=/opt/plesk/php/7.4/bin/php&writable_mode=chmod#debug=true&env=beta
upload+laravel://user@www.exemple.com/var/home/{{hostname}}?bin/php=/opt/plesk/php/7.4/bin/php&writable_mode=chmod#debug=false&env=production
```

## Features

### Automatic Crontab Setup

The package automatically configures essential Laravel cron jobs during deployment:

- **Queue Restart**: `php artisan queue:restart` runs every hour to prevent memory leaks
- **Schedule Runner**: `php artisan schedule:run` runs every minute to execute Laravel's task scheduler

These cron jobs are automatically set up just before the deployment unlock phase and use the deployment path to ensure they point to the current release.

## Security

If you discover any security related issues, please email
instead of using the issue tracker.

## Credits

- [tomgrv](https://github.com/tomgrv)
- [deployphp](https://github.com/deployphp)
