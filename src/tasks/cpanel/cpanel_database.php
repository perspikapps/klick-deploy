<?php

namespace Deployer;

use RuntimeException;

/**
 * Task to create a database and user on cPanel if they do not already exist.
 *
 * This task ensures that the database and user are created with appropriate privileges.
 * It also sets environment variables for database connection.
 */
desc('Add database if not exists');
task('cpanel:database', function () {

    // Retrieve the remote user
    $user = currentHost()->get('remote_user');
    if (empty($user)) {
        throw new RuntimeException('No remote user found.');
    }

    // Retrieve the application name
    $name = currentHost()->getLabels()['name'] ?? '';
    if (empty($name)) {
        throw new RuntimeException('No application name found.');
    }

    // Retrieve the environment or use a default value
    $env = currentHost()->getLabels()['env'] ?? 'default';
    if (empty($env)) {
        throw new RuntimeException('No environment found.');
    }

    // Build the database name
    $db_name = $user.'_'.strtolower($name).'_'.strtolower($env);

    // Set environment variables for the database
    setenv('DB_CONNECTION', 'mysql');
    setenv('DB_HOST', 'localhost');
    setenv('DB_PORT', '3306');
    setenv('DB_DATABASE', $db_name);

    // Check if the database exists
    $bases = uapi('Mysql', 'list_databases', null);
    if (! in_array($db_name, array_column($bases->data, 'database'))) {
        // Create the database if it does not exist
        info('Create database <fg=bright-magenta>'.$db_name.'</>');
        uapi('Mysql', 'create_database', ['name' => $db_name]);
    } else {
        info('Database <fg=bright-magenta>'.$db_name.'</> already exists');
    }

    // Check if the user exists
    $users = uapi('Mysql', 'list_users', null);
    $password = bin2hex(random_bytes(8)); // Generate a random password

    if (! in_array($db_name, array_column($users->data, 'user'))) {
        // Create the user if it does not exist
        info('Create database user <fg=bright-magenta>'.$db_name.'</>');
        uapi('Mysql', 'create_user', ['name' => $db_name, 'password' => $password]);

        // Set environment variables for the user
        setenv('DB_USERNAME', $db_name);
        setenv('DB_PASSWORD', $password);
    } else {
        info('Database user <fg=bright-magenta>'.$db_name.'</> already exists');
    }

    // Grant all privileges to the user on the database
    uapi('Mysql', 'set_privileges_on_database', [
        'user' => $db_name,
        'database' => $db_name,
        'privileges' => 'ALL PRIVILEGES',
    ]);
})->select('type=cpanel&db=mysql');

// Ensure the task runs after the release step
after('deploy:release', 'cpanel:database');
