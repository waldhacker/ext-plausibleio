<?php

declare(strict_types=1);

namespace Waldhacker\Plausibleio\Services;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class PlausibleService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private RequestFactoryInterface $factory;
    private ClientInterface $client;
    private ConfigurationService $configurationService;

    public function __construct(
        RequestFactoryInterface $factory,
        ClientInterface $client,
        ConfigurationService $configurationService
    ) {
        $this->factory = $factory;
        $this->client = $client;
        $this->configurationService = $configurationService;
    }

    public function getVisitors(string $timeFrame, string $site): array
    {
        $timeSeriesApi = 'api/v1/stats/timeseries?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
        ];

        $uri = $timeSeriesApi . http_build_query($params);
        return $this->sendAuthorizedRequest($uri);
    }

    public function getBrowserData(string $timeFrame, string $site): array
    {
        $browserDataApi = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
            'property' => 'visit:browser',
            'metrics' => 'visitors',
        ];
        $uri = $browserDataApi . http_build_query($params);
        return $this->sendAuthorizedRequest($uri);
    }

    public function getDeviceData(string $timeFrame, string $site): array
    {
        $deviceDataApi = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
            'property' => 'visit:device',
            'metrics' => 'visitors',
        ];
        $uri = $deviceDataApi . http_build_query($params);
        return $this->sendAuthorizedRequest($uri);
    }

    public function sendAuthorizedRequest(string $uri): array
    {
        $baseDomain = $this->configurationService->getBaseUrl();
        $uri = $baseDomain . $uri;
        $dataRequest = $this
            ->factory
            ->createRequest('GET', $uri)
            ->withHeader('authorization', 'Bearer ' . $this->configurationService->getApiKey());
        $response = $this->client->sendRequest($dataRequest);
        if ($response->getStatusCode() !== 200) {
            $this->logger->warning('Something went wrong while fetching analytics. ' . $response->getReasonPhrase());
            return [];
        }
        $responseBody = (string)$response->getBody();
        return (json_decode($responseBody, false, 512, JSON_THROW_ON_ERROR))->results;
    }
}
