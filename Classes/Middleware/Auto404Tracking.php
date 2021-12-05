<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Waldhacker\Plausibleio\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Adminpanel\Controller\MainController;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Context\Context;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

/**
 * PSR-15 Middleware to initialize the admin panel
 *
 * @internal
 */
class Auto404Tracking implements MiddlewareInterface
{
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;

    public function __construct(
        PlausibleService $plausibleService,
        ConfigurationService $configurationService
    ) {
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
    }

    /**
     * Catches and tracks the 404 error if the corresponding tracking
     * is activated.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response->getStatusCode() == 404) {
            $config = $this->configurationService->getPlausibleConfigurationFromSiteLanguage($request->getAttribute('language'));

            if ($config['auto404Tracking']) {
                $cp = ['path' => $request->getAttribute('normalizedParams')->getRequestUri()];
                $this->plausibleService->recordEvent($config['siteId'], $config['apiUrl'], '404', $request, $cp);
            }
        }

        return $response;
    }
}
