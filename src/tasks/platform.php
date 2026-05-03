<?php

namespace Deployer;

// -----------------------------------------------------------------------------
// @file platform.php
// @desc Loads all platform-related deployment tasks for klick-deploy package.
// -----------------------------------------------------------------------------

// Load platform-specific deployment tasks.
require_once __DIR__.'/platform/platform_savepub.php';    // Handles saving public keys.
require_once __DIR__.'/platform/platform_encrypt.php';    // Handles encryption of environment variables.
require_once __DIR__.'/platform/platform_decrypt.php';    // Handles decryption of environment variables.
require_once __DIR__.'/platform/platform_listhosts.php';  // Handles listing of hosts.
require_once __DIR__.'/platform/platform_knownhosts.php';  // Handles known hosts management.
require_once __DIR__.'/platform/platform_crontab.php';    // Handles crontab management.
