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
use Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class GoalDataWidgetController extends AbstractWidgetController
{
    private GoalDataProvider $dataProvider;

    public function __construct(
        GoalDataProvider $dataProvider,
        ConfigurationService $configurationService,
        ResponseFactoryInterface $responseFactory
    ) {
        parent::__construct($configurationService, $responseFactory);
        $this->dataProvider = $dataProvider;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        parent::__invoke($request);

        $data = [
            [
                'tab' => 'goal',
                'data'=> $this->dataProvider->getGoalsData($this->plausibleSiteId, $this->timeFrame , $this->filterRepo),
            ],
        ];

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($data, JSON_THROW_ON_ERROR));
        return $response;
    }
}
