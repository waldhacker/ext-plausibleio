<?php
/**
 * An array consisting of implementations of middlewares for a middleware stack to be registered
 *  'stackname' => [
 *      'middleware-identifier' => [
 *         'target' => classname or callable
 *         'before/after' => array of dependencies
 *      ]
 *   ]
 */
return [
    'frontend' => [
        'waldhacker/plausibleio/pageNotFoundTracking' => [
            'target' => Waldhacker\Plausibleio\Middleware\Auto404Tracking::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
                //'typo3/cms-core/response-propagation',
            ],
            'after' => [
            ],
        ],
    ],
];
