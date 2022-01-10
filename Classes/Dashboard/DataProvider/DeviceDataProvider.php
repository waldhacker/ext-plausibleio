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
        $browserFilterActivated = $this->plausibleService->isFilterActivated('visit:browser', $filters);
        $browserVersionFilterActivated = $this->plausibleService->isFilterActivated('visit:browser_version', $filters);
        $property = $browserFilterActivated ? 'visit:browser_version' : 'visit:browser';
        $dataColumnName = $browserFilterActivated ? 'browser_version' : 'browser';
        $filterLabel = $browserFilterActivated ? 'filter.deviceData.browserVersionIs' : 'filter.deviceData.browserIs';
        $columnLabel = $browserFilterActivated ? 'barChart.labels.browserVersion' : 'barChart.labels.browser';

        // show only browser data or, if browser is filtered, show all versions of the selected (filtered) browser
        $responseData = $this->getData($plausibleSiteId, $timeFrame, $property, $filters);

        // Show only browser data or, if browser is filtered, show all versions of the selected (filtered) browser
        $browserColumn = [
            'name' => $dataColumnName,
            'label' => $this->getLanguageService()->getLL($columnLabel),
        ];
        // When filtering by browser version there is no deeper filter than that
        if (!$browserVersionFilterActivated) {
            $browserColumn['filter'] = [
                'name' => $property,
                'label' => $this->getLanguageService()->getLL($filterLabel),
            ];
        }
        array_unshift($responseData['columns'], $browserColumn);

        $map = $this->plausibleService->dataCleanUp([$dataColumnName, 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        return $responseData;
    }

    public function getOSData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $osFilterActivated = $this->plausibleService->isFilterActivated('visit:os', $filters);
        $osVersionFilterActivated = $this->plausibleService->isFilterActivated('visit:os_version', $filters);
        $property = $osFilterActivated ? 'visit:os_version' : 'visit:os';
        $dataColumnName = $osFilterActivated ? 'os_version' : 'os';
        $filterLabel = $osFilterActivated ? 'filter.deviceData.osVersionIs' : 'filter.deviceData.osIs';
        $columnLabel = $osFilterActivated ? 'barChart.labels.osVersion' : 'barChart.labels.os';

        $responseData = $this->getData($plausibleSiteId, $timeFrame, $property, $filters);

        // Show only os data or, if os is filtered, show all versions of the selected (filtered) browser
        $osColumn = [
            'name' => $dataColumnName,
            'label' => $this->getLanguageService()->getLL($columnLabel),

        ];
        // When filtering by os version there is no deeper filter than that
        if (!$osVersionFilterActivated) {
            $osColumn['filter'] = [
                'name' => $property,
                'label' => $this->getLanguageService()->getLL($filterLabel),
            ];
        }
        array_unshift($responseData['columns'], $osColumn);

        $map = $this->plausibleService->dataCleanUp([$dataColumnName, 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        return $responseData;
    }

    public function getDeviceData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $deviceFilterActivated = $this->plausibleService->isFilterActivated('visit:device', $filters);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:device', $filters);

        $map = $this->plausibleService->dataCleanUp(['device', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        $deviceColumn = [
            'name' => 'device',
            'label' => $this->getLanguageService()->getLL('barChart.labels.screenSize'),
        ];
        // there is no deeper filter than Device
        if (!$deviceFilterActivated) {
            $deviceColumn['filter'] = [
                'name' => 'visit:device',
                'label' => $this->getLanguageService()->getLL('filter.deviceData.screenSizeIs'),
            ];
        }
        array_unshift($responseData['columns'], $deviceColumn);

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

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
