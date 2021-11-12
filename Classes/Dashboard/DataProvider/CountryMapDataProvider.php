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

use League\ISO3166\Exception\DomainException;
use League\ISO3166\ISO3166;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class CountryMapDataProvider
{
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;

    public function __construct(PlausibleService $plausibleService, ConfigurationService $configurationService)
    {
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
    }

    private function plausibleToDataMap(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            if (
                !is_object($item)
                || !property_exists($item, 'country')
                || !property_exists($item, 'visitors')
            ) {
                continue;
            }

            try {
                $iso3166Data = (new ISO3166)->alpha2($item->country);
            } catch (DomainException $e) {
                continue;
            }

            $result[] = [
                $iso3166Data['alpha3'],
                $item->visitors
            ];
        }

        return $result;
    }

    public function getCountryData(?string $timeFrame = null, ?string $site = null): array
    {
        $timeFrame = $timeFrame ?? $this->configurationService->getDefaultTimeFrameValue();
        $site = $site ?? $this->configurationService->getDefaultSite();

        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
            'property' => 'visit:country',
        ];

        return $this->plausibleService->sendAuthorizedRequest($endpoint, $params);
    }

    public function getCountryDataForDataMap(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->plausibleToDataMap($this->getCountryData($timeFrame, $site));
    }
}
