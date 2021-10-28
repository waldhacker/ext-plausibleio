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

namespace Waldhacker\Plausibleio\Services;

use TYPO3\CMS\Core\Localization\LanguageService;

class ChartService
{
    private PlausibleService $plausibleService;
    private LanguageService $languageService;
    private const EXT_KEY = 'plausibleio';

    public function __construct(
        PlausibleService $plausibleService,
        LanguageService $languageService
    ) {
        $this->plausibleService = $plausibleService;
        $this->languageService = $languageService;
        $this->languageService->includeLLFile('EXT:' . self::EXT_KEY . '/Resources/Private/Language/locallang.xlf');
    }

    public function getChartDataForTimeSeries(string $timeFrame, string $site): array
    {
        $results = $this->plausibleService->getVisitors($timeFrame, $site);

        $r = random_int(1, 255);
        $g = random_int(1, 255);
        $b = random_int(1, 255);
        $labels = [];
        $data = [];
        foreach ($results as $datum) {
            $labels[] = $datum->date;
            $data[] = $datum->visitors;
        }
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $this->languageService->getLL('visitors'),
                    'data' => $data,
                    'fill' => false,
                    'borderColor' => "rgb($r, $g, $b)",
                    'tension' => 0.5,
                ],
            ],
        ];
    }
}
