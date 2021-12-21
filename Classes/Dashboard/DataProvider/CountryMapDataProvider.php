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
use Waldhacker\Plausibleio\Services\ISO3166Service;
use Waldhacker\Plausibleio\Services\PlausibleService;

class CountryMapDataProvider
{
    private PlausibleService $plausibleService;
    private ISO3166Service $ISO3166Service;

    public function __construct(
        PlausibleService $plausibleService,
        ISO3166Service $ISO3166Service
    ) {
        $this->plausibleService = $plausibleService;
        $this->ISO3166Service = $ISO3166Service;
    }

    public function getCountryDataForDataMap(string $plausibleSiteId, string $timeFrame, array $filter = []): array
    {
        $data = $this->getCountryData($plausibleSiteId, $timeFrame, $filter);
        $data['data'] = $this->plausibleToDataMap($data['data']);
        /* plausibleToDataMap needs more data (e.g. valid ISO code) than getCountryData
         * and may therefore remove data sets. For this reason, the percentage proportions
         * must be calculated again.
         */
        $data['data'] = $this->calcPercentage($data['data']);
        return $data;
    }

    private function getCountryData(string $plausibleSiteId, string $timeFrame, array $filter = []): array
    {
        $countryFilterActivated = $this->plausibleService->isFilterActivated('visit:country', $filter);
        $property = 'visit:country';
        $dataColumnName = 'country';
        /*$property = $countryFilterActivated ? 'visit:region' : 'visit:country';
        $dataColumnName = $countryFilterActivated ? 'region' : 'countryname';
        $filterLabel = $countryFilterActivated ? 'filter.deviceData.osVersionIs' : 'filter.deviceData.osIs';
        $columnLabel = $countryFilterActivated ? 'barChart.labels.osVersion' : 'barChart.labels.os';
        */

        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property,
        ];
        $filterStr = $this->plausibleService->filtersToPlausibleFilterString($filter);
        if ($filterStr) {
            $params['filters'] = $filterStr;
        }

        $map = [];
        $responseData = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        // clean up data
        foreach ($responseData as $item) {
            if (!isset($item[$dataColumnName]) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $resultData['data'] = $map;
        $resultData['columns'] = [
            [
                'name' => $dataColumnName,
                'label' => $this->getLanguageService()->getLL('barChart.labels.country'),
                'filter' => [
                    'name' => 'visit:country',
                    'value' => 'alpha2',
                    'label' => $this->getLanguageService()->getLL('filter.deviceData.countryIs'),
                ],
            ],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.visitors'),
            ],
        ];

        return $resultData;
    }

    private function plausibleToDataMap(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            if (
                !is_array($item)
                || !isset($item['country'])
                || !isset($item['visitors'])
            ) {
                continue;
            }
            $iso3166Data = $this->ISO3166Service->alpha2($item['country']);
            if ($iso3166Data === null) {
                continue;
            }

            $result[] = [
                'alpha2' => $iso3166Data[ISO3166Service::ALPHA2],
                'alpha3' => $iso3166Data[ISO3166Service::ALPHA3],
                'country' => $iso3166Data[ISO3166Service::COUNTRYNAME],
                'visitors' => $item['visitors'],
                'percentage' => $item['percentage'],
            ];
        }

        return $result;
    }

    public function calcPercentage(array $dataArray): array
    {
        $visitorsSum = 0;

        foreach ($dataArray as $item) {
            if (array_key_exists('visitors', $item)) {
                $visitorsSum = $visitorsSum + $item['visitors'];
            }
        }
        foreach ($dataArray as $key => $value) {
            if (array_key_exists('visitors', $value)) {
                $dataArray[$key]['percentage'] = $value['visitors'] / $visitorsSum * 100;
            }
        }

        return $dataArray;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
