<?php

namespace Deployer;

use RuntimeException;

/**
 * Calls the cPanel UAPI for a given module and function.
 *
 * @param  string  $module  The cPanel module name.
 * @param  string  $function  The function to call within the module.
 * @param  array  $args  Arguments for the API call.
 * @param  string  $token  The cPanel API token.
 * @return object The decoded JSON result from the API.
 *
 * @throws RuntimeException If the token is missing or the API returns errors.
 *
 * @desc This function wraps a cURL call to the cPanel UAPI, handling errors and decoding the response.
 */
function uapi($module, $function, $args, $token = null)
{
    // Init args if not set
    if (empty($args)) {
        $args = '';
    } else {
        $args = array_map(function ($key, $value) {
            return "$key=$value";
        }, array_keys($args), $args);

        $args = implode(' ', $args);
    }

    $result = json_decode(run("uapi --output=json $module $function ".$args))->result;

    if (isset($result->errors) && ! empty($result->errors)) {
        // Log and throw if API returns errors
        throw new RuntimeException('Error: '.implode(', ', $result->errors));
    }

    return $result;
}

function getRootDomain($fqdn)
{
    $parts = explode('.', $fqdn);
    $numParts = count($parts);

    if ($numParts > 2) {
        // Combine the last two parts to create the root domain
        $rootDomain = $parts[$numParts - 2].'.'.$parts[$numParts - 1];
    } else {
        $rootDomain = $fqdn;
    }

    return $rootDomain;
}

function getSubDomain($fqdn)
{
    $rootDomain = getRootDomain(trim($fqdn));
    $subDomain = trim(str_replace($rootDomain, '', $fqdn), '.');

    return $subDomain;
}

function searchDomain($domains, $fdqn)
{
    return array_search($fdqn, array_column($domains, 'domain'));
}

// Load additional cPanel-related deployment tasks
require_once __DIR__.'/crypto.php';
require_once __DIR__.'/set_env.php';
require_once __DIR__.'/cpanel/cpanel_writable.php';
require_once __DIR__.'/cpanel/cpanel_database.php';
require_once __DIR__.'/cpanel/cpanel_domain.php';
require_once __DIR__.'/cpanel/cpanel_mail.php';
require_once __DIR__.'/cpanel/cpanel_htaccess.php';
