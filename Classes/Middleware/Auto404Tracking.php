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
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Adminpanel\Controller\MainController;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

use TYPO3\CMS\Core\Context\Context;

/**
 * PSR-15 Middleware to initialize the admin panel
 *
 * @internal
 */
class Auto404Tracking implements MiddlewareInterface
{
    private SiteFinder $siteFinder;

    public function __construct(
        //ExtensionConfiguration $extensionConfiguration,
        SiteFinder $siteFinder
    ) {
        //$this->extensionConfiguration = $extensionConfiguration;
        $this->siteFinder = $siteFinder;
        /*if ($this->getLanguageService() !== null) {
            $this->getLanguageService()->includeLLFile('EXT:' . self::EXT_KEY . '/Resources/Private/Language/locallang.xlf');
        }*/
    }

    /**
     * Catches and tracks the 404 error it if the corresponding tracking
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
            $config = $request->getAttribute('language')->toArray();

            if (array_key_exists('plausible_auto404Tracking', $config)) {
                if ($config['plausible_auto404Tracking']) {
                    $url = $request->getAttribute('normalizedParams')->getRequestUrl();
                    $path = $request->getAttribute('normalizedParams')->getRequestUri();
                    $referrer = $request->getServerParams()['HTTP_REFERER'] ?? '';
                    $userAgent = $request->getServerParams()['HTTP_USER_AGENT'] ?? '';
                    $xForwardedFor = $request->getServerParams()['HTTP_X_FORWARDED_FOR'] ?? '';
                    $host = $request->getAttribute('normalizedParams')->getRequestHost();

                    debug($request->getAttribute('language')->toArray());
                }
            }
        }

        return $response;
    }
}
