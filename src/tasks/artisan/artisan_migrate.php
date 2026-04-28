<?php

namespace Deployer;

/**
 * Override the built-in artisan:migrate task to perform a full database reset
 * on unstable environments. For all other environments the standard incremental
 * migrate --force is executed, preserving live data.
 */
task('artisan:migrate', function () {
    if (isUnstable()) {
        warning('Unstable environment — performing full database reset (migrate:fresh)...');
        artisan('migrate:fresh --force')();
    } else {
        artisan('migrate --force')();
    }
})->desc('Execute artisan migrate (migrate:fresh on unstable, migrate on all other environments)');
