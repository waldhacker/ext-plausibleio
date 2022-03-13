<?php

declare(strict_types=1);

/*
 * This file is part of the plausibleio extension for TYPO3
 * - (c) 2021 waldhacker UG (haftungsbeschränkt)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Waldhacker\Plausibleio\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class PageDataWidgetController extends AbstractWidgetController
{
    private PageDataProvider $pageDataProvider;

    public function __construct(
        PageDataProvider $pageDataProvider,
        ConfigurationService $configurationService,
        ResponseFactoryInterface $responseFactory
    ) {
        parent::__construct($configurationService, $responseFactory);
        $this->pageDataProvider = $pageDataProvider;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        parent::__invoke($request);

        $data = [
            [
                'tab' => 'toppage',
                'data'=> $this->pageDataProvider->getTopPageData($this->plausibleSiteId, $this->timeFrame, $this->filterRepo),
            ],
            [
                'tab' => 'entrypage',
                'data' => $this->pageDataProvider->getEntryPageData($this->plausibleSiteId, $this->timeFrame, $this->filterRepo),
            ],
            [
                'tab' => 'exitpage',
                'data' => $this->pageDataProvider->getExitPageData($this->plausibleSiteId, $this->timeFrame, $this->filterRepo),
            ],
        ];

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
