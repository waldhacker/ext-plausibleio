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
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Dashboard\Controller\AbstractController;
use GuzzleHttp\Exception\ClientException;

class PlausibleService extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private array $permittedFilters = [
        'event:page',
        'visit:entry_page',
        'visit:exit_page',
        'visit:browser',
        'visit:browser_version',
        'visit:device',
        'visit:os',
        'visit:os_version',
        'visit:country',
        'visit:region',
        'visit:city',
        'visit:source',
        'visit:utm_medium',
        'visit:utm_source',
        'visit:utm_campaign',
        'event:goal',
        'event:props',
    ];

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

    private function logWarning(string $text): void
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

    /**
     * Checks for all filters in $filters whether they are permissible
     * see: $this->permittedFilters
     * Note: Empty names in filters are not allowed and will be skipped
     * Note: Empty values of filters are not allowed and will be skipped
     * Note: Each filter type may only occur once. Duplicate filters are removed.
     *
     * @param array $filters
     * @return array All authorised filters
     */
    public function checkFilters(array $filters): array
    {
        $acceptedFilters = [];

        foreach ($filters as $filter) {
            if (array_key_exists('name', $filter) &&
                array_key_exists('value', $filter) &&
                $filter['value'] !== '' &&
                in_array($filter['name'], $this->permittedFilters)) {
                // Each filter type may only occur once
                $alreadyExists = false;
                foreach ($acceptedFilters as $acceptedFilter) {
                    if (strtolower($filter['name']) === strtolower($acceptedFilter['name'])) {
                        $alreadyExists = true;
                    }
                }
                if (!$alreadyExists) {
                    $acceptedFilters[] = $filter;
                }
            }
        }

        return $acceptedFilters;
    }

    /**
     * Note: Empty names in filters are not allowed and will be skipped
     * Note: Empty values of filters are not allowed and will be skipped
     *
     * @param array $filters
     * @return string
     */
    public function filtersToPlausibleFilterString(array $filters): string
    {
        $filterStr = '';

        foreach ($filters as $filter) {
            if (array_key_exists('name', $filter) &&
                array_key_exists('value', $filter) &&
                $filter['name'] !== '' &&
                $filter['value'] !== '') {
                $filterStr = $filterStr . $filter['name'] . '==' . $filter['value'] . ';';
            }
        }
        // remove last ';'
        $filterStr = trim($filterStr, ';');

        return $filterStr;
    }

    /**
     * Note: Empty names in filters are not allowed and will be skipped
     * Note: Empty values of filters are not allowed and will be skipped
     *
     * @param string $name An empty name is not allowed
     * @param array $filters
     * @throws MissingArrayPathException If the filter does not have all the required fields
     * @return array|null
     */
    public function isFilterActivated(string $name, array $filters): ?array
    {
        if ($name !== '') {
            foreach ($filters as $filter) {
                // Programming error has to be sanitized before calling the method -> global exception
                if (!array_key_exists('name', $filter)) {
                    throw new MissingArrayPathException('Invalid filter. Name does not exist.', 9505003);
                }
                if (!array_key_exists('value', $filter)) {
                    throw new MissingArrayPathException('Invalid filter. Value does not exist.', 9505004);
                }

                if ($filter['name'] !== '' &&
                    $filter['value'] !== '' &&
                    strtolower($filter['name']) == strtolower($name)) {
                    return $filter;
                }
            }
        }

        return null;
    }

    /**
     * Removes all Filters from $toRemove from $filters
     *
     * @param array $toRemove Array of strings (filter names) to remove from $filters
     * @param array $filters
     * @throws MissingArrayPathException If the filter does not have all the required fields
     * @return array Array of filters without the filters in $toRemove
     */
    public function removeFilter(array $toRemove, array $filters): array
    {
        $result = [];
        $filterToRemove = array_pop($toRemove);

        if ($filterToRemove == null) {
            return $filters;
        }

        // Remove one filter per recursive pass
        $filters = $this->removeFilter($toRemove, $filters);

        foreach ($filters as $filter) {
            if (!array_key_exists('name', $filter)) {
                // Programming error has to be sanitized before calling the method -> global exception
                throw new MissingArrayPathException('Invalid filter. Name does not exist.', 9505002);
            }
            if (strtolower($filter['name']) !== strtolower($filterToRemove)) {
                $result[] = $filter;
            }
        }

        return $result;
    }

    /**
     * @param string $name
     * @param array $filters
     * @throws MissingArrayPathException If the filter does not have all the required fields
     * @return string
     */
    function getFilterValue(string $name, array $filters): string
    {
        foreach ($filters as $filter) {
            // Programming error has to be sanitized before calling the method -> global exception
            if (!array_key_exists('name', $filter)) {
                throw new MissingArrayPathException('Invalid filter. Name does not exist.', 9505001);
            }
            if (strtolower($filter['name']) == strtolower($name)) {
                return $filter['value'] ?? '';
            }
        }

        return '';
    }

    /**
     * Checks whether all $mandatoryFields in the subarrays of $dataArray are
     * present. The mandatory field must be set but can be empty if $strict is false.
     * If this is not the case, the subarray is not included in the return value.
     *
     * @param array $mandatoryFields Array of strings. e.g. ['name', 'location']
     * @param array $dataArray Array of arrays. e.g. [['name' => 'berlin', 'location' => 'de'], ['name' => 'rome', 'location' => 'it'], ['name' => 'paris']]
     *                         The result of this example will be: [['name' => 'berlin', 'location' => 'de'], ['name' => 'rome', 'location' => 'it']]
     * @param bool $strict If $strict is true the mandatory fields must be set and
     *                     must not be empty.
     * @return array
     */
    public function dataCleanUp(array $mandatoryFields, array $dataArray, bool $strict=false): array
    {
        $result = [];

        foreach ($dataArray as $item) {
            $takeOver = true;

            foreach ($mandatoryFields as $mf) {
                if (!isset($item[$mf]) || ($strict && empty($item[$mf]))) {
                    $takeOver = false;
                    break;
                }
            }

            if ($takeOver) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function calcPercentage(array $dataArray): array
    {
        $visitorsSum = 0;

        foreach ($dataArray as $item) {
            $visitorsSum = $visitorsSum + $item['visitors'];
        }
        foreach ($dataArray as $key => $value) {
            $dataArray[$key]['percentage'] = ($value['visitors'] / $visitorsSum) * 100;
        }

        return $dataArray;
    }

    /**
     * @param string $plausibleSiteId
     * @param string $timeFrame
     * @param array $dataArray array of arrays
     * @return array
     */
    public function calcConversionRate(string $plausibleSiteId, string $timeFrame, array $dataArray): array
    {
        $endpoint = '/api/v1/stats/aggregate?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors',
        ];

        $totalVisitor = 1;
        $responseData = $this->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        if (
            is_array($responseData)
            && isset($responseData['visitors']['value'])
        ) {
            $totalVisitor = $responseData['visitors']['value'];
        }

        foreach ($dataArray as $id => $item) {
            $cr = ($item['visitors'] / $totalVisitor) * 100;
            $precision = 0;
            if ($cr < 1) {
                $precision = 1;
            } elseif ($cr < 0.1) {
                $precision = 2;
            }
            $dataArray[$id]['cr'] = round($cr, $precision);
            $dataArray[$id]['cr'] .= '%';
        }

        return $dataArray;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getCurrentDashboardId(): string {
        // method comes from implemented AbstractController
        return $this->loadCurrentDashboard();
    }
}
