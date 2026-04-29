<?php

namespace Deployer;

use RuntimeException;

desc('Add subdomain if not exists');

// Define a task to add a subdomain if it doesn't already exist
task('cpanel:subdomain', function () {

    // Retrieve the alias (subdomain) and deployment path from the current host configuration
    $alias = get('alias');
    $deploy_path = get('deploy_path');

    // Validate that the alias (hostname) is provided
    if (empty($alias)) {
        throw new RuntimeException('No hostname found.');
    }

    // Retrieve the list of domains from the cPanel API
    $domains = uapi('DomainInfo', 'domains_data', null);

    // Ensure that domains data is retrieved successfully
    if (empty($domains)) {
        throw new RuntimeException('No domains found.');
    }

    // Check if the alias exists as an main domain
    $is_main = ($domains->data->main_domain->domain ?? null) == $alias;

    // If the alias exists as a main domain, check if the document root matches the deployment path
    if ($is_main !== false) {
        info('Domain <fg=bright-magenta>'.$alias.'</> already exists as main domain');

        if ($domains->data->main_domain->documentroot !== $deploy_path) {
            throw new RuntimeException('Domain <fg=bright-magenta>'.$alias."</> already exists but the document root is different.\nPlease align the deploy paths.");
        }

        return;
    }

    // Check if the alias exists as an addon domain
    $idx = searchDomain($domains->data->addon_domains, $alias);

    // If the alias exists as an addon domain, check if the document root matches the deployment path
    if ($idx !== false) {
        info('Domain <fg=bright-magenta>'.$alias.'</> already exists as addon domain');

        if ($domains->data->addon_domains[$idx]->documentroot !== $deploy_path) {
            throw new RuntimeException('Domain <fg=bright-magenta>'.$alias."</> already exists but the document root is different.\nPlease align the deploy paths.");
        }

        return;
    }

    // Check if the alias exists as a subdomain
    $idx = searchDomain($domains->data->sub_domains, $alias);

    // If the alias exists as a subdomain, check if the document root matches the deployment path
    if ($idx !== false) {
        info('Domain <fg=bright-magenta>'.$alias.'</> already exists as subdomain');

        if ($domains->data->sub_domains[$idx]->documentroot !== $deploy_path) {
            throw new RuntimeException('Subdomain <fg=bright-magenta>'.$alias."</> already exists but the document root is different.\nPlease align the deploy paths.");
        }

        return;
    }

    // find subdomain and root domain
    $rootdomain = getRootDomain($alias);
    $subdomain = getSubDomain($alias);

    // Check if the subdomain is empty
    if (empty($subdomain)) {
        throw new RuntimeException('Subdomain <fg=bright-magenta>'.$alias.'</> cannot be empty.');
    }

    // Check if the root domain exists in the main domain or subdomains
    $idx = ($domains->data->main_domain->domain ?? null) == $rootdomain || searchDomain($domains->data->addon_domains, $rootdomain);

    // If the root domain does not exist, throw an error
    if (! $idx) {
        throw new RuntimeException('Subdomain <fg=bright-magenta>'.$alias."</> does not have a root domain defined on this server.\nPlease check the domain configuration.");
    }

    // Log the creation of the subdomain
    info('Create subdomain <fg=bright-magenta>'.$alias.'</> for <fg=bright-magenta>'.$deploy_path.'</>');

    // Use the cPanel API to add the subdomain
    uapi('SubDomain', 'addsubdomain', [
        'domain' => $subdomain,
        'rootdomain' => $rootdomain,
        'dir' => $deploy_path, // Set the document root
    ]);
})->select('type=cpanel');

// Ensure the subdomain task runs after the release task
after('deploy:release', 'cpanel:subdomain');
