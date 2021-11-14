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

namespace Waldhacker\Plausibleio\Services;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\MathUtility;

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

    public function getRandomId(string $prefix): string
    {
        return $prefix . '-' . bin2hex(random_bytes(8));
    }

    /**
     * @return array|int|null Endpoint /api/v1/stats/realtime/visitors
     *                        returns an int and the rest an array.
     */
    public function sendAuthorizedRequest(string $plausibleSiteId, string $endpoint, array $params)
    {
        $apiBaseUrl = $this->configurationService->getApiBaseUrl($plausibleSiteId);
        $apiKey = $this->configurationService->getApiKey($plausibleSiteId);

        $endpoint = ltrim($endpoint, '/');
        $uri = $endpoint . http_build_query($params);
        $uri = rtrim($apiBaseUrl, '/') . '/' . $uri;

        $dataRequest = $this->factory
            ->createRequest('GET', $uri)
            ->withHeader('authorization', 'Bearer ' . $apiKey);

        $response = $this->client->sendRequest($dataRequest);
        if ($response->getStatusCode() !== 200) {
            if ($this->logger !== null) {
                $this->logger->warning(sprintf(
                    'Something went wrong while fetching plausible endpoint "%s" for site "%s": %s',
                    $endpoint,
                    $plausibleSiteId,
                    $response->getReasonPhrase()
                ));
            }
            return null;
        }

        $responseBody = (string)$response->getBody();
        // endpoint /api/v1/stats/realtime/visitors returns only a number
        if (MathUtility::canBeInterpretedAsInteger($responseBody)) {
            return (int)$responseBody;
        }

        $results = null;
        try {
            $responseData = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            $results = $responseData['results'] ?? null;
        } catch (\JsonException $e) {
            if ($this->logger !== null) {
                $this->logger->warning(sprintf(
                    'Something went wrong while decoding data from plausible endpoint "%s" for site "%s": %s',
                    $endpoint,
                    $plausibleSiteId,
                    $e->getMessage()
                ));
            }

            return null;
        }

        if ($results === null) {
            if ($this->logger !== null) {
                $this->logger->warning(sprintf(
                    'Something went wrong while fetching plausible endpoint "%s" for site "%s"',
                    $endpoint,
                    $plausibleSiteId
                ));
            }
        }

        return $results;
    }
}
