<?php
namespace Deployer;

require 'recipe/typo3.php';

set('repository', 'git@github.com:maidem/taekwondo-mueller.git');

host('live')
    ->set('hostname', getenv('DEPLOY_HOST') ?: 'example.com')
    ->set('remote_user', getenv('DEPLOY_USER') ?: 'username')
    ->set('deploy_path', getenv('DEPLOY_PATH') ?: 'path to your project')
    ->set('branch', getenv('DEPLOY_BRANCH') ?: 'main')
    ->set('identity_file', '~/.ssh/id_ed25519');

set('web_path', 'public/');
set('bin/php', '/usr/local/bin/php');


/**
 * Bereinigung von fehlerhaften Releases
 */
task('deploy:clean_release', function () {
    $nextRelease = (int)get('release_name') ?: count(get('releases_list')) + 1;
    if (test("[ -d {{deploy_path}}/releases/{$nextRelease} ]")) {
        run("rm -rf {{deploy_path}}/releases/{$nextRelease}");
    }
});

/**
 * Erzwungenes Entsperren bei fehlgeschlagenem Deployment
 */
task('deploy:force_unlock', function () {
    if (test("[ -f {{deploy_path}}/.dep/deploy.lock ]")) {
        run("rm -f {{deploy_path}}/.dep/deploy.lock");
    }
});

/**
 * Löscht alte Releases und hält maximal 5 Versionen
 */
task('cleanup', function () {
    $releases = get('releases_list');
    $keep = 5;
    if (count($releases) <= $keep) {
        return;
    }
    $releasesToDelete = array_slice($releases, $keep);
    foreach ($releasesToDelete as $release) {
        run("rm -rf {{deploy_path}}/releases/{$release}");
    }
});

/**
 * TYPO3 Cache leeren
 */
task('typo3:cache:flush', function () {
    run('{{bin/php}} {{release_path}}/vendor/bin/typo3 cache:flush');
});

/**
 * Deployment-Workflow
 */
task('deploy', [
    'deploy:prepare',
    'deploy:force_unlock',
    'deploy:lock',
    'deploy:clean_release',
    'deploy:release',
    'deploy:update_code',
    'deploy:vendors',
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
]);

/**
 * Führe Cache Flush nach erfolgreichem Deployment aus
 */
after('deploy', 'typo3:cache:flush');

/**
 * Falls ein Deployment fehlschlägt, entferne den Lock
 */
after('deploy:failed', 'deploy:unlock');
