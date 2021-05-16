<?php

declare(strict_types=1);

namespace Waldhacker\Plausibleio\Tests\Unit;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Services\ChartService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class ChartServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ChartService::getChartDataForTimeSeries
     */
    public function getChartDataForTimeSeries(): void
    {
        $languageService = $this->prophesize(LanguageService::class);
        $languageService->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->willReturn();
        $languageService->getLL('visitors')->willReturn('Visitors');
        $plausibleService = $this->prophesize(PlausibleService::class);
        $timeFrame = '30d';
        $site = 'example.com';
        $visitorDataSet = new \stdClass();
        $visitorDataSet->date = '2021-04-30';
        $visitorDataSet->visitors = 30;
        $visitorDataSet2 = new \stdClass();
        $visitorDataSet2->date = '2021-05-01';
        $visitorDataSet2->visitors = 42;
        $plausibleService->getVisitors($timeFrame, $site)->willReturn(
            [
                $visitorDataSet,
                $visitorDataSet2,
            ]
        );
        $chartService = new ChartService($plausibleService->reveal(), $languageService->reveal());
        $result = $chartService->getChartDataForTimeSeries($timeFrame, $site);

        self::assertSame('Visitors', $result['datasets'][0]['label']);
        self::assertSame([30, 42], $result['datasets'][0]['data']);
    }
}
