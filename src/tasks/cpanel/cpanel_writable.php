<?php

namespace Deployer;

/**
 * Configure cPanel deployment.
 *
 * This task sets up the necessary configurations for deploying to a cPanel server.
 * It adjusts writable mode, configures SSH settings, and logs the setup process.
 */
desc('Configure writable for cpanel');
task('cpanel:writable', function () {

    // Set writable mode to 'chmod' for compatibility with cPanel
    set('writable_mode', 'chmod');
})->select('type=cpanel');

// Ensure the task runs after the setup step
before('deploy:writable', 'cpanel:writable');
