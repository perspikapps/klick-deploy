<?php

namespace Deployer;

/**
 * Task to set environment variables on the remote server.
 *
 * This task iterates over the labels of the current host and updates the environment variables accordingly.
 * It ensures that essential environment variables such as APP_KEY and APP_URL are set.
 */
desc('Ensure known hosts are set');
task('platform:knownhosts', function () {
    info('Setting known hosts for <fg=magenta>{{hostname}}</>...');
    runLocally('ssh-keygen -F {{hostname}} || ssh-keyscan -H {{hostname}} >> ~/.ssh/known_hosts');
})->oncePerNode();
