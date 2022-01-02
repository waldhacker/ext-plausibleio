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

namespace Waldhacker\Plausibleio\Dashboard\DataProvider;

use TYPO3\CMS\Core\Localization\LanguageService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class DeviceDataProvider
{
    private PlausibleService $plausibleService;

    public function __construct(PlausibleService $plausibleService)
    {
        $this->plausibleService = $plausibleService;
    }

    public function getBrowserData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $map = [];
        $browserFilterActivated = $this->plausibleService->isFilterActivated('visit:browser', $filters);
        $property = $browserFilterActivated ? 'visit:browser_version' : 'visit:browser';
        $dataColumnName = $browserFilterActivated ? 'browser_version' : 'browser';

        // show only browser data or, if browser is filtered, show all versions of the selected (filtered) browser
        $responseData = $this->getData($plausibleSiteId, $timeFrame, $property, $filters);

        // show only browser data or, if browser is filtered, show all versions of the selected (filtered) browser
        if ($browserFilterActivated) {
            array_unshift(
                $responseData['columns'],
                [
                    'name' => $dataColumnName,
                    'label' => $this->getLanguageService()->getLL('barChart.labels.browserVersion'),
                    'filter' => [
                        'name' => $property,
                        'label' => $this->getLanguageService()->getLL('filter.deviceData.browserVersionIs'),
                    ],
                ]
            );
        }
        else {
            array_unshift(
                $responseData['columns'],
                [
                    'name' => $dataColumnName,
                    'label' => $this->getLanguageService()->getLL('barChart.labels.browser'),
                    'filter' => [
                        'name' => $property,
                        'label' => $this->getLanguageService()->getLL('filter.deviceData.browserIs'),
                    ],
                ]
            );
        }

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item[$dataColumnName]) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        return $responseData;
    }

    public function getOSData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $map = [];
        $osFilterActivated = $this->plausibleService->isFilterActivated('visit:os', $filters);
        $property = $osFilterActivated ? 'visit:os_version' : 'visit:os';
        $dataColumnName = $osFilterActivated ? 'os_version' : 'os';
        $filterLabel = $osFilterActivated ? 'filter.deviceData.osVersionIs' : 'filter.deviceData.osIs';
        $columnLabel = $osFilterActivated ? 'barChart.labels.osVersion' : 'barChart.labels.os';

        $responseData = $this->getData($plausibleSiteId, $timeFrame, $property, $filters);

        // show only browser data or, if browser is filtered, show all versions of the selected (filtered) browser
        array_unshift(
            $responseData['columns'],
            [
                'name' => $dataColumnName,
                'label' => $this->getLanguageService()->getLL($columnLabel),
                'filter' => [
                    'name' => $property,
                    'label' => $this->getLanguageService()->getLL($filterLabel),
                ],
            ]
        );

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item[$dataColumnName]) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        return $responseData;
    }

    public function getDeviceData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $map = [];
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:device', $filters);

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item['device']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'device',
                'label' => $this->getLanguageService()->getLL('barChart.labels.screenSize'),
                'filter' => [
                    'name' => 'visit:device',
                    'label' => $this->getLanguageService()->getLL('filter.deviceData.screenSizeIs'),
                ],
            ]
        );

        return $responseData;
    }

    private function getData(string $plausibleSiteId, string $timeFrame, string $property, array $filters = []): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property,
            'metrics' => 'visitors',
        ];
        $filterStr = $this->plausibleService->filtersToPlausibleFilterString($filters);
        if ($filterStr) {
            $params['filters'] = $filterStr;
        }

        $responseData = [];
        $responseData['data'] = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        if (!is_array($responseData['data'])) {
            $responseData['data'] = [];
        }

        $responseData['columns'][] = [
            'name' => 'visitors',
            'label' => $this->getLanguageService()->getLL('barChart.labels.visitors'),
        ];

        return $responseData;
    }

    public function calcPercentage(array $dataArray): array
    {
        $visitorsSum = 0;

        foreach ($dataArray as $item) {
            $visitorsSum = $visitorsSum + $item['visitors'];
        }
        foreach ($dataArray as $key => $value) {
            $dataArray[$key]['percentage'] = $value['visitors'] / $visitorsSum * 100;
        }

        return $dataArray;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
