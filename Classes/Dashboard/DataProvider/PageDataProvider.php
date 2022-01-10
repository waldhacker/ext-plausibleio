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

    public function getTopPageData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $topPageFilterActivated = $this->plausibleService->isFilterActivated('event:page', $filters);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'event:page', $filters);

        $map = $this->plausibleService->dataCleanUp(['page', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
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

    public function getEntryPageData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $entryPageFilterActivated = $this->plausibleService->isFilterActivated('visit:entry_page', $filters);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:entry_page', $filters);

        $map = $this->plausibleService->dataCleanUp(['entry_page', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
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

    public function getExitPageData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $exitPageFilterActivated = $this->plausibleService->isFilterActivated('visit:exit_page', $filters);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:exit_page', $filters);

        $map = $this->plausibleService->dataCleanUp(['exit_page', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
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

    private function getData(string $plausibleSiteId, string $timeFrame, string $property, array $filter=[]): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property,
            'metrics' => 'visitors',
        ];
        $filterStr = $this->plausibleService->filtersToPlausibleFilterString($filter);
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

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
