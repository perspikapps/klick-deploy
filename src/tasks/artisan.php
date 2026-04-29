<?php

namespace Deployer;

// -----------------------------------------------------------------------------
// @file modules.php
// @desc Loads all modules-related deployment tasks for klick-deploy package.
// -----------------------------------------------------------------------------

// Load platform-specific deployment tasks.
require_once __DIR__.'/artisan/artisan_migrate.php';    // Handles database migrations.
