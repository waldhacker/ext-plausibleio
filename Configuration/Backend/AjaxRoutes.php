<?php

return [
    'plausible_visitorsovertime' => [
        'path' => '/plausibleio/visitorsovertime',
        'target' => \Waldhacker\Plausibleio\Controller\VisitorsOverTimeWidgetController::class,
    ],
    'plausible_countrymapdata' => [
        'path' => '/plausibleio/countrymapdata',
        'target' => \Waldhacker\Plausibleio\Controller\CountryMapDataWidgetController::class,
    ],
    'plausible_pagedata' => [
        'path' => '/plausibleio/pagedata',
        'target' => \Waldhacker\Plausibleio\Controller\PageDataWidgetController::class,
    ],
    'plausible_devicedata' => [
        'path' => '/plausibleio/devicedata',
        'target' => \Waldhacker\Plausibleio\Controller\DeviceDataWidgetController::class,
    ],
    'plausible_sourcedata' => [
        'path' => '/plausibleio/sourcedata',
        'target' => \Waldhacker\Plausibleio\Controller\SourceDataWidgetController::class,
    ],
];
