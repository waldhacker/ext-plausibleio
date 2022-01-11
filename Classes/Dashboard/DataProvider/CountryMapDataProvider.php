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
use Waldhacker\Plausibleio\Services\ISO3166_2_Service;
use Waldhacker\Plausibleio\Services\LocationCodeService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class CountryMapDataProvider
{
    private PlausibleService $plausibleService;
    private ISO3166Service $ISO3166Service;
    private ISO3166_2_Service $ISO3166_2_Service;
    private LocationCodeService $locationCodeService;

    public function __construct(
        PlausibleService $plausibleService,
        ISO3166Service $ISO3166Service,
        ISO3166_2_Service $ISO3166_2_Service,
        LocationCodeService $locationCodeService
    ) {
        $this->plausibleService = $plausibleService;
        $this->ISO3166Service = $ISO3166Service;
        $this->ISO3166_2_Service = $ISO3166_2_Service;
        $this->locationCodeService = $locationCodeService;
    }

    public function getCountryDataForDataMap(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $countryFilterActivated = $this->plausibleService->isFilterActivated('visit:country', $filters);
        $regionFilterActivated = $this->plausibleService->isFilterActivated('visit:region', $filters);

        $data = $this->getCountryData($plausibleSiteId, $timeFrame, $filters);

        if (!$countryFilterActivated && !$regionFilterActivated) {
            $data['data'] = $this->plausibleCountriesToDataMap($data['data']);
        }
        if ($countryFilterActivated && !$regionFilterActivated) {
            $data['data'] = $this->plausibleRegionsToDataMap($data['data']);
        }
        if ($regionFilterActivated) {
            $data['data'] = $this->plausibleCitiesToDataMap($data['data']);
        }

        /* plausible***ToDataMap needs more data (e.g. valid ISO code) than getCountryData
         * and may therefore remove data sets. For this reason, the percentage proportions
         * must be calculated again.
         */
        $data['data'] = $this->plausibleService->calcPercentage($data['data']);

        return $data;
    }

    /**
     * Only the country data, filtered but not with regions or cities.
     * @param string $plausibleSiteId
     * @param string $timeFrame
     * @param array $filters
     * @return array
     */
    public function getCountryDataOnlyForDataMap(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:country',
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

        $map = $this->plausibleService->dataCleanUp(['country', 'visitors'], $responseData['data'], true);
        $map = $this->plausibleService->calcPercentage($map);
        $map = $this->plausibleCountriesToDataMap($map);
        /* plausibleCountriesToDataMap needs more data (e.g. valid ISO code) than getCountryData
         * and may therefore remove data sets. For this reason, the percentage proportions
         * must be calculated again.
         */
        $map = $this->plausibleService->calcPercentage($map);
        $resultData['data'] = $map;
        $resultData['columns'] = [
            [
                'name' => 'country',
                'label' => $this->getLanguageService()->getLL('barChart.labels.country'),
                'filter' => [
                    'name' => 'visit:country',
                    'value' => 'alpha2',
                    'label' => $this->getLanguageService()->getLL('filter.locationData.countryIs'),
                ],
            ],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.visitors'),
            ],
        ];

        return $resultData;
    }

    private function getCountryData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $countryFilterActivated = $this->plausibleService->isFilterActivated('visit:country', $filters);
        $regionFilterActivated = $this->plausibleService->isFilterActivated('visit:region', $filters);
        $cityFilterActivated = $this->plausibleService->isFilterActivated('visit:city', $filters);
        $property = $countryFilterActivated ? 'visit:region' : 'visit:country';
        $property = $regionFilterActivated ? 'visit:city' : $property;
        $dataColumnName = $countryFilterActivated ? 'region' : 'country';
        $dataColumnName = $regionFilterActivated ? 'city' : $dataColumnName;
        $filterValueName = $countryFilterActivated ? 'isoCode' : 'alpha2';
        $filterValueName = $regionFilterActivated ? 'locationId' : $filterValueName;
        $filterLabel = $countryFilterActivated ? 'filter.locationData.regionIs' : 'filter.locationData.countryIs';
        $filterLabel = $regionFilterActivated ? 'filter.locationData.cityIs' : $filterLabel;
        $columnLabel = $countryFilterActivated ? 'barChart.labels.region' : 'barChart.labels.country';
        $columnLabel = $regionFilterActivated ? 'barChart.labels.city' : $columnLabel;


        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property,
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

        $map = $this->plausibleService->dataCleanUp([$dataColumnName, 'visitors'], $responseData['data'], true);
        $map = $this->plausibleService->calcPercentage($map);
        $resultData['data'] = $map;
        $resultData['columns'] = [
            [
                'name' => $dataColumnName,
                'label' => $this->getLanguageService()->getLL($columnLabel),

            ],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.visitors'),
            ],
        ];
        // When filtering by country, region and city there is no deeper filter than that
        if (!$countryFilterActivated || !$regionFilterActivated || !$cityFilterActivated) {
            $resultData['columns'][0]['filter'] = [
                'name' => $property,
                'value' => $filterValueName,
                'label' => $this->getLanguageService()->getLL($filterLabel),
            ];
        }

        return $resultData;
    }

    private function plausibleCountriesToDataMap(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            /*
            if (
                !is_array($item)
                || !isset($item['country'])
                || !isset($item['visitors'])
            ) {
                continue;
            }
            */
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

    private function plausibleRegionsToDataMap(array $data): array
    {
        $result = [];

        foreach ($data as $item) {
            $iso3166_2_Data = $this->ISO3166_2_Service->region($item['region']);
            if ($iso3166_2_Data === null) {
                continue;
            }

            $result[] = [
                'region' => $iso3166_2_Data[ISO3166_2_Service::REGIONNAME],
                'isoCode' => $iso3166_2_Data[ISO3166_2_Service::ISOCODE],
                'visitors' => $item['visitors'],
                'percentage' => $item['percentage'],
            ];
        }

        return $result;
    }

    private function plausibleCitiesToDataMap(array $data): array
    {
        $result = [];

        foreach ($data as $item) {
            $cityData = $this->locationCodeService->codeToCityData($item['city']);
            if ($cityData === null) {
                continue;
            }

            $result[] = [
                'city' => $cityData[LocationCodeService::CITYNAME],
                'locationId' => $cityData[LocationCodeService::LOCATIONIDNAME],
                'visitors' => $item['visitors'],
                'percentage' => $item['percentage'],
            ];
        }

        return $result;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
