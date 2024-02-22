<?php

$EM_CONF[$_EXTKEY] = [
    'title'            => 'Plausible.io',
    'description'      => 'Privacy-friendly analytics integration for TYPO3 CMS. Analyze your audience with Plausible Analytics and see nice dashboards with analytics data directly in the TYPO3 backend.',
    'category'         => 'backend',
    'author'           => 'waldhacker',
    'author_email'     => 'hello@waldhacker.dev',
    'author_company'   => 'waldhacker UG (haftungsbeschränkt)',
    'state'            => 'stable',
    'uploadfolder'     => '0',
    'clearCacheOnLoad' => 1,
    'version'          => '2.2.1',
    'constraints'      => [
        'depends' => [
            'backend' => '11.5.0-12.4.99',
            'dashboard' => '11.5.0-12.4.99',
            'fluid' => '11.5.0-12.4.99',
            'frontend' => '11.5.0-12.4.99',
            'typo3' => '11.5.0-12.4.99',
        ],
    ],
];
