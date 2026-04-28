<?php

namespace Deployer;

// Load cryptographic helper functions for encryption/decryption tasks.
require_once __DIR__.'/../crypto.php';

/**
 * Task to save or manage the public key for the current host.
 *
 * @desc Save public key for the current host. Offers options to regenerate, download, or view the public key.
 */
desc('Save public key for the current host');
task('platform:savepub', function () {

    $alias = get('alias');

    // Check if private key exists, generate public key from it if so, otherwise generate new key pair
    if (! test('[ -f ~/.ssh/id_rsa ]')) {
        // Prompt user to generate new key pair if private key is missing
        $gen = askChoice('No private key found, generating new key pair', ['no', 'yes'], 1);
        if ($gen == 'no') {
            warning('No key pair generated, exiting');

            return;
        }

        // Generate new RSA key pair
        run('openssl genrsa -out ~/.ssh/id_rsa 2048');
        run('openssl rsa -in ~/.ssh/id_rsa -pubout -out  ~/.ssh/id_rsa.pub');
        run('chmod 600 ~/.ssh/id_rsa');
        run('chmod 644 ~/.ssh/id_rsa.pub');
        info('New key pair generated on the <fg=cyan>'.$alias.'</>');
    }

    // Present options to the user for managing the public key
    $choices = [
        'Regenerate public key',
        'Download public key',
        'View openssh public key',
    ];
    $gen = askChoice('What would you like to do?', $choices, 2);

    // get position of the selected choice in the array
    $pos = array_search($gen, $choices);

    if ($pos == 0) {
        // Regenerate public key from private key
        run('openssl rsa -in ~/.ssh/id_rsa -pubout -out  ~/.ssh/id_rsa.pub');
        info('Public key regenerated on the server');
    }

    if ($pos == 1) {
        // Download the public key to local machine
        $publicKeyName = str_replace('.', '_', $alias).'.id_rsa.pub';
        download('~/.ssh/id_rsa.pub', './'.$publicKeyName);
        info('Public key saved as <fg=cyan>'.$publicKeyName.'</>');
    }

    if ($pos == 2) {
        // Display the public key in OpenSSH format
        $publicKey = run('ssh-keygen -y -f ~/.ssh/id_rsa ');
        info("Public key is: \n\n<fg=cyan>".$publicKey."</>\n\n");
    }
})->oncePerNode();
