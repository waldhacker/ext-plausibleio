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
use Waldhacker\Plausibleio\Services\ISO3166Service;
use Waldhacker\Plausibleio\Services\ISO3166_2_Service;
use Waldhacker\Plausibleio\Services\LocationCodeService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class CountryMapDataProvider extends AbstractDataProvider
{
    private ISO3166Service $ISO3166Service;
    private ISO3166_2_Service $ISO3166_2_Service;
    private LocationCodeService $locationCodeService;

    public function __construct(
        PlausibleService $plausibleService,
        ISO3166Service $ISO3166Service,
        ISO3166_2_Service $ISO3166_2_Service,
        LocationCodeService $locationCodeService
    ) {
        parent::__construct($plausibleService);
        $this->ISO3166Service = $ISO3166Service;
        $this->ISO3166_2_Service = $ISO3166_2_Service;
        $this->locationCodeService = $locationCodeService;
    }

    public function getCountryDataForDataMapWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $countryDataWithGoal = $this->getCountryDataForDataMapWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);
        $countryDataWithoutGoal = $this->getCountryDataForDataMapWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($countryDataWithoutGoal['columns'][0]['name'], $countryDataWithoutGoal['data'], $countryDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (browser, version) does not have to be determined.
            $countryDataWithoutGoal['columns'][0],
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

    public function getCountryDataForDataMapWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $countryFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITCOUNTRY);
        $regionFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITREGION);

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
        $data['data'] = $this->calcPercentage($data['data']);

        return $data;
    }

    /**
     * @param string $plausibleSiteId
     * @param string $timeFrame
     * @param FilterRepository $filters
     * @return array Returns a list of countries without a filter. If filtered by country, returns a list of the associated
     *               regions. If filtered by region, returns a list of the associated cities.
     */
    public function getCountryDataForDataMap(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        // At the moment, setFiltersFromArray removes a standalone custom property
        // filter because they are not supported by Plausible. See for this
        // FilterRepository->setFiltersFromArray
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);

        if (!$goalFilterActivated) {
            return $this->getCountryDataForDataMapWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getCountryDataForDataMapWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    /**
     * Only the country data, filtered but not subdivided in regions or cities.
     *
     * @param string $plausibleSiteId
     * @param string $timeFrame
     * @param FilterRepository $filters
     * @return array Always returns a list of countries, even when filtered by country or region.
     */
    public function getCountryDataOnlyForDataMap(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:country',
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

        $map = $this->dataCleanUp(['country', 'visitors'], $responseData['data'], true);
        $map = $this->calcPercentage($map);
        $map = $this->plausibleCountriesToDataMap($map);
        /* plausibleCountriesToDataMap needs more data (e.g. valid ISO code) than getCountryData
         * and may therefore remove data sets. For this reason, the percentage proportions
         * must be calculated again.
         */
        $map = $this->calcPercentage($map);
        $resultData['data'] = $map;
        $resultData['columns'] = [
            [
                'name' => ISO3166Service::COUNTRYNAME,
                'label' => $this->getLanguageService()->getLL('barChart.labels.country'),
                'filter' => [
                    'name' => 'visit:country',
                    'value' => ISO3166Service::ALPHA2,
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

    private function getCountryData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $countryFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITCOUNTRY);
        $regionFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITREGION);
        $cityFilterActivated= $filters->isFilterActivated(FilterRepository::FILTERVISITCITY);
        $property = $countryFilterActivated ? 'visit:region' : 'visit:country';
        $property = $regionFilterActivated ? 'visit:city' : $property;
        $dataColumnName = $countryFilterActivated ? ISO3166_2_Service::REGIONNAME : ISO3166Service::COUNTRYNAME;
        $dataColumnName = $regionFilterActivated ? LocationCodeService::CITYNAME : $dataColumnName;
        $plausibleDataName = $countryFilterActivated ? 'region' : 'country';
        $plausibleDataName = $regionFilterActivated ? 'city' : $plausibleDataName;
        $filterValueName = $countryFilterActivated ? ISO3166_2_Service::ISOCODE : ISO3166Service::ALPHA2;
        $filterValueName = $regionFilterActivated ? LocationCodeService::LOCATIONIDNAME : $filterValueName;
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
        $filterStr = $filters->toPlausibleFilterString();
        if ($filterStr) {
            $params['filters'] = $filterStr;
        }

        $responseData = [];
        $responseData['data'] = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        if (!is_array($responseData['data'])) {
            $responseData['data'] = [];
        }

        $map = $this->dataCleanUp([$plausibleDataName, 'visitors'], $responseData['data'], true);
        $map = $this->calcPercentage($map);
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
        // When filtering by country, region and city or region and city there is no deeper filter than that
        // because then it is filtered for a specific city and this city is also displayed
        if (!($countryFilterActivated && $regionFilterActivated && $cityFilterActivated) &&
            !($regionFilterActivated && $cityFilterActivated)) {
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
            $iso3166Data = $this->ISO3166Service->alpha2($item['country']);
            if ($iso3166Data === null) {
                continue;
            }

            $result[] = [
                ISO3166Service::ALPHA2 => $iso3166Data[ISO3166Service::ALPHA2],
                ISO3166Service::ALPHA3 => $iso3166Data[ISO3166Service::ALPHA3],
                ISO3166Service::COUNTRYNAME => $iso3166Data[ISO3166Service::COUNTRYNAME],
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
                ISO3166_2_Service::REGIONNAME => $iso3166_2_Data[ISO3166_2_Service::REGIONNAME],
                ISO3166_2_Service::ISOCODE => $iso3166_2_Data[ISO3166_2_Service::ISOCODE],
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
                LocationCodeService::CITYNAME => $cityData[LocationCodeService::CITYNAME],
                LocationCodeService::LOCATIONIDNAME => $cityData[LocationCodeService::LOCATIONIDNAME],
                'visitors' => $item['visitors'],
                'percentage' => $item['percentage'],
            ];
        }

        return $result;
    }
}
