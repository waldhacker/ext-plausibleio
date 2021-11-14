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

use Waldhacker\Plausibleio\Services\ISO3166Service;
use Waldhacker\Plausibleio\Services\PlausibleService;

class CountryMapDataProvider
{
    private PlausibleService $plausibleService;
    private ISO3166Service $ISO3166Service;

    public function __construct(
        PlausibleService $plausibleService,
        ISO3166Service $ISO3166Service
    ) {
        $this->plausibleService = $plausibleService;
        $this->ISO3166Service = $ISO3166Service;
    }

    public function getCountryDataForDataMap(string $plausibleSiteId, string $timeFrame): array
    {
        return $this->plausibleToDataMap($this->getCountryData($plausibleSiteId, $timeFrame));
    }

    private function getCountryData(string $plausibleSiteId, string $timeFrame): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:country',
        ];

        $responseData = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        return is_array($responseData) ? $responseData : [];
    }

    private function plausibleToDataMap(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            if (
                !is_array($item)
                || empty($item['country'])
                || empty($item['visitors'])
            ) {
                continue;
            }
            $iso3166Data = $this->ISO3166Service->alpha2($item['country']);
            if ($iso3166Data === null) {
                continue;
            }

            $result[] = [
                $iso3166Data[ISO3166Service::ALPHA3],
                $item['visitors']
            ];
        }

        return $result;
    }
}
