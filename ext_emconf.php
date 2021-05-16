<?php

$EM_CONF[$_EXTKEY] = [
    'title'            => 'Plausible.io',
    'description'      => 'Privacy friendly tracking solution',
    'category'         => 'backend',
    'author'           => 'Susanne Moog',
    'author_email'     => 'susanne@waldhacker.dev',
    'author_company'   => '',
    'state'            => 'stable',
    'uploadfolder'     => '0',
    'clearCacheOnLoad' => 1,
    'version'          => '1.0.0',
    'constraints'      => [
        'depends' => [
            'typo3' => '11.0.0-11.4.99',
            'form' => '11.0.0-11.4.99'
        ]
    ]
];
