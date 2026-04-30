<?php

namespace Deployer;

// Load cryptographic helper functions for encryption/decryption tasks.
require_once __DIR__.'/../crypto.php';

/**
 * Task to decrypt environment variables for the current host.
 *
 * @desc Decrypts a selected or provided encrypted environment variable using the host's private key.
 */
desc('Decrypt environment variables for the current host');
task('platform:decrypt', function () {

    $alias = get('alias');
    info('Decrypt environment variables for host <fg=cyan>'.$alias.'</>');

    // Load environment variables from context
    $env = get('env');
    if (empty($env)) {
        // Log and return if no environment variables are found
        error('No environment variables found to decrypt.');

        return;
    }

    // Prompt user to select the environment variable to decrypt
    $list = array_keys($env);
    $name = askchoice('Name of the environment variable ', $list);

    // Get the encrypted value, prompt if not present
    $data = $env[$name];
    if (empty($data)) {
        $data = ask('Encrypted value of the environment variable:');
    }

    info("Decrypting environment variable:\n\n<fg=cyan>".$data.'</>');

    // Decrypt the variable with the server private key
    $decrypted = decrypt($data);

    // Show the decrypted value to the user
    info("Decrypted value:\n\n<fg=cyan>".$decrypted."</>\n\n");
});
