<?php
defined('TYPO3') || die();

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class)
    ->configureContainer(
        new \B13\Container\Tca\ContainerConfiguration(
            'three-column-container',                // CType
            '3-Spalten-Layout',                      // Titel im Backend
            'Container mit drei Spalten',            // Beschreibung
            [
                [
                    ['name' => 'Spalte 1', 'colPos' => 300],
                    ['name' => 'Spalte 2', 'colPos' => 301],
                    ['name' => 'Spalte 3', 'colPos' => 302],
                ]
            ]
        )
        ->setIcon('EXT:taekwondo_mueller_sitepackage/Resources/Public/Icons/3cols.svg')
        ->setGroup('container')
    );

    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\B13\Container\Tca\Registry::class)
    ->configureContainer(
        new \B13\Container\Tca\ContainerConfiguration(
            'two-column-container',                 // CType
            '2-Spalten-Layout',                     // Label im Backend
            'Container mit zwei Spalten',           // Beschreibung
            [
                [
                    ['name' => 'Spalte links',  'colPos' => 310],
                    ['name' => 'Spalte rechts', 'colPos' => 311],
                ]
            ]
        )
        ->setIcon('EXT:taekwondo_mueller_sitepackage/Resources/Public/Icons/2cols.svg')
        ->setGroup('container')
    );

