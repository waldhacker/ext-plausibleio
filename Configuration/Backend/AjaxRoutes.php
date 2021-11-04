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
];
