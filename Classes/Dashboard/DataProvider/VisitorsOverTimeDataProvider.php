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

class VisitorsOverTimeDataProvider
{
    private const EXT_KEY = 'plausibleio';
    private PlausibleService $plausibleService;

    public function __construct(PlausibleService $plausibleService)
    {
        $this->plausibleService = $plausibleService;
        $this->getLanguageService()->includeLLFile('EXT:' . self::EXT_KEY . '/Resources/Private/Language/locallang.xlf');
    }

    public function getOverview(string $plausibleSiteId, string $timeFrame, array $filter = []): array
    {
        $endpoint = '/api/v1/stats/aggregate?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors,visit_duration,pageviews,bounce_rate',
        ];
        $filterStr = $this->plausibleService->filtersToPlausibleFilterString($filter);
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
            $result = [
                'bounce_rate' => $responseData['bounce_rate']['value'],
                'pageviews' => $responseData['pageviews']['value'],
                'visit_duration' => $responseData['visit_duration']['value'],
                'visitors' => $responseData['visitors']['value'],
            ];
        }

        return $result;
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

    public function getChartData(string $plausibleSiteId, string $timeFrame, array $filter = []): array
    {
        $results = $this->getVisitors($plausibleSiteId, $timeFrame, $filter);

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
                    'label' => $this->getLanguageService()->getLL('visitors'),
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
