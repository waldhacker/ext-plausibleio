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
        $timeFrame = $request->getQueryParams()['timeFrame'] ?? false;
        $site = $request->getQueryParams()['site'] ?? $this->configurationService->getDefaultSite();
        if (!in_array($timeFrame, $this->configurationService->getTimeFrameValues(), true) || $timeFrame === false) {
            $timeFrame = $this->configurationService->getDefaultTimeFrameValue();
        }

        $data = [
            [
                'tab' => 'allsources',
                'data'=> $this->dataProvider->getAllSourcesData($timeFrame, $site),
            ],
            [
                'tab' => 'mediumsource',
                'data' => $this->dataProvider->getMediumData($timeFrame, $site),
            ],
            [
                'tab' => 'sourcesource',
                'data' => $this->dataProvider->getSourceData($timeFrame, $site),
            ],
            [
                'tab' => 'campaignsource',
                'data' => $this->dataProvider->getCampaignData($timeFrame, $site),
            ],
        ];

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
