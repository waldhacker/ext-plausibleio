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

class PageDataProvider
{
    private PlausibleService $plausibleService;

    public function __construct(PlausibleService $plausibleService)
    {
        $this->plausibleService = $plausibleService;
    }

    public function getTopPageData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'event:page');

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item['page']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

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

        return $responseData;
    }

    public function getEntryPageData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:entry_page');

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item['entry_page']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

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

        return $responseData;
    }

    public function getExitPageData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:exit_page');

        // clean up data
        foreach ($responseData['data'] as $item) {
            if (!isset($item['exit_page']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = $item;
        }

        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;
        $responseData['columns'] =  [
            [
                'name' => 'exit_page',
                'label' => $this->getLanguageService()->getLL('barChart.labels.pageUrl'),
            ],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.uniqueExits'),
            ],
        ];

        return $responseData;
    }

    private function getData(string $plausibleSiteId, string $timeFrame, string $property): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property
        ];

        $responseData = [];
        $responseData['data'] = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        if (!is_array($responseData['data'])) {
            $responseData['data'] = [];
        }

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
