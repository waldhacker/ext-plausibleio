<?php

declare(strict_types=1);

namespace Waldhacker\Plausibleio\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waldhacker\Plausibleio\Services\ChartService;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class VisitorTimeSeriesController
{
    private ResponseFactoryInterface $responseFactory;
    private ChartService $chartService;
    private ConfigurationService $configurationService;

    public function __construct(
        ChartService $chartService,
        ConfigurationService $configurationService,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->chartService = $chartService;
        $this->configurationService = $configurationService;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $timeFrame = $request->getQueryParams()['timeFrame'] ?? false;
        $site = $request->getQueryParams()['site'] ?? $this->configurationService->getDefaultSite();
        if (!in_array($timeFrame, $this->configurationService->getTimeFrameValues(), true) || $timeFrame === false) {
            $timeFrame = $this->configurationService->getDefaultTimeFrameValue();
        }

        $chartData = $this->chartService->getChartDataForTimeSeries($timeFrame, $site);

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($chartData, JSON_THROW_ON_ERROR));
        return $response;
    }
}
