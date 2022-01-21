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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class VisitorsOverTimeDataProvider
{
    private const EXT_KEY = 'plausibleio';
    private PlausibleService $plausibleService;
    private GoalDataProvider $goalDataProvider;

    public function __construct(GoalDataProvider $goalDataProvider, PlausibleService $plausibleService)
    {
        $this->goalDataProvider = $goalDataProvider;
        $this->plausibleService = $plausibleService;
        $this->getLanguageService()->includeLLFile('EXT:' . self::EXT_KEY . '/Resources/Private/Language/locallang.xlf');
    }

    public function getOverviewWithGoal(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $filtersWithoutGoal = $this->plausibleService->removeFilter(['event:goal'], $filters);

        $dataWithoutGoal = $this->getOverviewWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);
        $dataWithoutGoal = $dataWithoutGoal['data'] ?? [];

        $resultData = [];
        $resultData['visitors'] = $dataWithoutGoal['visitors'] ?? 0;

        $goalConversionData = $this->goalDataProvider->getGoalsData($plausibleSiteId, $timeFrame, $filters);
        try {
            $goalConversionData = ArrayUtility::getValueByPath($goalConversionData, 'data/0');
        } catch (\RuntimeException $e) {
            $goalConversionData = [];
        }

        $resultData['uniques_conversions'] = $goalConversionData['visitors'] ?? 0;
        $resultData['total_conversions'] = $goalConversionData['events'] ?? 0;
        $resultData['cr'] = $goalConversionData['cr'] ?? 0;

        $result = [
            'columns' => [
                [
                    'name' => 'visitors',
                    'label' => $this->getLanguageService()->getLL('widget.visitorsOverTime.overview.uniqueVisitors'),
                ],
                [
                    'name' => 'uniques_conversions',
                    'label' => $this->getLanguageService()->getLL('barChart.labels.uniqueConversions'),
                ],
                [
                    'name' => 'total_conversions',
                    'label' => $this->getLanguageService()->getLL('barChart.labels.totalConversions'),
                ],
                [
                    'name' => 'cr',
                    'label' => $this->getLanguageService()->getLL('barChart.labels.conversionRate'),
                ],
            ],
            'data' => $resultData,
        ];

        return $result;
    }

    public function getOverviewWithoutGoal(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $endpoint = '/api/v1/stats/aggregate?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors,visit_duration,pageviews,bounce_rate',
        ];
        $filterStr = $this->plausibleService->filtersToPlausibleFilterString($filters);
        if ($filterStr) {
            $params['filters'] = $filterStr;
        }

        $result = [];
        $responseData = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        if (
            is_array($responseData)
            && isset($responseData['bounce_rate']['value'])
            && isset($responseData['pageviews']['value'])
            && isset($responseData['visit_duration']['value'])
            && isset($responseData['visitors']['value'])
        ) {
            // convert seconds of visit_duration to readable format
            $minutes = 0;
            $seconds = 0;
            $visitDuration = $responseData['visit_duration']['value'];
            if ($visitDuration) {
                // full minutes
                $minutes = floor($visitDuration / 60);
                // remaining seconds
                $seconds = $visitDuration - ($minutes * 60);
            }
            if ($minutes + $seconds > 0) {
                $responseData['visit_duration']['value'] = ($minutes > 0 ? $minutes . 'm ' : '') . ($seconds > 0 ? $seconds . 's' : '');
            } else {
                $responseData['visit_duration']['value'] = '-';
            }

            $result = [
                'columns' => [
                    [
                        'name' => 'visitors',
                        'label' => $this->getLanguageService()->getLL('widget.visitorsOverTime.overview.uniqueVisitors'),
                    ],
                    [
                        'name' => 'pageviews',
                        'label' => $this->getLanguageService()->getLL('widget.visitorsOverTime.overview.totalPageviews'),
                    ],
                    [
                        'name' => 'visit_duration',
                        'label' => $this->getLanguageService()->getLL('widget.visitorsOverTime.overview.visitDuration'),
                    ],
                    [
                        'name' => 'current_visitors',
                        'label' => $this->getLanguageService()->getLL('widget.visitorsOverTime.overview.currentVisitors'),
                    ],
                ],
                'data' => [
                    'bounce_rate' => $responseData['bounce_rate']['value'],
                    'pageviews' => $responseData['pageviews']['value'],
                    'visit_duration' => $responseData['visit_duration']['value'],
                    'visitors' => $responseData['visitors']['value'],
                    'current_visitors' => $this->getCurrentVisitors($plausibleSiteId, $filters),
                ],
            ];
        }

        return $result;
    }

    public function getOverview(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $goalFilterActivated = $this->plausibleService->isFilterActivated('event:goal', $filters);

        if (!$goalFilterActivated) {
            return $this->getOverviewWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getOverviewWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getCurrentVisitors(string $plausibleSiteId, array $filter = []): int
    {
        $endpoint = '/api/v1/stats/realtime/visitors?';
        $params = [
            'site_id' => $plausibleSiteId,
        ];
        $filterStr = $this->plausibleService->filtersToPlausibleFilterString($filter);
        if ($filterStr) {
            $params['filters'] = $filterStr;
        }

        $responseData =  $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);

        return is_int($responseData) ? $responseData : 0;
    }

    public function getChartData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $goalFilterActivated = $this->plausibleService->isFilterActivated('event:goal', $filters);
        $chartLabel = $goalFilterActivated ? $this->getLanguageService()->getLL('barChart.labels.convertedVisitors') : $this->getLanguageService()->getLL('visitors');

        $results = $this->getVisitors($plausibleSiteId, $timeFrame, $filters);

        $labels = [];
        $data = [];
        foreach ($results as $item) {
            if (!isset($item['date']) || !isset($item['visitors'])) {
                continue;
            }

            $labels[] = $item['date'];
            $data[] = $item['visitors'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $chartLabel,
                    'data' => $data,
                    'fill' => false,
                    'borderColor' => '#85bcee',
                    'tension' => 0.5,
                ],
            ],
        ];
    }

    private function getVisitors(string $plausibleSiteId, string $timeFrame, array $filter = []): array
    {
        $endpoint = 'api/v1/stats/timeseries?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
        ];
        $filterStr = $this->plausibleService->filtersToPlausibleFilterString($filter);
        if ($filterStr) {
            $params['filters'] = $filterStr;
        }

        $responseData = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        return is_array($responseData) ? $responseData : [];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
