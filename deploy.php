<?php

namespace Deployer;

require 'recipe/typo3.php';

// -----------------------------------------------------------------------------
// Basis
// -----------------------------------------------------------------------------
set('repository', 'git@github.com:maidem/taekwondo-mueller.git');
set('shared_files', []);
set('shared_dirs', []);
set('writable_dirs', []);
set('allow_anonymous_stats', false);

// SSH für Git (mehr Debug + strikte Key-Nutzung)
set('git_ssh_command', 'ssh -i ~/.ssh/taekwondo-deployer -o IdentitiesOnly=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=~/.ssh/known_hosts');

// Host
host('production')
    ->set('hostname', getenv('DEPLOY_HOST'))
    ->set('remote_user', getenv('DEPLOY_USER'))
    ->set('deploy_path', getenv('DEPLOY_PATH'))
    ->set('identity_file', '~/.ssh/taekwondo-deployer');

// -----------------------------------------------------------------------------
// SSH vorbereiten (GitHub Key eintragen)
// -----------------------------------------------------------------------------
task('deploy:prepare_ssh', function () {
    run('mkdir -p ~/.ssh && ssh-keyscan -H github.com >> ~/.ssh/known_hosts');
});
before('deploy:update_code', 'deploy:prepare_ssh');

// -----------------------------------------------------------------------------
// DEBUG: Config + Umgebung anzeigen
// -----------------------------------------------------------------------------
desc('Debug: zeige Deployer-Variablen');
task('debug:config', function () {
    writeln('--- DEBUG CONFIG ---');
    writeln('host: {{hostname}}');
    writeln('user: {{remote_user}}');
    writeln('deploy_path: {{deploy_path}}');
    writeln('repository: {{repository}}');
    writeln('branch: ' . input()->getOption('branch'));
    writeln('git_ssh_command: {{git_ssh_command}}');
    writeln('--------------------');
});

// vor Deploy gleich mal zeigen
before('deploy:info', 'debug:config');

// -----------------------------------------------------------------------------
// DEBUG: Vor update_code – wer bin ich? welche Verzeichnisse?
// -----------------------------------------------------------------------------
desc('Debug: Remote-Check vor update_code');
task('debug:pre_update_code', function () {
    run("echo '--- PRE update_code ---'");
    run("whoami || true");
    run("pwd || true");
    run("ls -la {{deploy_path}} || true");
    run("ls -la {{deploy_path}}/.dep || true");
    run("ls -la {{deploy_path}}/.dep/repo || true");
    run("echo '----------------------'");
});
before('deploy:update_code', 'debug:pre_update_code');

// -----------------------------------------------------------------------------
// DEBUG: Nach update_code (wenn erfolgreich)
// -----------------------------------------------------------------------------
desc('Debug: Remote-Check nach update_code');
task('debug:post_update_code', function () {
    run("echo '--- POST update_code ---'");
    run("cd {{deploy_path}}/.dep/repo && git remote -v || true");
    run("cd {{deploy_path}}/.dep/repo && git show-ref --heads || true");
    run("echo '------------------------'");
});
after('deploy:update_code', 'debug:post_update_code');

// -----------------------------------------------------------------------------
// DEBUG: Bei Fehler – Git neu testen + ggf. re-clone
// -----------------------------------------------------------------------------
desc('Debug: Sammle Infos nach fehlgeschlagenem Deploy & optional re-clonen');
task('debug:on_fail', function () {
    writeln('!!! DEPLOY FAILED – sammle Debug-Daten !!!');

    // 1) Git-Verbindung direkt testen
    runLocally("GIT_SSH_COMMAND=\"{{git_ssh_command}}\" git ls-remote {{repository}} || true");

    // 2) Remote Git-Fetch im Fehlerfall (mit -v)
    run("if [ -d {{deploy_path}}/.dep/repo ]; then "
        . "cd {{deploy_path}}/.dep/repo && "
        . "GIT_SSH_COMMAND='{{git_ssh_command}} -v' git fetch origin --prune || true; "
        . "fi");

    // 3) Optional Auto-Reclone (nur wenn ENV DEBUG_FORCE_RECLONE=1 gesetzt)
    if (getenv('DEBUG_FORCE_RECLONE') === '1') {
        run("echo 'FORCE RECLONE aktiviert'; rm -rf {{deploy_path}}/.dep/repo || true");
    } else {
        writeln('Setze DEBUG_FORCE_RECLONE=1 im CI, um automatisch neu zu klonen.');
    }
});
after('deploy:failed', 'debug:on_fail');

// -----------------------------------------------------------------------------
// Standard-Fail-Unlock
// -----------------------------------------------------------------------------
after('deploy:failed', 'deploy:unlock');