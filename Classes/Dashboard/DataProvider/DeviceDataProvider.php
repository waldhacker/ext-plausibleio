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

class DeviceDataProvider
{
    private PlausibleService $plausibleService;

    public function __construct(PlausibleService $plausibleService)
    {
        $this->plausibleService = $plausibleService;
    }

    public function getBrowserData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:browser');

        foreach ($result as $item) {
            if (!isset($item['browser'], $item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['browser'], 'visitors' => $item['visitors']];
        }

        return $map;
    }

    public function getOSData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:os');

        foreach ($result as $item) {
            if (!isset($item['os'], $item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['os'], 'visitors' => $item['visitors']];
        }

        return $map;
    }

    public function getDeviceData(string $plausibleSiteId, string $timeFrame): array
    {
        $map = [];
        $result = $this->getData($plausibleSiteId, $timeFrame, 'visit:device');

        foreach ($result as $item) {
            if (!isset($item['device'], $item['visitors'])) {
                continue;
            }
            $map[] = ['label' => $item['device'], 'visitors' => $item['visitors']];
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
