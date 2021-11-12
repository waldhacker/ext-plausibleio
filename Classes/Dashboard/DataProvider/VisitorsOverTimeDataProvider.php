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
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class VisitorsOverTimeDataProvider implements ChartDataProviderInterface
{
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;
    private LanguageService $languageService;
    private const EXT_KEY = 'plausibleio';

    public function __construct(
        PlausibleService $plausibleService,
        LanguageService $languageService,
        ConfigurationService $configurationService
    ) {
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
        $this->languageService = $languageService;
        $this->languageService->includeLLFile('EXT:' . self::EXT_KEY . '/Resources/Private/Language/locallang.xlf');
    }

    public function getOverview(?string $timeFrame = null, ?string $site = null): array
    {
        $timeFrame = $timeFrame ?? $this->configurationService->getDefaultTimeFrameValue();
        $site = $site ?? $this->configurationService->getDefaultSite();
        $result = [];

        $endpoint = '/api/v1/stats/aggregate?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
            'metrics' => 'visitors,visit_duration,pageviews,bounce_rate',
        ];

        // api/v1/stats/aggregate returns an object
        $data = $this->plausibleService->sendAuthorizedRequest($endpoint, $params);
        if (
            is_object($data)
            && property_exists($data, 'bounce_rate')
            && property_exists($data->bounce_rate, 'value')
            && property_exists($data, 'pageviews')
            && property_exists($data->pageviews, 'value')
            && property_exists($data, 'visit_duration')
            && property_exists($data->visit_duration, 'value')
            && property_exists($data, 'visitors')
            && property_exists($data->visitors, 'value')
        ) {
            $result = [
                'bounce_rate' => $data->bounce_rate->value,
                'pageviews' => $data->pageviews->value,
                'visit_duration' => $data->visit_duration->value,
                'visitors' => $data->visitors->value,
            ];
        }

        return $result;
    }

    public function getCurrentVisitors(?string $site = null): int
    {
        $site = $site ?? $this->configurationService->getDefaultSite();

        $endpoint = '/api/v1/stats/realtime/visitors?';
        $params = [
            'site_id' => $site,
        ];

        $result =  $this->plausibleService->sendAuthorizedRequest($endpoint, $params);

        return $result==null ? 0 : $result;
    }

    private function getVisitors(string $timeFrame, string $site): array
    {
        $endpoint = 'api/v1/stats/timeseries?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
        ];

        return $this->plausibleService->sendAuthorizedRequest($endpoint, $params);
    }

    public function getChartData(?string $timeFrame = null, ?string $site = null): array
    {
        $timeFrame = $timeFrame ?? $this->configurationService->getDefaultTimeFrameValue();
        $site = $site ?? $this->configurationService->getDefaultSite();

        $results = $this->getVisitors($timeFrame, $site);

        $labels = [];
        $data = [];
        foreach ($results as $datum) {
            $labels[] = $datum->date;
            $data[] = $datum->visitors;
        }
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $this->languageService->getLL('visitors'),
                    'data' => $data,
                    'fill' => false,
                    'borderColor' => '#85bcee',
                    'tension' => 0.5,
                ],
            ],
        ];
    }
}
