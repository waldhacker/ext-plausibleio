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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Filter;
use Waldhacker\Plausibleio\FilterRepository;
use Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class PageDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    private ObjectProphecy $languageServiceProphecy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $this->languageServiceProphecy->reveal();
    }

    public function getTopPageDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'topPageDataWithGoal' => [
                'data' => [
                    ['page' => '/start/end', 'visitors' => 12],
                    ['page' => '/into/darkness', 'visitors' => 8],
                ],
            ],
            'topPageDataWithoutGoal' => [
                'data' => [
                    ['page' => '/start/end', 'visitors' => 48],
                    ['page' => '/into/darkness', 'visitors' => 16],
                ],
                'columns' => [
                    [
                        'name' => 'page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'event:page',
                            'label' => 'Page is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['page' => '/start/end', 'visitors' => 12, 'cr' => '25%'],
                    ['page' => '/into/darkness', 'visitors' => 8, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'event:page',
                            'label' => 'Page is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Conversions'],
                    ['name' => 'cr', 'label' => 'CR'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getTopPageDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getTopPageDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getTopPageDataWithGoalReturnsProperValues(
        ?array $topPageDataWithGoal,
        ?array $topPageDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(PageDataProvider::class)
            ->onlyMethods(['getTopPageDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getTopPageDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($topPageDataWithGoal, $topPageDataWithoutGoal);

        self::assertSame($expected, $subject->getTopPageDataWithGoal('', '', new FilterRepository()));
    }

    public function getTopPageDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['page' => '/de', 'visitors' => 12],
                ['page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['page' => '/en', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'event:page',
                            'label' => 'Page is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'all items are transformed with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [['name' => FilterRepository::FILTEREVENTPAGE, 'value' => '/startpage/subpage']],
            'endpointData' => [
                ['page' => '/de', 'visitors' => 12],
                ['page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['page' => '/en', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'page',
                        'label' => 'Page url',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without page are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['page' => '/de', 'visitors' => 12],
                ['page' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['page' => '', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'event:page',
                            'label' => 'Page is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['page' => '/de', 'visitors' => 3],
                ['page' => '/en', 'visitors' => null],
                ['page' => '/en'],
            ],
            'expected' => [
                'data' => [
                    ['page' => '/de', 'visitors' => 3, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'event:page',
                            'label' => 'Page is',
                            ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getTopPageDataWithoutGoalReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getTopPageDataWithoutGoal
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::count
     * @covers \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     */
    public function getTopPageDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.pageUrl')->willReturn('Page url');
        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('filter.pageData.pageIs')->willReturn('Page is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'event:page',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new PageDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getTopPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getTopPageData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getTopPageDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);

        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $topPageDataProviderMock = $this->getMockBuilder(PageDataProvider::class)
            ->onlyMethods(['getTopPageDataWithoutGoal', 'getTopPageDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $topPageDataProviderMock->expects($this->once())->method('getTopPageDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $topPageDataProviderMock->getTopPageData('', '', $filterRepo));

        $topPageDataProviderMock->expects($this->once())->method('getTopPageDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $topPageDataProviderMock->getTopPageData('', '', new FilterRepository()));
    }

    public function getEntryPageDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'entryPageDataWithGoal' => [
                'data' => [
                    ['entry_page' => '/start/end', 'visitors' => 12],
                    ['entry_page' => '/into/darkness', 'visitors' => 8],
                ],
            ],
            'entryPageDataWithoutGoal' => [
                'data' => [
                    ['entry_page' => '/start/end', 'visitors' => 48],
                    ['entry_page' => '/into/darkness', 'visitors' => 16],
                ],
                'columns' => [
                    [
                        'name' => 'entry_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:entry_page',
                            'label' => 'Entry page is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['entry_page' => '/start/end', 'visitors' => 12, 'cr' => '25%'],
                    ['entry_page' => '/into/darkness', 'visitors' => 8, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'entry_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:entry_page',
                            'label' => 'Entry page is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Conversions'],
                    ['name' => 'cr', 'label' => 'CR'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getEntryPageDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getEntryPageDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getEntryPageDataWithGoalReturnsProperValues(
        ?array $entryPageDataWithGoal,
        ?array $entryPageDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(PageDataProvider::class)
            ->onlyMethods(['getEntryPageDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getEntryPageDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($entryPageDataWithGoal, $entryPageDataWithoutGoal);

        self::assertSame($expected, $subject->getEntryPageDataWithGoal('', '', new FilterRepository()));
    }

    public function getEntryPageDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['entry_page' => '/de', 'visitors' => 12],
                ['entry_page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['entry_page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['entry_page' => '/en', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'entry_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:entry_page',
                            'label' => 'Entry page is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Unique Entrances',
                    ],
                ],
            ],
        ];

        yield 'all items are transformed with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [['name' => FilterRepository::FILTERVISITENTRYPAGE, 'value' => '/startpage/subpage']],
            'endpointData' => [
                ['entry_page' => '/de', 'visitors' => 12],
                ['entry_page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['entry_page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['entry_page' => '/en', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'entry_page',
                        'label' => 'Page url',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Unique Entrances',
                    ],
                ],
            ],
        ];

        yield 'items without entry_page are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['entry_page' => '/de', 'visitors' => 12],
                ['entry_page' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['entry_page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['entry_page' => '', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'entry_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:entry_page',
                            'label' => 'Entry page is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Unique Entrances',
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['entry_page' => '/de', 'visitors' => 3],
                ['entry_page' => '/en', 'visitors' => null],
                ['entry_page' => '/en'],
            ],
            'expected' => [
                'data' => [
                    ['entry_page' => '/de', 'visitors' => 3, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'entry_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:entry_page',
                            'label' => 'Entry page is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Unique Entrances',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getEntryPageDataWithoutGoalReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getEntryPageDataWithoutGoal
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::count
     * @covers \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     */
    public function getEntryPageDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.pageUrl')->willReturn('Page url');
        $this->languageServiceProphecy->getLL('barChart.labels.uniqueEntrances')->willReturn('Unique Entrances');
        $this->languageServiceProphecy->getLL('filter.pageData.entryPageIs')->willReturn('Entry page is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:entry_page',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new PageDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getEntryPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getEntryPageData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getEntryPageDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);

        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $entryPageDataProviderMock = $this->getMockBuilder(PageDataProvider::class)
            ->onlyMethods(['getEntryPageDataWithoutGoal', 'getEntryPageDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $entryPageDataProviderMock->expects($this->once())->method('getEntryPageDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $entryPageDataProviderMock->getEntryPageData('', '', $filterRepo));

        $entryPageDataProviderMock->expects($this->once())->method('getEntryPageDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $entryPageDataProviderMock->getEntryPageData('', '', new FilterRepository()));
    }

    public function getExitPageDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'exitPageDataWithGoal' => [
                'data' => [
                    ['exit_page' => '/start/end', 'visitors' => 12],
                    ['exit_page' => '/into/darkness', 'visitors' => 8],
                ],
            ],
            'exitPageDataWithoutGoal' => [
                'data' => [
                    ['exit_page' => '/start/end', 'visitors' => 48],
                    ['exit_page' => '/into/darkness', 'visitors' => 16],
                ],
                'columns' => [
                    [
                        'name' => 'exit_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:exit_page',
                            'label' => 'Exit page is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['exit_page' => '/start/end', 'visitors' => 12, 'cr' => '25%'],
                    ['exit_page' => '/into/darkness', 'visitors' => 8, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'exit_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:exit_page',
                            'label' => 'Exit page is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Conversions'],
                    ['name' => 'cr', 'label' => 'CR'],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getExitPageDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getExitPageDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getExitPageDataWithGoalReturnsProperValues(
        ?array $exitPageDataWithGoal,
        ?array $exitPageDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(PageDataProvider::class)
            ->onlyMethods(['getExitPageDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getExitPageDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($exitPageDataWithGoal, $exitPageDataWithoutGoal);

        self::assertSame($expected, $subject->getExitPageDataWithGoal('', '', new FilterRepository()));
    }

    public function getExitPageDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['exit_page' => '/de', 'visitors' => 12],
                ['exit_page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['exit_page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['exit_page' => '/en', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'exit_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:exit_page',
                            'label' => 'Exit page is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Unique Exits',
                    ],
                ],
            ],
        ];

        yield 'all items are transformed with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [['name' => 'visit:exit_page', 'value' => '/startpage/subpage']],
            'endpointData' => [
                ['exit_page' => '/de', 'visitors' => 12],
                ['exit_page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['exit_page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['exit_page' => '/en', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'exit_page',
                        'label' => 'Page url',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Unique Exits',
                    ],
                ],
            ],
        ];

        yield 'items without exit_page are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['exit_page' => '/de', 'visitors' => 12],
                ['exit_page' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['exit_page' => '/de', 'visitors' => 12, 'percentage' => 75.0],
                    ['exit_page' => '', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'exit_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:exit_page',
                            'label' => 'Exit page is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Unique Exits',
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['exit_page' => '/de', 'visitors' => 3],
                ['exit_page' => '/en', 'visitors' => null],
                ['exit_page' => '/en'],
            ],
            'expected' => [
                'data' => [
                    ['exit_page' => '/de', 'visitors' => 3, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'exit_page',
                        'label' => 'Page url',
                        'filter' => [
                            'name' => 'visit:exit_page',
                            'label' => 'Exit page is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Unique Exits',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getExitPageDataWithoutGoalReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getExitPageDataWithoutGoal
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::count
     * @covers \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
    */
    public function getExitPageDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.pageUrl')->willReturn('Page url');
        $this->languageServiceProphecy->getLL('barChart.labels.uniqueExits')->willReturn('Unique Exits');
        $this->languageServiceProphecy->getLL('filter.pageData.exitPageIs')->willReturn('Exit page is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:exit_page',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new PageDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getExitPageDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getExitPageData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getExitPageDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);

        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $exitPageDataProviderMock = $this->getMockBuilder(PageDataProvider::class)
            ->onlyMethods(['getExitPageDataWithoutGoal', 'getExitPageDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $exitPageDataProviderMock->expects($this->once())->method('getExitPageDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $exitPageDataProviderMock->getExitPageData('', '', $filterRepo));

        $exitPageDataProviderMock->expects($this->once())->method('getExitPageDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $exitPageDataProviderMock->getExitPageData('', '', new FilterRepository()));
    }
}
