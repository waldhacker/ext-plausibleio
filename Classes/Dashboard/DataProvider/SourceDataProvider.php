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

    public function getAllSourcesData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:source', $filters);

        $map = $this->plausibleService->dataCleanUp(['source', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'source',
                'label' => $this->getLanguageService()->getLL('barChart.labels.source'),
                'filter' => [
                    'name' => 'visit:source',
                    'label' => $this->getLanguageService()->getLL('filter.sourceData.sourceIs'),
                ],
            ]
        );

        return $responseData;
    }

    public function getMediumData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_medium', $filters);

        $map = $this->plausibleService->dataCleanUp(['utm_medium', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'utm_medium',
                'label' => $this->getLanguageService()->getLL('barChart.labels.UTMMedium'),
                'filter' => [
                    'name' => 'visit:utm_medium',
                    'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMMediumIs'),
                ],
            ]
        );

        return $responseData;
    }

    public function getSourceData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_source', $filters);

        $map = $this->plausibleService->dataCleanUp(['utm_source', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'utm_source',
                'label' => $this->getLanguageService()->getLL('barChart.labels.UTMSource'),
                'filter' => [
                    'name' => 'visit:utm_source',
                    'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMSourceIs'),
                ],
            ]
        );

        return $responseData;
    }

    public function getCampaignData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_campaign', $filters);

        $map = $this->plausibleService->dataCleanUp(['utm_campaign', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'utm_campaign',
                'label' => $this->getLanguageService()->getLL('barChart.labels.UTMCampaign'),
                'filter' => [
                    'name' => 'visit:utm_campaign',
                    'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMCampaignIs'),
                ],
            ]
        );

        return $responseData;
    }

    public function getTermData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_term', $filters);

        $map = $this->plausibleService->dataCleanUp(['utm_term', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'utm_term',
                'label' => $this->getLanguageService()->getLL('barChart.labels.UTMTerm'),
                'filter' => [
                    'name' => 'visit:utm_term',
                    'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMTermIs'),
                ],
            ]
        );

        return $responseData;
    }

    public function getContentData(string $plausibleSiteId, string $timeFrame, array $filters = []): array
    {
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_content', $filters);

        $map = $this->plausibleService->dataCleanUp(['utm_content', 'visitors'], $responseData['data']);
        $map = $this->plausibleService->calcPercentage($map);
        $responseData['data'] = $map;

        array_unshift(
            $responseData['columns'],
            [
                'name' => 'utm_content',
                'label' => $this->getLanguageService()->getLL('barChart.labels.UTMContent'),
                'filter' => [
                    'name' => 'visit:utm_content',
                    'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMContentIs'),
                ],
            ]
        );

        return $responseData;
    }

    private function getData(string $plausibleSiteId, string $timeFrame, string $property, array $filters = []): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property,
            'metrics' => 'visitors',
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

        $responseData['columns'][] = [
            'name' => 'visitors',
            'label' => $this->getLanguageService()->getLL('barChart.labels.visitors'),
        ];

        return $responseData;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
