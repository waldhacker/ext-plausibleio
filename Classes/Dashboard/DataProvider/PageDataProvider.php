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
        $result = $this->getData($plausibleSiteId, $timeFrame, 'event:page');

        foreach ($result as $item) {
            if (empty($item['page']) || empty($item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['page'], 'visitors' => $item['visitors']];
        }

        return $map;
    }

    public function getEntryPageData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:entry_page');

        foreach ($result as $item) {
            if (empty($item['entry_page']) || empty($item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['entry_page'], 'visitors' => $item['visitors']];
        }

        return $map;
    }

    public function getExitPageData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:exit_page');

        foreach ($result as $item) {
            if (empty($item['exit_page']) || empty($item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['exit_page'], 'visitors' => $item['visitors']];
        }

        return $map;
    }

    private function getData(string $plausibleSiteId, string $timeFrame, string $property): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property
        ];

        $responseData = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        return is_array($responseData) ? $responseData : [];
    }
}
