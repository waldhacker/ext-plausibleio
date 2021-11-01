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

class BrowserDataProvider
{
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;

    public function __construct(PlausibleService $plausibleService, ConfigurationService $configurationService)
    {
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
    }

    public function getBrowserData(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->plausibleService->getBrowserData(
            $timeFrame ?? $this->configurationService->getDefaultTimeFrameValue(),
            $site ?? $this->configurationService->getDefaultSite()
        );
    }
}
