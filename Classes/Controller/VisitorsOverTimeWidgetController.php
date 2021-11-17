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
use Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class VisitorsOverTimeWidgetController
{
    private ResponseFactoryInterface $responseFactory;
    private VisitorsOverTimeDataProvider $visitorsOverTimeDataProvider;
    private ConfigurationService $configurationService;

    public function __construct(
        VisitorsOverTimeDataProvider $visitorsOverTimeDataProvider,
        ConfigurationService $configurationService,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->visitorsOverTimeDataProvider = $visitorsOverTimeDataProvider;
        $this->configurationService = $configurationService;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $plausibleSiteId = $request->getQueryParams()['siteId'] ?? null;
        if ($plausibleSiteId === null || !in_array($plausibleSiteId, $this->configurationService->getAvailablePlausibleSiteIds(), true)) {
            $plausibleSiteId = $this->configurationService->getPlausibleSiteIdFromUserConfiguration();
        }

        $timeFrame = $request->getQueryParams()['timeFrame'] ?? null;
        if ($timeFrame === null || !in_array($timeFrame, $this->configurationService->getTimeFrameValues(), true)) {
            $timeFrame = $this->configurationService->getTimeFrameValueFromUserConfiguration();
        }

        $this->configurationService->persistPlausibleSiteIdInUserConfiguration($plausibleSiteId);
        $this->configurationService->persistTimeFrameValueInUserConfiguration($timeFrame);

        $chartData = $this->visitorsOverTimeDataProvider->getChartData($plausibleSiteId, $timeFrame);
        $overviewData = $this->visitorsOverTimeDataProvider->getOverview($plausibleSiteId, $timeFrame);
        $overviewData['current_visitors'] = $this->visitorsOverTimeDataProvider->getCurrentVisitors($plausibleSiteId);

        $data = [
            'chartData' => $chartData,
            'overViewData' => $overviewData,
        ];

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
