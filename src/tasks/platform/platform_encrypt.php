<?php

namespace Deployer;

use RuntimeException;

// Load cryptographic helper functions for encryption/decryption tasks.
require_once __DIR__.'/../crypto.php';

/**
 * Task to encrypt environment variables for the current host.
 *
 * @desc Encrypts a user-provided value using the host's public key and verifies the result.
 */
desc('List selected hosts');
task('platform:encrypt', function () {

    $alias = get('alias');
    info('Encrypt environment variables for host <fg=cyan>'.$alias.'</>');

    // Prompt user for the value to encrypt
    $data = ask('Value to encrypt: ');
    if (empty($data)) {
        // Log and throw if no value is provided
        throw new RuntimeException('No value provided. Please provide a value to encrypt.');
    }

    // Encrypt the variable with the server public key
    $encrypted = encrypt($data);

    // Show the encrypted value to the user
    info("Encrypted entry generated:\n\n<fg=green>".$encrypted."</>\n\n");

    // Verify the encrypted variable by decrypting it
    $decrypted = decrypt($encrypted);
    if ($decrypted != $data) {
        // Log and throw if verification fails
        throw new RuntimeException('Verification failed. The encrypted variable does not match the original value.');
    }

    info('Variable successfully encrypted and verified.');
})->oncePerNode();
