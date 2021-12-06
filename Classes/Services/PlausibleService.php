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

use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\MathUtility;
use GuzzleHttp\Exception\ClientException;

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

    private function logWarning(string $text)
    {
        if ($this->logger !== null)
            $this->logger->warning(trim($text));
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
            $this->logWarning(sprintf(
                    'Something went wrong while fetching plausible endpoint "%s" for site "%s": %s',
                    $endpoint,
                    $plausibleSiteId,
                    $response->getReasonPhrase()
                ));

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
            $this->logWarning(sprintf(
                    'Something went wrong while decoding data from plausible endpoint "%s" for site "%s": %s',
                    $endpoint,
                    $plausibleSiteId,
                    $e->getMessage()
                ));

            return null;
        }

        if ($results === null) {
            $this->logWarning(sprintf(
                    'Something went wrong while fetching plausible endpoint "%s" for site "%s"',
                    $endpoint,
                    $plausibleSiteId
                ));
        }

        return $results;
    }

    /**
     * See https://plausible.io/docs/events-api
     *
     * @param string $plausibleSiteId Domain name of the site in Plausible
     * @param string $apiUrl Plausible API base url
     * @param string $eventName Name of the event. Can specify pageview which is
     *      a special type of event in Plausible. All other names will be treated
     *      as custom events.
     * @param ServerRequestInterface $request
     * @param array $customProperties Custom properties for the event. Custom properties
     *      only accepts scalar values such as strings, numbers and booleans.
     *      Data structures such as objects, arrays etc. are not accepted.
     *      e.g. ['method' => 'HTTP', 'reloaded' => true]
     * @return bool Returns true if the event was successfully tracked, otherwise false.
     */
    public function recordEvent(string $plausibleSiteId, string $apiUrl, string $eventName, ServerRequestInterface $request, array $customProperties = null): bool
    {
        $endpoint = 'api/event';
        $pageUrl = $request->getAttribute('normalizedParams')->getRequestUrl();
        $referrer = $request->getServerParams()['HTTP_REFERER'] ?? '';
        $userAgent = $request->getServerParams()['HTTP_USER_AGENT'] ?? '';
        $xForwardedFor = $request->getServerParams()['HTTP_X_FORWARDED_FOR'] ?? '';

        if ($plausibleSiteId == '') {
            $this->logWarning(sprintf(
                'Plausible site id can\'t be blank on recording event at endpoint "%s"',
                $endpoint
            ));
            return false;
        }
        if ($apiUrl == '') {
            $this->logWarning(sprintf(
                'Plausible API base url can\'t be blank on recording event at endpoint "%s" for site "%s"',
                $endpoint,
                $plausibleSiteId
            ));
            return false;
        }
        if ($pageUrl == '') {
            $this->logWarning(sprintf(
                'Plausible page url can\'t be blank on recording event at endpoint "%s" for site "%s"',
                $endpoint,
                $plausibleSiteId
            ));
            return false;
        }
        if ($eventName == '') {
            $this->logWarning(sprintf(
                'Plausible event name can\'t be blank on recording event at endpoint "%s" for site "%s"',
                $endpoint,
                $plausibleSiteId
            ));
            return false;
        }
        if ($customProperties === null)
            $customProperties = [];
        foreach ($customProperties as $key => $prop) {
            if (!is_scalar($prop)) {
                $this->logWarning(sprintf(
                    'Plausible custom properties only accepts scalar values on recording event at endpoint "%s" for site "%s". The key of the faulty data is: "%s"',
                    $endpoint,
                    $plausibleSiteId,
                    $key
                ));
                return false;
            }
        }

        try {
            $postDataJson = json_encode([
                'name' => $eventName,
                'url' => $pageUrl,
                'domain' => $plausibleSiteId,
                'referrer' => $referrer,
                //'screen_width' => ,
                /* the array inside json data for post must be escaped (e.g. " -> \")
                 * otherwise plausible throws an error */
                'props' => json_encode($customProperties),
            ], JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logWarning(sprintf(
                'Can\'t encode options for plausible recording event at endpoint "%s" for site "%s": %s',
                $endpoint,
                $plausibleSiteId,
                $e->getMessage()
            ));
            return false;
        }

        $additionalOptions = [
            'headers' => [
                'User-Agent' => $userAgent,
                'X-Forwarded-For' => $xForwardedFor,
                'Content-Type' => 'application/json',
            ],
            'body' => $postDataJson,
        ];

        $uri = rtrim($apiUrl, '/') . '/' . $endpoint;
        try {
            $response = $this->factory->request($uri, 'POST', $additionalOptions);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $this->logWarning(sprintf(
                'Error on plausible request to recording event at endpoint "%s" for site "%s": %s',
                $endpoint,
                $plausibleSiteId,
                $e->getMessage()
            ));
            return false;
        }

        if ($response->getStatusCode() !== 202) {
            $this->logWarning(sprintf(
                    'Something went wrong while recording plausible event at endpoint "%s" for site "%s": %s',
                    $endpoint,
                    $plausibleSiteId,
                    $response->getReasonPhrase()
                ));
            return false;
        }

        return true;
    }
}
