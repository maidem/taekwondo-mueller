<?php
namespace Deployer;

require 'recipe/typo3.php';

// --- Basis-Repo ---
set('repository', 'git@github.com:maidem/taekwondo-mueller.git');

// Optional: shallow clone deaktivieren (sicherer bei Tags, History)
set('git_tty', true); // interaktive Auth braucht man selten, aber TTY schadet nicht
set('writable_mode', 'chmod'); // oder acl/fastcgi, je nach Server

// Shared dirs/files (TYPO3 spezifisch anpassen)
set('shared_dirs', [
    'var',           // TYPO3 var/ (Caches, Logs, Sessions)
    'public/fileadmin',
    'public/uploads',
]);

set('shared_files', [
    '.env',          // Falls du env-Dateien nutzt
    'public/typo3conf/LocalConfiguration.php',
    'public/typo3conf/AdditionalConfiguration.php',
]);

// Writable dirs (Webserver braucht Schreibrechte)
set('writable_dirs', [
    'var',
    'public/fileadmin',
    'public/uploads',
]);

// PHP Pfad auf dem Server (ggf. anpassen)
set('bin/php', '/usr/local/bin/php');

// Webroot relativ zum Release-Pfad
set('web_path', 'public/');

// Host-Definition (über GitHub-Umgebungsvariablen überschreibbar)
host('live')
    ->set('hostname', getenv('DEPLOY_HOST') ?: 'example.com')
    ->set('remote_user', getenv('DEPLOY_USER') ?: 'username')
    ->set('deploy_path', getenv('DEPLOY_PATH') ?: '/var/www/your-project')
    ->set('branch', getenv('DEPLOY_BRANCH') ?: 'main')
    // Wichtig: Muss mit Workflow-Key-Datei übereinstimmen!
    ->set('identity_file', '~/.ssh/id_ed25519_deployer');

// Erzwingt, dass git auf dem Remote-Server den korrekten SSH-Key nutzt (wichtig für update_code)
// Für Produktion ggf. Fingerprint-Pinning ergänzen.
set('git_ssh_command', 'ssh -i ~/.ssh/id_ed25519_deployer -o StrictHostKeyChecking=accept-new');

// Robustere Code-Aktualisierung: direktes Clone pro Release statt Mirror+remote update
set('update_code_strategy', 'clone');

// Shallow-Clone für Geschwindigkeit; volle History? -> auf 0 oder false ändern
set('git_depth', 1);

// --- Direktes Git-Clone statt Mirror/Fetch (override deploy:update_code) ---
// Nutzt git_ssh_command (oben gesetzt). Shallow via git_depth.
// Entfernt Deployer-Mirror (\.dep\/repo) & das fehlerhafte 'git remote update'.
task('deploy:update_code', function () {
    $depth = get('git_depth');
    $depthFlag = '';
    if (is_numeric($depth) && (int)$depth > 0) {
        $depthFlag = '--depth='.(int)$depth;
    }
    $repository = get('repository');
    $branch     = get('branch');
    // Clone direkt in das vorbereitete Release-Verzeichnis.
    $cmd = trim("git clone $depthFlag --single-branch --branch $branch $repository {{release_path}}");
    run($cmd);
    // Optional: Git-Metadaten nach dem Clone entfernen (wenn auf Prod nicht nötig)
    // run('rm -rf {{release_path}}/.git');
});

/* ----------------------------------------------------------
 | Custom Tasks
 * --------------------------------------------------------*/

// Bereinigung von (halb-)angelegten Releases
// (Nützlich, wenn Deployer abbricht, bevor alles erstellt ist)
task('deploy:clean_release', function () {
    $nextRelease = (int)get('release_name') ?: count(get('releases_list')) + 1;
    if (test("[ -d {{deploy_path}}/releases/{$nextRelease} ]")) {
        run("rm -rf {{deploy_path}}/releases/{$nextRelease}");
    }
});

// Erzwungenes Entsperren bei fehlgeschlagenem Deployment
task('deploy:force_unlock', function () {
    if (test('[ -f {{deploy_path}}/.dep/deploy.lock ]')) {
        run('rm -f {{deploy_path}}/.dep/deploy.lock');
    }
});

// Eigene Cleanup-Strategie: halte max. 5 Releases
task('cleanup:custom', function () {
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

// TYPO3 Cache Flush nach Deployment
task('typo3:cache:flush', function () {
    run('{{bin/php}} {{release_path}}/vendor/bin/typo3 cache:flush');
});

/* ----------------------------------------------------------
 | Haupt-Deploy-Flow
 * --------------------------------------------------------*/

desc('Deployment');
task('deploy', [
    'deploy:prepare',      // erstellt Releases/Shared-Struktur
    'deploy:force_unlock', // sicherheitshalber
    'deploy:lock',         // lock
    'deploy:clean_release',
    'deploy:release',
    'deploy:update_code',  // git clone/checkout
    'deploy:vendors',      // composer install auf Server (optional, wenn du vendor mitlieferst, entfernen)
    'deploy:shared',
    'deploy:writable',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup:custom',      // alte Releases weg
]);

// Nach erfolgreichem Deployment: TYPO3 Cache flushen
after('deploy', 'typo3:cache:flush');

// Falls Deployment fehlschlägt: Lock entfernen
after('deploy:failed', 'deploy:unlock');