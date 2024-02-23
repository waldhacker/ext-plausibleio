<?php

return [
    'dependencies' => [
        'backend',
        'core',
        'dashboard',
    ],
    'imports' => [
        '@waldhacker/plausibleio/' => [
            'path' => 'EXT:plausibleio/Resources/Public/JavaScript/',
            'exclude' => [
                'EXT:dashboard/Resources/Public/JavaScript/Contrib/',
            ],
        ],
        'd3' => 'EXT:plausibleio/Resources/Public/JavaScript/Contrib/d3.min.js',
        'd3-format' => 'EXT:plausibleio/Resources/Public/JavaScript/Contrib/d3-format.js',
        'datamaps' => 'EXT:plausibleio/Resources/Public/JavaScript/Contrib/datamaps.world.min.js',
        'topojson' => 'EXT:plausibleio/Resources/Public/JavaScript/Contrib/topojson-client.min.js',
    ],
];
