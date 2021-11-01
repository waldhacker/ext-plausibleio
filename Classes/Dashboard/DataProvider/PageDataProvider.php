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

use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class PageDataProvider
{
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;

    public function __construct(PlausibleService $plausibleService, ConfigurationService $configurationService)
    {
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
    }

    private function getPageData(string $property, ?string $timeFrame = null, ?string $site = null): array
    {
        $timeFrame = $timeFrame ?? $this->configurationService->getDefaultTimeFrameValue();
        $site = $site ?? $this->configurationService->getDefaultSite();

        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
            'property' => $property
        ];

        return $this->plausibleService->sendAuthorizedRequest($endpoint, $params);
    }

    public function getTopPageData(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->getPageData('event:page', $timeFrame, $site);
    }

    public function getEntryPageData(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->getPageData('visit:entry_page', $timeFrame, $site);
    }

    public function getExitPageData(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->getPageData('visit:exit_pag', $timeFrame, $site);
    }
}
