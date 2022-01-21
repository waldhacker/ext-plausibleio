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

use GuzzleHttp\Client;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider;
use Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class VisitorsOverTimeDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function tearDown(): void
    {
        $GLOBALS['LANG'] = null;

        parent::tearDown();
    }

    public function getOverviewWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointOverviewWithoutGoalData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 40],
            ],
            'endpointCurrentVisitorsData' => 12,
            'endpointGetGoalsData' => [
                'data' => [
                    0 => [
                        'visitors' => 20,
                        'events' => 32,
                        'cr' => 0.5,
                    ],
                ],
            ],
            'expected' => [
                'columns' => [
                    [
                        'name' => 'visitors',
                        'label' => 'Unique visitors',
                    ],
                    [
                        'name' => 'uniques_conversions',
                        'label' => 'Unique conversions',
                    ],
                    [
                        'name' => 'total_conversions',
                        'label' => 'Total conversions',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'Conversion rate',
                    ],
                ],
                'data' => [
                    'visitors' => 40,
                    'uniques_conversions' => 20,
                    'total_conversions' => 32,
                    'cr' => 0.5,
                ],
            ],
        ];

        yield 'items without bounce_rate are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointOverviewWithoutGoalData' => [
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 40],
            ],
            'endpointCurrentVisitorsData' => 12,
            'endpointGetGoalsData' => [
                'data' => [
                    0 => [
                        'visitors' => 20,
                        'events' => 32,
                        'cr' => 0.5,
                    ],
                ],
            ],
            'expected' => [
                'columns' => [
                    [
                        'name' => 'visitors',
                        'label' => 'Unique visitors',
                    ],
                    [
                        'name' => 'uniques_conversions',
                        'label' => 'Unique conversions',
                    ],
                    [
                        'name' => 'total_conversions',
                        'label' => 'Total conversions',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'Conversion rate',
                    ],
                ],
                'data' => [
                    'visitors' => 0,
                    'uniques_conversions' => 20,
                    'total_conversions' => 32,
                    'cr' => 0.5,
                ],
            ],
        ];

        yield 'items without pageviews are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointOverviewWithoutGoalData' => [
                'bounce_rate' => ['value' => 1],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 40],
            ],
            'endpointCurrentVisitorsData' => 12,
            'endpointGetGoalsData' => [
                'data' => [
                    0 => [
                        'visitors' => 20,
                        'events' => 32,
                        'cr' => 0.5,
                    ],
                ],
            ],
            'expected' => [
                'columns' => [
                    [
                        'name' => 'visitors',
                        'label' => 'Unique visitors',
                    ],
                    [
                        'name' => 'uniques_conversions',
                        'label' => 'Unique conversions',
                    ],
                    [
                        'name' => 'total_conversions',
                        'label' => 'Total conversions',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'Conversion rate',
                    ],
                ],
                'data' => [
                    'visitors' => 0,
                    'uniques_conversions' => 20,
                    'total_conversions' => 32,
                    'cr' => 0.5,
                ],
            ],
        ];

        yield 'items without visit_duration are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointOverviewWithoutGoalData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visitors' => ['value' => 40],
            ],
            'endpointCurrentVisitorsData' => 12,
            'endpointGetGoalsData' => [
                'data' => [
                    0 => [
                        'visitors' => 20,
                        'events' => 32,
                        'cr' => 0.5,
                    ],
                ],
            ],
            'expected' => [
                'columns' => [
                    [
                        'name' => 'visitors',
                        'label' => 'Unique visitors',
                    ],
                    [
                        'name' => 'uniques_conversions',
                        'label' => 'Unique conversions',
                    ],
                    [
                        'name' => 'total_conversions',
                        'label' => 'Total conversions',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'Conversion rate',
                    ],
                ],
                'data' => [
                    'visitors' => 0,
                    'uniques_conversions' => 20,
                    'total_conversions' => 32,
                    'cr' => 0.5,
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointOverviewWithoutGoalData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
            ],
            'endpointCurrentVisitorsData' => 12,
            'endpointGetGoalsData' => [
                'data' => [
                    0 => [
                        'visitors' => 20,
                        'events' => 32,
                        'cr' => 0.5,
                    ],
                ],
            ],
            'expected' => [
                'columns' => [
                    [
                        'name' => 'visitors',
                        'label' => 'Unique visitors',
                    ],
                    [
                        'name' => 'uniques_conversions',
                        'label' => 'Unique conversions',
                    ],
                    [
                        'name' => 'total_conversions',
                        'label' => 'Total conversions',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'Conversion rate',
                    ],
                ],
                'data' => [
                    'visitors' => 0,
                    'uniques_conversions' => 20,
                    'total_conversions' => 32,
                    'cr' => 0.5,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getOverviewWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverview
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getCurrentVisitors
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverviewWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverviewWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     */
    public function getOverviewWithGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointOverviewWithoutGoalData,
        int $endpointCurrentVisitorsData,
        ?array $endpointGetGoalsData,
        array $expected
    ): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $goalDataProviderProphecy = $this->prophesize(GoalDataProvider::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->shouldBeCalled();
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.uniqueVisitors')->willReturn('Unique visitors');
        $languageServiceProphecy->getLL('barChart.labels.uniqueConversions')->willReturn('Unique conversions');
        $languageServiceProphecy->getLL('barChart.labels.totalConversions')->willReturn('Total conversions');
        $languageServiceProphecy->getLL('barChart.labels.conversionRate')->willReturn('Conversion rate');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.uniqueVisitors')->willReturn('Unique visitors');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.totalPageviews')->willReturn('Total pageviews');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.visitDuration')->willReturn('Visit duration');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.currentVisitors')->willReturn('Current visitors');

        $plausibleServiceProphecy->removeFilter(['event:goal'], $filters)->willReturn([]);

        if ($filters) {
            $plausibleServiceProphecy->filtersToPlausibleFilterString($filters)->willReturn($filters[0]['name']);
        }
        $plausibleServiceProphecy->filtersToPlausibleFilterString([])->willReturn('');

        $goalDataProviderProphecy->getGoalsData($plausibleSiteId, $timeFrame, $filters)->willReturn($endpointGetGoalsData)->shouldBeCalled();

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors,visit_duration,pageviews,bounce_rate',
        ];
        if ($filters) {
            $authorizedRequestParams['filters'] = $filters[0]['name'];
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            '/api/v1/stats/aggregate?',
            $authorizedRequestParams
        )
        ->willReturn($endpointOverviewWithoutGoalData)
        ->shouldBeCalled();

        unset($authorizedRequestParams['period']);
        unset($authorizedRequestParams['metrics']);
        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            '/api/v1/stats/realtime/visitors?',
            $authorizedRequestParams
        )
            ->willReturn($endpointCurrentVisitorsData);

        $subject = new VisitorsOverTimeDataProvider($goalDataProviderProphecy->reveal(), $plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getOverviewWithGoal($plausibleSiteId, $timeFrame, $filters));
    }

    public function getOverviewWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 4],
            ],
            'endpointCurrentVisitorsData' => 12,
            'expected' => [
                'columns' => [
                    [
                        'name' => 'visitors',
                        'label' => 'Unique visitors',
                    ],
                    [
                        'name' => 'pageviews',
                        'label' => 'Total pageviews',
                    ],
                    [
                        'name' => 'visit_duration',
                        'label' => 'Visit duration',
                    ],
                    [
                        'name' => 'current_visitors',
                        'label' => 'Current visitors',
                    ],
                ],
                'data' => [
                    'bounce_rate' => 1,
                    'pageviews' => 2,
                    'visit_duration' => '3s',
                    'visitors' => 4,
                    'current_visitors' => 12,
                ],
            ],
        ];

        yield 'items without bounce_rate are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 4],
            ],
            'endpointCurrentVisitorsData' => 12,
            'expected' => [],
        ];

        yield 'items without pageviews are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 4],
            ],
            'endpointCurrentVisitorsData' => 12,
            'expected' => [],
        ];

        yield 'items without visit_duration are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visitors' => ['value' => 4],
            ],
            'endpointCurrentVisitorsData' => 12,
            'expected' => [],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
            ],
            'endpointCurrentVisitorsData' => 12,
            'expected' => [],
        ];
    }

    /**
     * @test
     * @dataProvider getOverviewWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverview
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getCurrentVisitors
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverviewWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     */
    public function getOverviewWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        int $endpointCurrentVisitorsData,
        array $expected
    ): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $goalDataProviderProphecy = $this->prophesize(GoalDataProvider::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->shouldBeCalled();
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.uniqueVisitors')->willReturn('Unique visitors');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.totalPageviews')->willReturn('Total pageviews');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.visitDuration')->willReturn('Visit duration');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.currentVisitors')->willReturn('Current visitors');

        if ($filters) {
            $plausibleServiceProphecy->filtersToPlausibleFilterString($filters)->willReturn($filters[0]['name']);
        }
        $plausibleServiceProphecy->filtersToPlausibleFilterString([])->willReturn('');

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors,visit_duration,pageviews,bounce_rate',
        ];
        if ($filters) {
            $authorizedRequestParams['filters'] = $filters[0]['name'];
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            '/api/v1/stats/aggregate?',
            $authorizedRequestParams
        )
            ->willReturn($endpointData)
            ->shouldBeCalled();

        unset($authorizedRequestParams['period']);
        unset($authorizedRequestParams['metrics']);
        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            '/api/v1/stats/realtime/visitors?',
            $authorizedRequestParams
        )
            ->willReturn($endpointCurrentVisitorsData);

        $subject = new VisitorsOverTimeDataProvider($goalDataProviderProphecy->reveal(), $plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getOverviewWithoutGoal($plausibleSiteId, $timeFrame, $filters));
    }

    public function getOverviewReturnsProperValuesDataProvider(): \Generator
    {
        yield 'without goal filter' => [
            'filters' => [],
        ];

        yield 'with goal filter' => [
            'filters' => ['name' => 'event:goal'],
        ];
    }

    /**
     * @test
     * @dataProvider getOverviewReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverview
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverviewWithGoal
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverviewWithoutGoal
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::isFilterActivated
     */
    public function getOverviewCallsMethodsCorrect(
        array $filters
    ): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $goalDataProviderProphecy = $this->prophesize(GoalDataProvider::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        if (count($filters)) {
            $plausibleServiceProphecy->isFilterActivated('event:goal', $filters)->willReturn(['name' => 'event:goal']);
        } else {
            $plausibleServiceProphecy->isFilterActivated('event:goal', $filters)->willReturn(null);
        }

        $VisitorsOverTimeDataProviderMock = $this->getMockBuilder(VisitorsOverTimeDataProvider::class)
            ->onlyMethods(['getOverviewWithoutGoal', 'getOverviewWithGoal'])
            ->setConstructorArgs([
                $goalDataProviderProphecy->reveal(),
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        if (count($filters)) {
            $VisitorsOverTimeDataProviderMock->expects($this->exactly(1))->method('getOverviewWithGoal');
        } else {
            $VisitorsOverTimeDataProviderMock->expects($this->exactly(1))->method('getOverviewWithoutGoal');
        }

        $VisitorsOverTimeDataProviderMock->getOverview('', '', $filters);
    }

    public function getCurrentVisitorsReturnsVisitorsDataProvider(): \Generator
    {
        yield 'integers from API will be returned' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'filters' => [],
            'endpointData' => 2,
            'expected' => 2,
        ];

        yield 'integers from API will be returned with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'filters' => [
                ['name' => 'visit:device==Desktop'],
            ],
            'endpointData' => 2,
            'expected' => 2,
        ];

        yield 'non integers from API will be returned as 0' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'filters' => [],
            'endpointData' => null,
            'expected' => 0,
        ];
    }

    /**
     * @test
     * @dataProvider getCurrentVisitorsReturnsVisitorsDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getCurrentVisitors
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     */
    public function getCurrentVisitorsReturnsVisitors(
        string $plausibleSiteId,
        array $filters,
        ?int $endpointData,
        int $expected
    ): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $goalDataProviderProphecy = $this->prophesize(GoalDataProvider::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->shouldBeCalled();

        if ($filters) {
            $plausibleServiceProphecy->filtersToPlausibleFilterString($filters)->willReturn($filters[0]['name']);
        }
        $plausibleServiceProphecy->filtersToPlausibleFilterString([])->willReturn('');

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
        ];
        if ($filters) {
            $authorizedRequestParams['filters'] = $filters[0]['name'];
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            '/api/v1/stats/realtime/visitors?',
            $authorizedRequestParams
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new VisitorsOverTimeDataProvider($goalDataProviderProphecy->reveal(), $plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getCurrentVisitors($plausibleSiteId, $filters));
    }

    public function getChartDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['date' => '2021-04-16', 'visitors' => 3],
                ['date' => '2021-05-16', 'visitors' => 4],
            ],
            'expected' => [
                'labels' => [
                    '2021-04-16',
                    '2021-05-16',
                ],
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [3, 4],
                        'fill' => false,
                        'borderColor' => '#85bcee',
                        'tension' => 0.5,
                    ],
                ],
            ],
        ];

        yield 'all items are transformed with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [
                ['name' => 'visit:device==Desktop'],
            ],
            'endpointData' => [
                ['date' => '2021-04-16', 'visitors' => 3],
                ['date' => '2021-05-16', 'visitors' => 4],
            ],
            'expected' => [
                'labels' => [
                    '2021-04-16',
                    '2021-05-16',
                ],
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [3, 4],
                        'fill' => false,
                        'borderColor' => '#85bcee',
                        'tension' => 0.5,
                    ],
                ],
            ],
        ];

        yield 'items without date are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['date' => '2021-04-16', 'visitors' => 3],
                ['date' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'labels' => [
                    '2021-04-16',
                    ''
                ],
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [3, 4],
                        'fill' => false,
                        'borderColor' => '#85bcee',
                        'tension' => 0.5,
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['date' => '2021-04-16', 'visitors' => 3],
                ['date' => '2021-05-16', 'visitors' => null],
                ['date' => '2021-05-16'],
            ],
            'expected' => [
                'labels' => [
                    '2021-04-16',
                ],
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [3],
                        'fill' => false,
                        'borderColor' => '#85bcee',
                        'tension' => 0.5,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getChartDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getChartData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getVisitors
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     */
    public function getChartDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $goalDataProviderProphecy = $this->prophesize(GoalDataProvider::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->shouldBeCalled();
        $languageServiceProphecy->getLL('visitors')->willReturn('Visitors');

        $plausibleServiceProphecy->isFilterActivated('event:goal', [])->willReturn(null);
        if ($filters && $filters[0]['name'] == 'event:goal') {
            $plausibleServiceProphecy->isFilterActivated('event:goal', $filters)->willReturn($filters[0]);
        } else {
            $plausibleServiceProphecy->isFilterActivated('event:goal', $filters)->willReturn(null);
        }

        if ($filters) {
            $plausibleServiceProphecy->filtersToPlausibleFilterString($filters)->willReturn($filters[0]['name']);
        }
        $plausibleServiceProphecy->filtersToPlausibleFilterString([])->willReturn('');

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
        ];
        if ($filters) {
            $authorizedRequestParams['filters'] = $filters[0]['name'];
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            'api/v1/stats/timeseries?',
            $authorizedRequestParams
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new VisitorsOverTimeDataProvider($goalDataProviderProphecy->reveal(), $plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getChartData($plausibleSiteId, $timeFrame, $filters));
    }
}
