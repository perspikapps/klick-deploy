<?php

namespace Deployer;

// Include task files for deployment
require_once __DIR__.'/tasks/helpers.php'; // Shared helper functions (isUnstable, etc.)
require_once __DIR__.'/tasks/modules.php'; // Tasks related to module management
require_once __DIR__.'/tasks/platform.php'; // Tasks related to platform configuration
require_once __DIR__.'/tasks/cpanel.php'; // Tasks related to cPanel configuration
require_once __DIR__.'/tasks/set_env.php'; // Tasks for setting environment variables
require_once __DIR__.'/tasks/set_version.php'; // Tasks for setting application version
require_once __DIR__.'/tasks/upload_assets.php'; // Tasks for uploading assets
require_once __DIR__.'/tasks/artisan.php'; // Override artisan:migrate for alpha env reset
require_once __DIR__.'/tasks/auto_unlock.php'; // Force deploy:unlock after failed alpha deployments

// Set the number of releases to keep on the server
if (function_exists('isUnstable') && isUnstable()) {
    set('keep_releases', 0);
} else {
    set('keep_releases', 2);
}

// Define the source path for the deployment
set('source_path', './');

// Enable SSH agent forwarding for secure connections
set('forwardAgent', 'true');

// Run database seeding after every migration
after('artisan:migrate', 'artisan:db:seed');

// On unstable deployments, install dev dependencies so factories and seeders work
before('deploy:vendors', function () {
    if (isUnstable()) {
        set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --optimize-autoloader --ignore-platform-reqs');
    }
});
