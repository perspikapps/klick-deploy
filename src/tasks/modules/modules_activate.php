<?php

namespace Deployer;

/**
 * Configure cPanel deployment.
 *
 * This task sets up the necessary configurations for deploying to a cPanel server.
 * It adjusts writable mode, configures SSH settings, and logs the setup process.
 */
desc('Configure writable for cpanel');
task('modules:activate', function () {

    $modules = get('modules');

    // if module is space-separated string, convert entries with ! to array
    if (is_string($modules)) {
        $modules = preg_split('/\s+/', trim($modules));
    }

    $enable = array_filter($modules, function ($module) {
        return ! str_starts_with($module, '!');
    });

    $disable = array_filter($modules, function ($module) {
        return \str_starts_with($module, '!');
    });

    if (empty($modules) || empty($enable)) {
        info('No modules to enable, enabling all modules');
        artisan('module:enable --all')();

        foreach ($disable as $module) {
            info('Disabling module: <fg=yellow>'.$module.'</>');
            artisan('module:disable '.substr($module, 1))();
        }
    } else {
        info('<fg=yellow>Disabling all modules</>');
        artisan('module:disable --all')();

        foreach ($enable as $module) {
            info('Enabling module: <fg=green>'.$module.'</>');
            artisan('module:enable '.$module)();
        }
    }

    artisan('module:list', ['showOutput'])();
});

// Ensure the task runs after the setup step
before('artisan:config:cache', 'modules:activate');
