<?php

namespace Deployer;

require_once 'contrib/crontab.php';

/**
 * Setup crontab jobs for Laravel application
 *
 * This task configures essential cron jobs:
 * - Queue restart every hour to prevent memory leaks
 * - Schedule runner every minute for Laravel's task scheduler
 */
set('crontab:use_sudo', false);
set('crontab:identifier', function () {
    return get('alias');
});
set(
    'bin/flock',
    function () {
        $lockid = substr(md5(get('alias')), 0, 8);

        return "/usr/bin/flock -w 30 {{deploy_path}}/shared/.{$lockid}.lock";
    }
);

set(
    'bin/php-artisan',
    function () {
        return 'cd {{deploy_path}}/current && {{bin/flock}} {{bin/php}} artisan';
    }
);

desc('Setup crontab jobs for Laravel application');
task('platform:crontab', function () {
    writeLn('Setting up crontab jobs...');
    invoke('crontab:sync');
})->limit(1);

after('deploy:symlink', 'platform:crontab');
