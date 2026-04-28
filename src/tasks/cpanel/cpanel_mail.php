<?php

namespace Deployer;

use Exception;
use Symfony\Component\Console\Input\InputOption;

desc('Configure cPanel mail server with account verification');

option('default-user-mail', null, InputOption::VALUE_OPTIONAL, 'Default user email');
option('default-user-name', null, InputOption::VALUE_OPTIONAL, 'Default user name');

// Define a task to configure mail server and ensure mailer account exists
task('cpanel:mail', function () {

    $appDomain = get('alias');
    $clientSettings = uapi('Email', 'get_client_settings', null);
    $smtpHost = $clientSettings->data->smtp_host ?? $appDomain;
    $smtpPort = $clientSettings->data->smtp_port ?? null;

    if (empty($smtpPort)) {
        $servers = uapi('Chkservd', 'get_exim_ports', null);
        $smtpPort = $servers->data->ports[0] ?? 25;
        warning('Could not resolve SMTP port from Email::get_client_settings, falling back to Exim port lookup.');
    }

    $mailerEmail = 'no-reply@'.$appDomain;

    // Check if mailer email account exists
    info("Checking if email account <fg=bright-cyan>{$mailerEmail}</> exists...");
    $emailAccounts = uapi('Email', 'list_pops', ['domain' => $appDomain]);
    $mailerExists = false;

    if (isset($emailAccounts->data)) {
        info('Found <fg=bright-yellow>'.count($emailAccounts->data)."</> email accounts for domain <fg=bright-cyan>{$appDomain}</>");
        foreach ($emailAccounts->data as $account) {

            if ($account->email === $mailerEmail) {
                $mailerExists = true;
                info("Found existing mailer account: <fg=bright-green>{$mailerEmail}</>");
                break;
            }
        }
        if (! $mailerExists) {
            warning("Mailer account <fg=bright-cyan>{$mailerEmail}</> not found in existing accounts");
        }
    } else {
        warning("No email accounts found for domain <fg=bright-cyan>{$appDomain}</>");
    }

    // Generate a random password for the mailer account
    $randomPassword = bin2hex(random_bytes(16)); // Generate random 32-character password
    // Set environment variables for the smtp server
    setenv('MAIL_USERNAME', $mailerEmail);
    setenv('MAIL_PASSWORD', $randomPassword);
    setenv('MAIL_MAILER', 'smtp');
    setenv('MAIL_HOST', $smtpHost);
    setenv('MAIL_PORT', $smtpPort);
    setenv('MAIL_ENCRYPTION', null);

    // Create mailer account if it doesn't exist
    if (! $mailerExists) {

        $createResult = uapi('Email', 'add_pop', [
            'domain' => $appDomain,
            'email' => 'no-reply',
            'password' => $randomPassword,
            'quota' => 0, // Unlimited quota
        ]);

        if ($createResult->status === 1) {
            info("Created email account <fg=bright-green>{$mailerEmail}</> with random password");
        } else {
            throw new Exception("Failed to create email account {$mailerEmail}: ".($createResult->errors[0] ?? 'Unknown error'));
        }
    } else {
        info("Email account <fg=bright-green>{$mailerEmail}</> already exists");

        $updateResult = uapi('Email', 'passwd_pop', [
            'domain' => $appDomain,
            'email' => 'no-reply',
            'password' => $randomPassword,
        ]);

        if ($updateResult->status !== 1) {
            throw new \Exception("Failed to update password for email account {$mailerEmail}: ".($updateResult->errors[0] ?? 'Unknown error'));
        }
    }

    // Suspend incoming mail
    $suspendResult = uapi('Email', 'suspend_incoming', [
        'email' => $mailerEmail,
    ]);

    if ($suspendResult->status === 1) {
        info("Suspended incoming mail for <fg=bright-green>{$mailerEmail}</>");
    } else {
        warning("Failed to suspend incoming mail for {$mailerEmail}: ".($suspendResult->errors[0] ?? 'Unknown error'));
    }

    // Email config
    setenv('MAIL_FROM_ADDRESS', $mailerEmail);
    setenv('MAIL_FROM_NAME', 'Mailer');

    info('Mail server <fg=bright-magenta>'.$smtpHost.':'.$smtpPort.'</> configured with account <fg=bright-green>'.$mailerEmail.'</>');
})->select('type=cpanel&mail=smtp');

// Ensure the mail task runs after the release task
after('deploy:release', 'cpanel:mail');
