<?php

declare(strict_types=1);

namespace Waldhacker\Plausibleio\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waldhacker\Plausibleio\Dashboard\DataProvider\TimeSeriesDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class VisitorTimeSeriesController
{
    private ResponseFactoryInterface $responseFactory;
    //private ChartService $chartService;
    private TimeSeriesDataProvider $timeSeriesDataProvider;
    private ConfigurationService $configurationService;

    public function __construct(
        TimeSeriesDataProvider $timeSeriesDataProvider,
        ConfigurationService $configurationService,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->responseFactory = $responseFactory;
        $this->timeSeriesDataProvider = $timeSeriesDataProvider;
        $this->configurationService = $configurationService;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $timeFrame = $request->getQueryParams()['timeFrame'] ?? false;
        $site = $request->getQueryParams()['site'] ?? $this->configurationService->getDefaultSite();
        if (!in_array($timeFrame, $this->configurationService->getTimeFrameValues(), true) || $timeFrame === false) {
            $timeFrame = $this->configurationService->getDefaultTimeFrameValue();
        }

        $chartData = $this->timeSeriesDataProvider->getChartData($timeFrame, $site);

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json');
        $response->getBody()->write((string)json_encode($chartData, JSON_THROW_ON_ERROR));
        return $response;
    }
}
