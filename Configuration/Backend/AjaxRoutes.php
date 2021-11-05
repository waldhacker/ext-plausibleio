<?php

return [
    'plausible_visitortimeseries' => [
        'path' => '/plausibleio/visitortimeseries',
        'target' => \Waldhacker\Plausibleio\Controller\VisitorTimeSeriesController::class,
    ],
    'plausible_countrymap' => [
        'path' => '/plausibleio/countrymap',
        'target' => \Waldhacker\Plausibleio\Controller\CountryMapController::class,
    ],
    'plausible_page' => [
        'path' => '/plausibleio/page',
        'target' => \Waldhacker\Plausibleio\Controller\PageController::class,
    ],
    'plausible_device' => [
        'path' => '/plausibleio/device',
        'target' => \Waldhacker\Plausibleio\Controller\DeviceController::class,
    ],
    'plausible_source' => [
        'path' => '/plausibleio/source',
        'target' => \Waldhacker\Plausibleio\Controller\SourceController::class,
    ],
];
