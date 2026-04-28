<?php

namespace Deployer;

use Exception;

// Sets public assets paths. Default to [ 'build', 'dist' , 'js' , 'css' ]
// These paths represent the directories containing the assets to be uploaded to the remote server.
set('public_assets_paths', [
    'public/build',
    'public/js',
    'public/css',
]);

/**
 * Task to upload built assets to the remote server.
 *
 * This task ensures that the local repository is aligned with the remote repository,
 * verifies the existence of the required asset directories, and uploads them to the remote server.
 * It is designed to run only once during the deployment process.
 */
desc('Upload public assets');
task('deploy:upload_assets', function () {

    // Get the git binary path and deployment paths
    $git = get('bin/git'); // Path to the git binary
    $bare = parse('{{deploy_path}}/.dep/repo'); // Path to the bare repository on the remote server

    // Resolve the target ref (branch, tag, or revision) being deployed.
    // The bare repo HEAD always points to its default branch, so we must compare
    // the specific target ref on both sides instead of HEAD.
    $target = get('target') ?: 'HEAD';
    $escapedTarget = escapeshellarg($target);
    $escapedBare = escapeshellarg($bare);
    $escapedGit = escapeshellarg($git);
    $local = trim(runLocally("$escapedGit rev-parse $escapedTarget")); // Get the local SHA for the target
    $remote = trim(run("$escapedGit --git-dir=$escapedBare rev-parse $escapedTarget")); // Get the remote SHA for the same target

    // Ensure the local and remote repositories are aligned
    // If they are not aligned, throw an exception to prevent uploading mismatched assets.
    if ($local !== $remote) {
        throw new Exception("Tree mismatch, cannot upload locally built assets!\nRemote: $remote <- $target\nLocal : $local <- $target");
    }

    // Log a message indicating that the local repository is aligned with the remote repository
    info('Local repository aligned with <fg=cyan>'.get('hostname').'</> repository.');

    // Upload the built assets to the remote server
    foreach (get('public_assets_paths') as $path) {

        // Check if the asset directory exists locally
        // If it does not exist, throw an exception to notify the user to build the assets first.
        $escapedPath = escapeshellarg($path);
        if (trim(runLocally("test -d $escapedPath && echo 1 || echo 0")) !== '1') {
            throw new Exception("Directory $path does not exist.\nPlease build the assets first.");
        }

        // Log a message indicating the upload process for the current directory
        info('Uploading <fg=cyan>'.$path.'</> to <fg=cyan>'.get('hostname').'</>');

        // Upload the directory to the remote server
        upload(
            './'.$path.'/', // Local build directory
            '{{release_or_current_path}}/'.$path, // Remote public directory
            ['progress_bar' => false] // Disable progress bar for cleaner output
        );
    }
});
// Run this task after the code update step
// This ensures that the assets are uploaded after the latest code is deployed.
after('deploy:update_code', 'deploy:upload_assets');
