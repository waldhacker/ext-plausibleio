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
use Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class DeviceDataWidgetController extends AbstractWidgetController
{
    private DeviceDataProvider $deviceDataProvider;

    public function __construct(
        DeviceDataProvider $deviceDataProvider,
        ConfigurationService $configurationService,
        ResponseFactoryInterface $responseFactory
    ) {
        parent::__construct($configurationService, $responseFactory);
        $this->deviceDataProvider = $deviceDataProvider;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        parent::__invoke($request);

        $data = [
            [
                'tab' => 'browser',
                'data'=> $this->deviceDataProvider->getBrowserData($this->plausibleSiteId, $this->timeFrame, $this->filterRepo),
            ],
            [
                'tab' => 'device',
                'data' => $this->deviceDataProvider->getDeviceData($this->plausibleSiteId, $this->timeFrame, $this->filterRepo),
            ],
            [
                'tab' => 'operatingsystem',
                'data' => $this->deviceDataProvider->getOSData($this->plausibleSiteId, $this->timeFrame, $this->filterRepo),
            ],
        ];

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, JSON_THROW_ON_ERROR));

        return $response;
    }
}
