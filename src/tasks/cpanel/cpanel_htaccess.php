<?php

namespace Deployer;

/**
 * Task to set shared configuration on the remote server.
 *
 * This task applies an .htaccess file to configure the web server for a Laravel application.
 * It includes rules for handling symbolic links, authorization headers, URL redirection,
 * and the front controller. The .htaccess file is deployed to the specified path.
 */
desc('Deploy htaccess on cpanel');
task('cpanel:htaccess', function () {
    // Define the .htaccess content with necessary configurations
    $hta = <<<'EOT'
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Http to Https redirection
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Enable symbolic links
    Options +FollowSymLinks

    # Handle Authorization MemberHeader
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Remove public URL from the path
    RewriteCond %{REQUEST_URI} !^/current/public/
    RewriteRule ^(.*)$ /current/public/\$1 [L,QSA]

    # Handle Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
    
</IfModule>
EOT;

    // Log the application of the .htaccess file
    info('Apply <fg=bright-magenta>htaccess</> file');

    // Write the .htaccess content to the deploy path
    run('echo "'.$hta.'" > {{deploy_path}}/.htaccess');
})->select('type=cpanel');

// Ensure the task runs after the release step
after('deploy:release', 'cpanel:htaccess');
