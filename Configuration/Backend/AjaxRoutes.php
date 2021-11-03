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
];
