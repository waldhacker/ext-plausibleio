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

namespace Waldhacker\Plausibleio\Tests\Unit\Dashboard\DataProvider;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Dashboard\DataProvider\TimeSeriesDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class TimeSeriesDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\TimeSeriesDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\TimeSeriesDataProvider::getChartData
     */
    public function getChartData(): void
    {
        $languageService = $this->prophesize(LanguageService::class);
        $languageService->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->willReturn();
        $languageService->getLL('visitors')->willReturn('Visitors');

        $configurationService = $this->prophesize(ConfigurationService::class);

        $timeFrame = '30d';
        $site = 'example.com';
        $visitorDataSet = new \stdClass();
        $visitorDataSet->date = '2021-04-30';
        $visitorDataSet->visitors = 30;
        $visitorDataSet2 = new \stdClass();
        $visitorDataSet2->date = '2021-05-01';
        $visitorDataSet2->visitors = 42;

        $plausibleService = $this->prophesize(PlausibleService::class);
        $plausibleService->getVisitors($timeFrame, $site)->willReturn(
            [
                $visitorDataSet,
                $visitorDataSet2,
            ]
        );

        $dataProvider = new TimeSeriesDataProvider(
            $plausibleService->reveal(),
            $languageService->reveal(),
            $configurationService->reveal()
        );
        $result = $dataProvider->getChartData($timeFrame, $site);

        self::assertSame('Visitors', $result['datasets'][0]['label']);
        self::assertSame([30, 42], $result['datasets'][0]['data']);
    }
}
