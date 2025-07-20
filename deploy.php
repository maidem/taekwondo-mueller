<?php

namespace Deployer;

require 'recipe/typo3.php';

set('repository', 'git@github.com:maidem/taekwondo-mueller.git');
set('shared_files', []);
set('shared_dirs', []);
set('writable_dirs', []);
set('allow_anonymous_stats', false);


host('production')
    ->set('hostname', getenv('DEPLOY_HOST'))
    ->set('remote_user', getenv('DEPLOY_USER'))
    ->set('deploy_path', getenv('DEPLOY_PATH'))
    ->set('identity_file', '~/.ssh/taekwondo-deployer');

set('git_ssh_command', 'ssh -i ~/.ssh/taekwondo-deployer -o StrictHostKeyChecking=accept-new');

after('deploy:failed', 'deploy:unlock');
