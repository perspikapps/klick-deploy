<?php

namespace Deployer;

use RuntimeException;

/**
 * Decrypts a value using the provided private key.
 *
 * @param  string  $value  The value to decrypt (base64 encoded).
 * @param  string  $private_key  Path to the private key file.
 * @return string Decrypted value.
 *
 * @throws RuntimeException If decryption fails or value is empty.
 *
 * @desc This function uses OpenSSL to decrypt a base64-encoded value using a private key.
 *       It throws an exception if the decryption fails or the result is empty.
 */
function decrypt($value, $private_key = '~/.ssh/id_rsa')
{
    // if value is empty, return null
    if (empty($value)) {
        return null;
    }

    // Run OpenSSL decryption command on the remote host
    $value = run('echo \'%secret%\' | base64 -d | openssl pkeyutl -decrypt -inkey '.$private_key, secret: $value);
    if (empty($value)) {
        // Log and throw if decryption fails
        throw new RuntimeException('Value is empty');
    }

    return $value;
}

/**
 * Encrypts a value using the provided public or private key.
 *
 * @param  string  $value  The value to encrypt.
 * @param  string  $public_key  Path to the public key file.
 * @return string Encrypted value (base64 encoded).
 *
 * @throws RuntimeException If encryption fails or key file is missing.
 *
 * @desc This function uses OpenSSL to encrypt a value using a public key.
 *       It throws an exception if the key file is missing or the result is empty.
 */
function encrypt($value, $public_key = '~/.ssh/id_rsa.pub')
{
    // Check if the public key file exists
    if (! test('[ -f '.$public_key.' ]')) {
        throw new RuntimeException("Public key file not found: {$public_key}.\nPlease generate it first.");
    }

    // Run OpenSSL encryption command on the remote host
    $value = run('echo \'%secret%\' | openssl pkeyutl -encrypt -pubin -inkey '.$public_key.' | base64', secret: $value);

    if (empty($value)) {
        // Log and throw if encryption fails
        throw new RuntimeException('Value is empty after encryption');
    }

    return $value;
}
