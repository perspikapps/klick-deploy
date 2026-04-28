<?php

namespace Deployer;

use Symfony\Component\Console\Input\InputOption;

// Define an option to specify the application version during deployment
option('app-version', null, InputOption::VALUE_REQUIRED, 'App version being deployed');

/**
 * Task to set the application version on the remote server.
 *
 * This task determines the application version from various sources and writes it to a VERSION file.
 */
desc('Set application version');
task('deploy:set_version', function () {

    $build = '+dep.{{release_name}}'; // Append deployment-specific build information

    // Determine the application version
    if (input()->hasOption('app-version') && ! empty(input()->getOption('app-version'))) {
        $version = input()->getOption('app-version'); // Use the version provided as an option
        info('Version given <fg=bright-magenta>'.$version.$build.'</>');
    } elseif (getenv('APP_VERSION') !== false) {
        $version = getenv('APP_VERSION'); // Use the version from the environment variable
        info('Version from env <fg=bright-magenta>'.$version.$build.'</>');
    } else {
        $version = runLocally('gitversion /showvariable FullSemVer'); // Calculate the version using GitVersion
        info('Version calculated <fg=bright-magenta>'.$version.$build.'</>');
    }

    // Write the version to the VERSION file on the remote server
    $fullVersion = $version.$build;
    run('printf %s '.escapeshellarg($fullVersion).' > {{release_or_current_path}}/VERSION');
});

// Run this task after setting the environment variables
after('deploy:env', 'deploy:set_version');
