<?php

namespace Deployer;

use RuntimeException;

require_once __DIR__.'/crypto.php';

/**
 * Normalizes a regular env value before writing it to .env.
 *
 * @param  string|null  $value  The value to normalize.
 * @return string Normalized value, with spaces handled appropriately.
 */
function normalizeEnvFileValue(?string $value): string
{
    $value = trim((string) preg_replace('/\s+/', ' ', $value ?? ''));

    if ($value === '') {
        return '';
    }

    if (str_contains($value, ' ') && ! (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
        return '"'.str_replace('"', '\\"', $value).'"';
    }

    return $value;
}

/**
 * Normalizes a secret value before writing it to .env.
 *
 * @param  string  $value  The secret value to normalize.
 * @return string Normalized value, with newlines escaped and spaces handled appropriately.
 */
function normalizeSecretEnvFileValue(string $value): string
{
    $value = str_replace(["\r\n", "\r", "\n"], '\\n', trim($value));

    if ($value === '') {
        return '';
    }

    if (preg_match('/\s/', $value) === 1 && ! (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
        return '"'.str_replace('"', '\\"', $value).'"';
    }

    return $value;
}

/**
 * Resolves host secret definitions.
 *
 * Supported structures:
 * - `APP_KEY: ENCRYPTED_VALUE` (encrypted inline value)
 * - `- APP_KEY` (environment variable lookup by same name)
 * - `APP_KEY: { secret: ENCRYPTED_VALUE, env: APP_KEY_ENV }` (optional explicit fallback)
 *
 * @param  array<int|string, mixed>  $definitions
 * @param  null|callable(string): string|false  $resolver
 * @return array<string, array{value: string, encrypted: bool, source: string}>
 */
function resolveHostSecrets(array $definitions, ?callable $resolver = null): array
{
    $resolver ??= static fn (string $name): string|false => getenv($name);

    $resolved = [];

    foreach ($definitions as $key => $value) {
        $secretKey = is_int($key) ? trim((string) $value) : trim((string) $key);

        if ($secretKey === '') {
            throw new RuntimeException('Host secret keys can not be empty.');
        }

        if (is_array($value)) {
            $inlineSecret = trim((string) ($value['secret'] ?? ''));

            if ($inlineSecret !== '') {
                $resolved[$secretKey] = ['value' => $inlineSecret, 'encrypted' => true, 'source' => 'secret'];

                continue;
            }

            $source = trim((string) ($value['env'] ?? $secretKey));
        } elseif (is_int($key)) {
            $source = $secretKey;
        } elseif (is_string($value)) {
            $trimmedValue = trim($value);

            if (preg_match('/^%([A-Z0-9_]+)%$/', $trimmedValue, $matches) === 1) {
                $source = trim($matches[1]);

                if ($source === '') {
                    throw new RuntimeException("Invalid %VAR% reference for host secret [{$secretKey}].");
                }

                $resolvedValue = $resolver($source);

                if ($resolvedValue === false) {
                    warning("Missing environment variable [{$source}] for host secret [{$secretKey}].");

                    continue;
                }

                $resolved[$secretKey] = ['value' => $resolvedValue, 'encrypted' => false, 'source' => $source];

                continue;
            }

            if (str_starts_with($trimmedValue, '%')) {
                throw new RuntimeException("Invalid %VAR% reference for host secret [{$secretKey}].");
            }

            $inlineSecret = $trimmedValue;

            if ($inlineSecret === '') {
                $source = $secretKey;
            } else {
                $resolved[$secretKey] = ['value' => $inlineSecret, 'encrypted' => true, 'source' => 'secret'];

                continue;
            }
        } elseif ($value === null) {
            $source = $secretKey;
        } else {
            throw new RuntimeException('Host secrets must be a list of env names, a map of env key to encrypted value, or {secret?, env?}.');
        }

        if ($source === '') {
            $source = $secretKey;
        }

        $resolvedValue = $resolver($source);

        if ($resolvedValue === false) {
            throw new RuntimeException("Missing environment variable [{$source}] for host secret [{$secretKey}].");
        }

        $resolved[$secretKey] = ['value' => $resolvedValue, 'encrypted' => false, 'source' => $source];
    }

    return $resolved;
}

/**
 * Helper function to ensure an environment variable exists in the .env file.
 *
 * This function checks if the specified environment variable key exists in the .env file.
 * If it does not exist, it appends the key with an empty value to the file.
 *
 * @param  string  $key  The environment variable key.
 */
function touch($key)
{
    run("grep -c '^$key=' {{deploy_path}}/shared/.env || echo '$key=' >>  {{deploy_path}}/shared/.env");
}

/**
 * Helper function to set or update an environment variable in the .env file.
 *
 * This function checks if the specified environment variable key exists in the .env file.
 * If it exists, it updates the value of the key. If it does not exist, it appends the key with the specified value to the file.
 *
 * @param  string  $key  The environment variable key.
 * @param  string|null  $value  The value to set for the environment variable.
 */
function setenv($key, $value = null)
{
    $value = $value ?? '';
    run("if grep -q '^$key=' {{deploy_path}}/shared/.env; then sed -i -e '/^$key=/d' {{deploy_path}}/shared/.env; fi; printf '%s=%s\\n' '$key' ".escapeshellarg($value).' >> {{deploy_path}}/shared/.env');
}

/**
 * Helper function to set or update a secret environment variable in the .env file without logging its value.
 *
 * @param  string  $key  The environment variable key.
 * @param  string  $value  The value to set for the environment variable.
 */
function setSecretEnv($key, $value)
{
    run("if grep -q '^$key=' {{deploy_path}}/shared/.env; then sed -i -e '/^$key=/d' {{deploy_path}}/shared/.env; fi; printf '%s=%s\\n' '$key' %secret% >> {{deploy_path}}/shared/.env", secret: escapeshellarg($value));
}

/**
 * Task to set environment variables on the remote server.
 *
 * This task iterates over the labels of the current host and updates the environment variables accordingly.
 * It ensures that essential environment variables such as APP_KEY and APP_URL are set.
 */
desc('Apply remote env variables');
task('deploy:set_env', function () {
    // Iterate over the labels of the current host and set environment variables
    foreach (currentHost()->getLabels() as $label => $value) {
        // Sanitize label: add prefix, put uppercase, only alpha and underscore
        $key = 'APP_'.strtoupper(preg_replace('/[^a-z0-9A-Z_]/', '', $label));

        // Sanitize value: remove newlines and trim whitespace. If spaces are present, put the value in quotes
        $value = normalizeEnvFileValue((string) $value);

        info('Apply <fg=bright-magenta>'.$key.'='.$value.'</>');
        setenv($key, $value);
    }

    // Ensure APP_KEY is set, generate a new one if not
    info('Ensure <fg=magenta>APP_KEY</> is set');
    touch('APP_KEY');

    // Ensure APP_URL is set, default to the current host alias
    info('Ensure <fg=magenta>APP_URL</> is set');
    setenv('APP_URL', '{{alias}}');

    // Define APP_DEBUG based on the environment
    writeln('');
    $env = currentHost()->getLabels()['env'] ?? 'production';

    switch ($env) {
        case 'local':
        case 'staging':
            warning('Force <fg=bright-red>APP_DEBUG=true</>');
            setenv('APP_DEBUG', 'true');
            break;
        case 'production':
            info('Force <fg=green>APP_DEBUG=false</>');
            setenv('APP_DEBUG', 'false');
            break;
        default:
            warning('Unknown environment: <fg=red>'.$env.'</>, defaulting to APP_DEBUG=<fg=green>false</>');
            setenv('APP_DEBUG', 'false');
    }

    // Add an empty line for better readability in logs
    writeln('');

    // Set DEFAULT_USER_MAIL from the --default-user-mail deployer option
    $defaultUserMail = input()->getOption('default-user-mail');
    if (! empty($defaultUserMail)) {
        info('Apply <fg=bright-magenta>DEFAULT_USER_MAIL='.$defaultUserMail.'</>');
        setenv('DEFAULT_USER_MAIL', $defaultUserMail);
    }

    // Set DEFAULT_USER_NAME from the --default-user-name deployer option
    $defaultUserName = input()->getOption('default-user-name');
    if (! empty($defaultUserName)) {
        info('Apply <fg=bright-magenta>DEFAULT_USER_NAME='.$defaultUserName.'</>');
        setenv('DEFAULT_USER_NAME', $defaultUserName);
    }

    // Add an empty line for better readability in logs
    writeln('');

    $hostSecrets = currentHost()->get('secrets', []);

    if (! is_array($hostSecrets)) {
        throw new RuntimeException('Host secrets must be declared as an array.');
    }

    foreach (resolveHostSecrets($hostSecrets) as $key => $secret) {
        if (! preg_match('/^[A-Z0-9_]+$/', $key)) {
            throw new RuntimeException("Invalid secret key [{$key}]: must match ^[A-Z0-9_]+\$.");
        }

        $value = $secret['value'];

        if ($secret['encrypted'] === true) {
            info('Apply <fg=bright-magenta>'.$key.'</> from encrypted host secret');
            $value = decrypt($value);
        } else {
            info('Apply <fg=bright-magenta>'.$key.'</> from environment');
        }

        setSecretEnv($key, normalizeSecretEnvFileValue($value));
    }

    writeln('');
})->addAfter('artisan:key:generate');

// Run this task before caching the configuration
before('artisan:config:cache', 'deploy:set_env');
