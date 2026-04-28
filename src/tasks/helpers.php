<?php

namespace Deployer;

/**
 * Returns true when the current host is labelled as an unstable environment.
 */
function isUnstable(): bool
{
    return (currentHost()->getLabels()['env'] ?? 'production') === 'unstable';
}
