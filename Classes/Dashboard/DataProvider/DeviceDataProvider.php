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

use Waldhacker\Plausibleio\FilterRepository;

class DeviceDataProvider extends AbstractDataProvider
{
    public function getBrowserDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $browserDataWithGoal = $this->getBrowserDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL);
        $browserDataWithoutGoal = $this->getBrowserDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($browserDataWithoutGoal['columns'][0]['name'], $browserDataWithoutGoal['data'], $browserDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (browser, version) does not have to be determined.
            $browserDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    /**
     * Note: The goal filter must already have been removed from $filters before the call.
     *       Within the method there is no check whether the goal filter is activated. The
     *       real difference to getBrowserDataWithGoal are the returned columns and their
     *       corresponding data.
     *
     * @param string $plausibleSiteId
     * @param string $timeFrame
     * @param array $filters
     * @return array
     */
    public function getBrowserDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $browserFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITBROWSER);
        $browserVersionFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITBROWSERVERSION);
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
        // When filtering by browser and browser version there is no deeper filter than that
        if (!$browserFilterActivated || !$browserVersionFilterActivated) {
            $browserColumn['filter'] = [
                'name' => $property,
                'label' => $this->getLanguageService()->getLL($filterLabel),
            ];
        }
        array_unshift($responseData['columns'], $browserColumn);

        $map = $this->dataCleanUp([$dataColumnName, 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        return $responseData;
    }

    public function getBrowserData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL);

        if (!$goalFilterActivated) {
            return $this->getBrowserDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getBrowserDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getOSDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $osDataWithGoal = $this->getOSDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL);
        $osDataWithoutGoal = $this->getOSDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($osDataWithoutGoal['columns'][0]['name'], $osDataWithoutGoal['data'], $osDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (os, version) does not have to be determined.
            $osDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    public function getOSDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $osFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITOS);
        $osVersionFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITOSVERSION);
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
        // When filtering by os and os version there is no deeper filter than that
        if (!$osFilterActivated || !$osVersionFilterActivated) {
            $osColumn['filter'] = [
                'name' => $property,
                'label' => $this->getLanguageService()->getLL($filterLabel),
            ];
        }
        array_unshift($responseData['columns'], $osColumn);

        $map = $this->dataCleanUp([$dataColumnName, 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        return $responseData;
    }

    public function getOSData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL);

        if (!$goalFilterActivated) {
            return $this->getOSDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getOSDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getDeviceDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $deviceDataWithGoal = $this->getDeviceDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL);
        $deviceDataWithoutGoal = $this->getDeviceDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($deviceDataWithoutGoal['columns'][0]['name'], $deviceDataWithoutGoal['data'], $deviceDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (Desktop) does not have to be determined.
            $deviceDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    public function getDeviceDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $deviceFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITDEVICE);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:device', $filters);

        $map = $this->dataCleanUp(['device', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
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

    public function getDeviceData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL);

        if (!$goalFilterActivated) {
            return $this->getDeviceDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getDeviceDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    private function getData(string $plausibleSiteId, string $timeFrame, string $property, FilterRepository $filters): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property,
            'metrics' => 'visitors',
        ];
        $filterStr = $filters->toPlausibleFilterString();
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
}
