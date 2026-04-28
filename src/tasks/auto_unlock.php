<?php

namespace Deployer;

/**
 * Force-unlock the deployment lock after a failed unstable deployment.
 * Without this, any subsequent deploy attempt is blocked by a stale lock file
 * left behind by the failed migrate:fresh / seed sequence.
 */
after('deploy:failed', function () {
    if (isUnstable()) {
        warning('Unstable environment — forcing deploy:unlock to allow re-deployment...');
        invoke('deploy:unlock');
    }
});
