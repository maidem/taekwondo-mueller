<?php
namespace Deployer;

require 'recipe/common.php';

// --- Projekt ---
set('application', 'taekwondo-mueller');
set('repository', 'git@github.com:maidem/taekwondo-mueller.git');

// Branch aus Secret, sonst main.
set('branch', function () {
    return getenv('DEPLOY_BRANCH') ?: 'main';
});

// PHP-Binary auf dem Server (du hast 8.4 installiert).
set('bin/php', '/usr/local/bin/php');

// TYPO3-Verzeichnisstruktur (Composer-basierte Installs, public/ Webroot)
set('shared_dirs', [
    'public/fileadmin',
    'public/uploads',
    'public/typo3temp',
    'var',
]);
set('shared_files', [
    // Lege hier Dateien ab, die server-spezifisch sind:
    'config/system/additional.php',
    'public/.htaccess',
    'public/.user.ini'
]);
set('writable_dirs', [
    'var',
    'public/fileadmin',
    'public/uploads',
]);
set('allow_anonymous_stats', false);
set('keep_releases', 5);
set('ssh_private_key', getenv('SSH_PRIVATE_KEY'));
// --- Host Definition (aus GitHub Secrets via ENV) ---
host('live')
    ->set('hostname', getenv('DEPLOY_HOST') ?: 'example.com')
    ->set('remote_user', getenv('DEPLOY_USER') ?: 'deployer')
    ->set('deploy_path', getenv('DEPLOY_PATH') ?: '/var/www/html/typo3');

// --------------------------------------
// TYPO3 Tasks
// --------------------------------------
desc('Flush TYPO3 cache');
task('typo3:cache:flush', function () {
    // Nach Symlink auf "current" ausführen.
    run('{{bin/php}} {{current_path}}/vendor/bin/typo3 cache:flush || true');
});

// Optional: Datenbankschema-Check (nur wenn gewünscht)
// task('typo3:db:update', function () {
//     run('{{bin/php}} {{current_path}}/vendor/bin/typo3 database:updateschema --no-interaction || true');
// });

// --------------------------------------
// Hooks
// --------------------------------------
after('deploy:symlink', 'typo3:cache:flush');
after('deploy:failed', 'deploy:unlock');
after('deploy:update_code', 'deploy:vendors');

// --------------------------------------
// Rollback Task
// --------------------------------------
desc('Rollback to previous release');
task('rollback', function () {
    run('cd {{deploy_path}} && ln -nfs $(ls -td releases/* | sed -n 2p) current');
});

task('fix:permissions', function () {
    run('find {{release_path}} -type d -exec chmod 2770 {} +');
    run('find {{release_path}} -type f -exec chmod 0660 {} +');
});

after('deploy:prepare', 'fix:permissions');