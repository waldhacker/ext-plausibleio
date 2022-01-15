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
use Waldhacker\Plausibleio\Services\PlausibleService;

class SourceDataWidgetController
{
    private ResponseFactoryInterface $responseFactory;
    private SourceDataProvider $dataProvider;
    private ConfigurationService $configurationService;
    private PlausibleService $plausibleService;

    public function __construct(
        SourceDataProvider $sourceDataProvider,
        ConfigurationService $configurationService,
        PlausibleService $plausibleService,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->dataProvider = $sourceDataProvider;
        $this->configurationService = $configurationService;
        $this->plausibleService = $plausibleService;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $dashBoardId = $request->getQueryParams()['dashboard'] ?? ConfigurationService::DASHBOARD_DEFAULT_ID;

        $plausibleSiteId = $request->getQueryParams()['siteId'] ?? null;
        if ($plausibleSiteId === null || !in_array($plausibleSiteId, $this->configurationService->getAvailablePlausibleSiteIds(), true)) {
            $plausibleSiteId = $this->configurationService->getPlausibleSiteIdFromUserConfiguration($dashBoardId);
        }

        $timeFrame = $request->getQueryParams()['timeFrame'] ?? null;
        if ($timeFrame === null || !in_array($timeFrame, $this->configurationService->getTimeFrameValues(), true)) {
            $timeFrame = $this->configurationService->getTimeFrameValueFromUserConfiguration($dashBoardId);
        }

        // request->getQueryParams() already returns a json decoded array
        $filters = $request->getQueryParams()['filter'] ?? null;
        if (!is_array($filters)) {
            $filters = [];
        }
        $filters = $this->plausibleService->checkFilters($filters);

        $this->configurationService->persistPlausibleSiteIdInUserConfiguration($plausibleSiteId, $dashBoardId);
        $this->configurationService->persistTimeFrameValueInUserConfiguration($timeFrame, $dashBoardId);
        $this->configurationService->persistFiltersInUserConfiguration($filters, $dashBoardId);

        $data = [
            [
                'tab' => 'allsources',
                'data'=> $this->dataProvider->getAllSourcesData($plausibleSiteId, $timeFrame, $filters),
            ],
            [
                'tab' => 'mediumsource',
                'data' => $this->dataProvider->getMediumData($plausibleSiteId, $timeFrame, $filters),
            ],
            [
                'tab' => 'sourcesource',
                'data' => $this->dataProvider->getSourceData($plausibleSiteId, $timeFrame, $filters),
            ],
            [
                'tab' => 'campaignsource',
                'data' => $this->dataProvider->getCampaignData($plausibleSiteId, $timeFrame, $filters),
            ],
            [
                'tab' => 'termsource',
                'data' => $this->dataProvider->getTermData($plausibleSiteId, $timeFrame, $filters),
            ],
            [
                'tab' => 'contentsource',
                'data' => $this->dataProvider->getContentData($plausibleSiteId, $timeFrame, $filters),
            ],
        ];

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
