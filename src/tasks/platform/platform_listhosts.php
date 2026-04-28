<?php

namespace Deployer;

/**
 * Task to set environment variables on the remote server.
 *
 * This task iterates over the labels of the current host and updates the environment variables accordingly.
 * It ensures that essential environment variables such as APP_KEY and APP_URL are set.
 */
desc('List selected hosts');
task('platform:listhosts', function () {

    $host = array_map(function ($host) {
        return $host->get('alias');
    }, selectedHosts());

    echo json_encode($host, JSON_PRETTY_PRINT);
})->once();
