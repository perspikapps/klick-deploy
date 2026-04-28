<?php

namespace Deployer;

/**
 * Task to list the selected hosts for the current deployment.
 *
 * This task returns the aliases of all selected hosts as a JSON array,
 * which is useful for CI/CD pipelines that need to know which hosts will be targeted.
 */
desc('List selected hosts');
task('platform:listhosts', function () {

    $host = array_map(function ($host) {
        return $host->get('alias');
    }, selectedHosts());

    echo json_encode($host, JSON_PRETTY_PRINT);
})->once();
