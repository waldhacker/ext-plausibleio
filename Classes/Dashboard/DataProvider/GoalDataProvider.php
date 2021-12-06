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

class GoalDataProvider
{
    private const EXT_KEY = 'plausibleio';
    private PlausibleService $plausibleService;

    public function __construct(PlausibleService $plausibleService)
    {
        $this->plausibleService = $plausibleService;
        //$this->getLanguageService()->includeLLFile('EXT:' . self::EXT_KEY . '/Resources/Private/Language/locallang.xlf');
    }

    public function getGoals(string $plausibleSiteId, string $timeFrame): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        //$endpoint = 'api/stats/plausible-master.ddev.site/conversions?';
        //$endpoint = '/api/stats/'.$plausibleSiteId.'/conversions';
        //$endpoint = '/api/stats/' . $plausibleSiteId . '/browsers?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'event:goal',
            //'property' => 'event:name',
            'metrics' => 'visitors,pageviews',
        ];

        $responseData = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);

        if (!is_array($responseData)) {
            $responseData = [];
        }

        $visitorsSum = 0;
        foreach ($responseData as $item) {
            $visitorsSum = $visitorsSum + $item['visitors'];
        }
        foreach ($responseData as $key => $value) {
            $responseData[$key]['percentage'] = $value['visitors'] / $visitorsSum * 100;
        }

        $result = [
            'columns' => [
                [
                    'name' => 'goal',
                    'label' => $this->getLanguageService()->getLL('barChart.labels.goal'),
                ],
                [
                    'name' => 'pageviews',
                    'label' => 'Page Views',
                ],
                [
                    'name' => 'visitors',
                    'label' => $this->getLanguageService()->getLL('barChart.labels.visitors'),
                ],
            ],
            'data' => $responseData,
        ];

        return $result;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
