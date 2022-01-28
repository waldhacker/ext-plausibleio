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

class PageDataProvider extends AbstractDataProvider
{
    public function getTopPageDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $topPageDataWithGoal = $this->getTopPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL);
        $topPageDataWithoutGoal = $this->getTopPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($topPageDataWithoutGoal['columns'][0]['name'], $topPageDataWithoutGoal['data'], $topPageDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (browser, version) does not have to be determined.
            $topPageDataWithoutGoal['columns'][0],
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
    public function getTopPageDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $topPageFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTPAGE);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'event:page', $filters);

        $map = $this->dataCleanUp(['page', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;
        $responseData['columns'] = [
            [
                'name' => 'page',
                'label' => $this->getLanguageService()->getLL('barChart.labels.pageUrl'),
            ],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.visitors'),
            ],
        ];
        // When filtering by top page there is no deeper filter than that
        if (!$topPageFilterActivated) {
            $responseData['columns'][0]['filter'] = [
                'name' => 'event:page',
                'label' => $this->getLanguageService()->getLL('filter.pageData.pageIs'),
            ];
        }

        return $responseData;
    }

    public function getTopPageData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL);

        if (!$goalFilterActivated) {
            return $this->getTopPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getTopPageDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getEntryPageDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $entryPageDataWithGoal = $this->getEntryPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL);
        $entryPageDataWithoutGoal = $this->getEntryPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($entryPageDataWithoutGoal['columns'][0]['name'], $entryPageDataWithoutGoal['data'], $entryPageDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (browser, version) does not have to be determined.
            $entryPageDataWithoutGoal['columns'][0],
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

    public function getEntryPageDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $entryPageFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITENTRYPAGE);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:entry_page', $filters);

        $map = $this->dataCleanUp(['entry_page', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;
        $responseData['columns'] = [
            [
                'name' => 'entry_page',
                'label' => $this->getLanguageService()->getLL('barChart.labels.pageUrl'),
            ],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.uniqueEntrances'),
            ],
        ];
        // When filtering by entry page there is no deeper filter than that
        if (!$entryPageFilterActivated) {
            $responseData['columns'][0]['filter'] = [
                'name' => 'visit:entry_page',
                'label' => $this->getLanguageService()->getLL('filter.pageData.entryPageIs'),
            ];
        }

        return $responseData;
    }

    public function getEntryPageData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL);

        if (!$goalFilterActivated) {
            return $this->getEntryPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getEntryPageDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getExitPageDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $exitPageDataWithGoal = $this->getExitPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL);
        $exitPageDataWithoutGoal = $this->getExitPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($exitPageDataWithoutGoal['columns'][0]['name'], $exitPageDataWithoutGoal['data'], $exitPageDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (browser, version) does not have to be determined.
            $exitPageDataWithoutGoal['columns'][0],
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

    public function getExitPageDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $exitPageFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITEXITPAGE);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:exit_page', $filters);

        $map = $this->dataCleanUp(['exit_page', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;
        $responseData['columns'] = [
            [
                'name' => 'exit_page',
                'label' => $this->getLanguageService()->getLL('barChart.labels.pageUrl'),
            ],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.uniqueExits'),
            ],
        ];
        // When filtering by exit page there is no deeper filter than that
        if (!$exitPageFilterActivated) {
            $responseData['columns'][0]['filter'] = [
                'name' => 'visit:exit_page',
                'label' => $this->getLanguageService()->getLL('filter.pageData.exitPageIs'),
            ];
        }

        return $responseData;
    }

    public function getExitPageData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL);

        if (!$goalFilterActivated) {
            return $this->getExitPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getExitPageDataWithGoal($plausibleSiteId, $timeFrame, $filters);
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

        return $responseData;
    }
}
