<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'taekwondomueller sitepackage',
    'description' => '',
    'category' => 'templates',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'fluid_styled_content' => '13.4.0-13.4.99',
            'rte_ckeditor' => '13.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'TaekwondoMueller\\TaekwondomuellerSitepackage\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'maidem',
    'author_email' => 'admin@maidem.de',
    'author_company' => 'taekwondo-mueller',
    'version' => '1.0.0',
];
