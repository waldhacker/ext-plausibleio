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

class SourceDataProvider
{
    private PlausibleService $plausibleService;

    public function __construct(PlausibleService $plausibleService)
    {
        $this->plausibleService = $plausibleService;
    }

    public function getAllSourcesData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:source');

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item['source']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'source',
                'label' => $this->getLanguageService()->getLL('barChart.labels.source'),
            ]
        );

        return $responseData;
    }

    public function getMediumData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_medium');

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item['utm_medium']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'utm_medium',
                'label' => $this->getLanguageService()->getLL('barChart.labels.UTMMedium'),
            ]
        );

        return $responseData;
    }

    public function getSourceData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_source');

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item['utm_source']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'utm_source',
                'label' => $this->getLanguageService()->getLL('barChart.labels.UTMSource'),
            ]
        );

        return $responseData;
    }

    public function getCampaignData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_campaign');

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item['utm_campaign']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'utm_campaign',
                'label' => $this->getLanguageService()->getLL('barChart.labels.UTMCampaign'),
            ]
        );

        return $responseData;
    }

    private function getData(string $plausibleSiteId, string $timeFrame, string $property): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property,
            'metrics' => 'visitors',
        ];

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
