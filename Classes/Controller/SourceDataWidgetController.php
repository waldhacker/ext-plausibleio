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
use Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class SourceDataWidgetController
{
    private ResponseFactoryInterface $responseFactory;
    private SourceDataProvider $dataProvider;
    private ConfigurationService $configurationService;

    public function __construct(
        SourceDataProvider $sourceDataProvider,
        ConfigurationService $configurationService,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->dataProvider = $sourceDataProvider;
        $this->configurationService = $configurationService;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $plausibleSiteId = $request->getQueryParams()['siteId'] ?? false;
        if (!in_array($plausibleSiteId, $this->configurationService->getAvailablePlausibleSiteIds(), true) || $plausibleSiteId === false) {
            $plausibleSiteId = $this->configurationService->getPlausibleSiteIdFromUserConfiguration();
        }

        $timeFrame = $request->getQueryParams()['timeFrame'] ?? false;
        if (!in_array($timeFrame, $this->configurationService->getTimeFrameValues(), true) || $timeFrame === false) {
            $timeFrame = $this->configurationService->getTimeFrameValueFromUserConfiguration();
        }

        $this->configurationService->persistPlausibleSiteIdInUserSession($plausibleSiteId);
        $this->configurationService->persistTimeFrameValueInUserSession($timeFrame);

        $data = [
            [
                'tab' => 'allsources',
                'data'=> $this->dataProvider->getAllSourcesData($plausibleSiteId, $timeFrame),
            ],
            [
                'tab' => 'mediumsource',
                'data' => $this->dataProvider->getMediumData($plausibleSiteId, $timeFrame),
            ],
            [
                'tab' => 'sourcesource',
                'data' => $this->dataProvider->getSourceData($plausibleSiteId, $timeFrame),
            ],
            [
                'tab' => 'campaignsource',
                'data' => $this->dataProvider->getCampaignData($plausibleSiteId, $timeFrame),
            ],
        ];

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
