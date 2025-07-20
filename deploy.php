<?php
namespace Deployer;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/mittwald/deployer-recipes/recipes/deploy.php';

// Mittwald API Token (alternativ Umgebungsvariable MITTWALD_API_TOKEN setzen)
set('mittwald_token', getenv('MITTWALD_API_TOKEN'));

// Repository
set('repository', 'git@github.com:maidem/taekwondo-mueller.git');

// PHP Version für Mittwald Projekt
set('php_version', '8.4');

// Mittwald App Host
mittwald_app('p-cv7flj') // Ersetze mit deiner UUID oder Kurz-ID
    ->set('public_path', '/')             // Pfad auf Mittwald-Server, meist '/'
    ->set('mittwald_app_dependencies', [
        'php'      => '{{php_version}}',
        'gm'       => '*',
        'composer' => '*',
    ]);

// Gemeinsame Dateien/Ordner (optional anpassen)
add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hooks
after('deploy:failed', 'deploy:unlock');
