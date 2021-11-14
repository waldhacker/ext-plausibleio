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
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:source');

        foreach ($result as $item) {
            if (!isset($item['source']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['source'], 'visitors' => $item['visitors']];
        }

        return $map;
    }

    public function getMediumData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_medium');

        foreach ($result as $item) {
            if (!isset($item['utm_medium']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['utm_medium'], 'visitors' => $item['visitors']];
        }

        return $map;
    }

    public function getSourceData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_source');

        foreach ($result as $item) {
            if (!isset($item['utm_source']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['utm_source'], 'visitors' => $item['visitors']];
        }

        return $map;
    }

    public function getCampaignData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_campaign');

        foreach ($result as $item) {
            if (!isset($item['utm_campaign']) || !isset($item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['utm_campaign'], 'visitors' => $item['visitors']];
        }

        return $map;
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

        $responseData = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        return is_array($responseData) ? $responseData : [];
    }
}
