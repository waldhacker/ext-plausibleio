<?php

declare(strict_types = 1);

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
use Waldhacker\Plausibleio\Filter;
use Waldhacker\Plausibleio\FilterRepository;
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

        yield 'items without visitors set to visitors 0' => [
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

        yield 'items without uniques_conversions set to uniques_conversions 0' => [
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
                        //'visitors' => 20, // unique conversions
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
                    'uniques_conversions' => 0,
                    'total_conversions' => 32,
                    'cr' => 0.5,
                ],
            ],
        ];

        yield 'items without total_conversions set to total_conversions 0' => [
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
                        //'events' => 32, // total conversions
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
                    'total_conversions' => 0,
                    'cr' => 0.5,
                ],
            ],
        ];

        yield 'items without cr set to cr 0' => [
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
                        //'cr' => 0.5,
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
                    'cr' => 0,
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
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
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
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.uniqueVisitors')->willReturn('Unique visitors');
        $languageServiceProphecy->getLL('barChart.labels.uniqueConversions')->willReturn('Unique conversions');
        $languageServiceProphecy->getLL('barChart.labels.totalConversions')->willReturn('Total conversions');
        $languageServiceProphecy->getLL('barChart.labels.conversionRate')->willReturn('Conversion rate');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.uniqueVisitors')->willReturn('Unique visitors');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.totalPageviews')->willReturn('Total pageviews');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.visitDuration')->willReturn('Visit duration');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.currentVisitors')->willReturn('Current visitors');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $goalDataProviderProphecy->getGoalsData($plausibleSiteId, $timeFrame, $filterRepo)->willReturn($endpointGetGoalsData)->shouldBeCalled();

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors,visit_duration,pageviews,bounce_rate',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $authorizedRequestParamsGetCurrentVisitors = $authorizedRequestParams;
        unset($authorizedRequestParamsGetCurrentVisitors['period']);
        unset($authorizedRequestParamsGetCurrentVisitors['metrics']);

        $plausibleServiceProphecy
            ->sendAuthorizedRequest($plausibleSiteId, '/api/v1/stats/aggregate?', $authorizedRequestParams)
            ->willReturn($endpointOverviewWithoutGoalData);
        $plausibleServiceProphecy
            ->sendAuthorizedRequest($plausibleSiteId, '/api/v1/stats/realtime/visitors?', $authorizedRequestParamsGetCurrentVisitors)
            ->willReturn($endpointCurrentVisitorsData);

        $subject = new VisitorsOverTimeDataProvider($goalDataProviderProphecy->reveal(), $plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getOverviewWithGoal($plausibleSiteId, $timeFrame, $filterRepo));
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
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
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
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.uniqueVisitors')->willReturn('Unique visitors');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.totalPageviews')->willReturn('Total pageviews');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.visitDuration')->willReturn('Visit duration');
        $languageServiceProphecy->getLL('widget.visitorsOverTime.overview.currentVisitors')->willReturn('Current visitors');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors,visit_duration,pageviews,bounce_rate',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }
        $authorizedRequestParamsGetCurrentVisitors = $authorizedRequestParams;
        unset($authorizedRequestParamsGetCurrentVisitors['period']);
        unset($authorizedRequestParamsGetCurrentVisitors['metrics']);

        $plausibleServiceProphecy
            ->sendAuthorizedRequest($plausibleSiteId, '/api/v1/stats/aggregate?', $authorizedRequestParams)
            ->willReturn($endpointData);
        $plausibleServiceProphecy
            ->sendAuthorizedRequest($plausibleSiteId, '/api/v1/stats/realtime/visitors?', $authorizedRequestParamsGetCurrentVisitors)
            ->willReturn($endpointCurrentVisitorsData);

        $subject = new VisitorsOverTimeDataProvider($goalDataProviderProphecy->reveal(), $plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getOverviewWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverview
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverviewWithGoal
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverviewWithoutGoal
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getOverviewCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $goalDataProviderProphecy = $this->prophesize(GoalDataProvider::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $VisitorsOverTimeDataProviderMock = $this->getMockBuilder(VisitorsOverTimeDataProvider::class)
            ->onlyMethods(['getOverviewWithoutGoal', 'getOverviewWithGoal'])
            ->setConstructorArgs([
                $goalDataProviderProphecy->reveal(),
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $VisitorsOverTimeDataProviderMock->expects($this->once())->method('getOverviewWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $VisitorsOverTimeDataProviderMock->getOverview('', '', $filterRepo));

        $VisitorsOverTimeDataProviderMock->expects($this->once())->method('getOverviewWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $VisitorsOverTimeDataProviderMock->getOverview('', '', new FilterRepository()));
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
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getCurrentVisitors
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
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

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            '/api/v1/stats/realtime/visitors?',
            $authorizedRequestParams
        )
            ->willReturn($endpointData)
            ->shouldBeCalled();

        $subject = new VisitorsOverTimeDataProvider($goalDataProviderProphecy->reveal(), $plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getCurrentVisitors($plausibleSiteId, $filterRepo));
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
                    '',
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
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getChartData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getVisitors
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
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
        $languageServiceProphecy->getLL('visitors')->willReturn('Visitors');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            'api/v1/stats/timeseries?',
            $authorizedRequestParams
        )
            ->willReturn($endpointData)
            ->shouldBeCalled();

        $subject = new VisitorsOverTimeDataProvider($goalDataProviderProphecy->reveal(), $plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getChartData($plausibleSiteId, $timeFrame, $filterRepo));
    }
}
