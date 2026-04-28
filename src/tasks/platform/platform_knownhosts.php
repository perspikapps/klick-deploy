<?php

namespace Deployer;

/**
 * Task to ensure the remote hostname is present in the local SSH known_hosts file.
 *
 * This task checks whether the remote host is already in known_hosts, and if not,
 * uses ssh-keyscan to add it, enabling non-interactive SSH connections.
 */
desc('Ensure known hosts are set');
task('platform:knownhosts', function () {
    info('Setting known hosts for <fg=magenta>{{hostname}}</>...');
    runLocally('ssh-keygen -F {{hostname}} || ssh-keyscan -H {{hostname}} >> ~/.ssh/known_hosts');
})->oncePerNode();
