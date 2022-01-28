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
use Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class SourceDataProviderTest extends UnitTestCase
{
    private ObjectProphecy $languageServiceProphecy;

    use ProphecyTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $this->languageServiceProphecy->reveal();
    }

    public function getAllSourcesDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'allSourcesDataWithGoal' => [
                'data' => [
                    ['source' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['source' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
            ],
            'allSourcesDataWithoutGoal' => [
                'data' => [
                    ['source' => 'source1', 'visitors' => 8, 'percentage' => 25.0],
                    ['source' => 'source2', 'visitors' => 48, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'source',
                        'label' => 'Source',
                        'filter' => [
                            'name' => 'visit:source',
                            'label' => 'Source is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['source' => 'source1', 'visitors' => 4, 'percentage' => 25.0, 'cr' => '50%'],
                    ['source' => 'source2', 'visitors' => 12, 'percentage' => 75.0, 'cr' => '25%'],
                ],
                'columns' => [
                    [
                        'name' => 'source',
                        'label' => 'Source',
                        'filter' => [
                            'name' => 'visit:source',
                            'label' => 'Source is',
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
     * @dataProvider getAllSourcesDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getAllSourcesDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getAllSourcesDataWithGoalReturnsProperValues(
        ?array $allSourcesDataWithGoal,
        ?array $allSourcesDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getAllSourcesDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getAllSourcesDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($allSourcesDataWithGoal, $allSourcesDataWithoutGoal);
        self::assertSame($expected, $subject->getAllSourcesDataWithGoal('', '', new FilterRepository()));
    }

    public function getAllSourcesDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['source' => 'source1', 'visitors' => 4],
                ['source' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['source' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['source' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'source',
                        'label' => 'Source',
                        'filter' => [
                            'name' => 'visit:source',
                            'label' => 'Source is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'all items are transformed with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [['name' => FilterRepository::FILTERVISITSOURCE, 'value' => 'waldhacker.dev']],
            'endpointData' => [
                ['source' => 'source1', 'visitors' => 4],
                ['source' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['source' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['source' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'source',
                        'label' => 'Source',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'items without source are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['source' => 'source1', 'visitors' => 4],
                ['source' => '', 'visitors' => 12],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['source' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['source' => '', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'source',
                        'label' => 'Source',
                        'filter' => [
                            'name' => 'visit:source',
                            'label' => 'Source is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['source' => 'source1', 'visitors' => 4],
                ['source' => 'source2', 'visitors' => null],
                ['source' => 'source2'],
            ],
            'expected' => [
                'data' => [
                    ['source' => 'source1', 'visitors' => 4, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'source',
                        'label' => 'Source',
                        'filter' => [
                            'name' => 'visit:source',
                            'label' => 'Source is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getAllSourcesDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getAllSourcesDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers       \Waldhacker\Plausibleio\Filter::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     */
    public function getAllSourcesDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.source')->willReturn('Source');
        $this->languageServiceProphecy->getLL('filter.sourceData.sourceIs')->willReturn('Source is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:source',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getAllSourcesDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getAllSourcesData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getAllSourcesDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $sourceDataProviderMock = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getAllSourcesDataWithoutGoal', 'getAllSourcesDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $sourceDataProviderMock->expects($this->once())->method('getAllSourcesDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $sourceDataProviderMock->getAllSourcesData('', '', $filterRepo));

        $sourceDataProviderMock->expects($this->once())->method('getAllSourcesDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $sourceDataProviderMock->getAllSourcesData('', '', new FilterRepository()));
    }

    public function getMediumDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'mediumDataWithGoal' => [
                'data' => [
                    ['utm_medium' => 'medium_ABC', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_medium' => 'medium_CFG', 'visitors' => 12, 'percentage' => 75.0],
                ],
            ],
            'mediumDataWithoutGoal' => [
                'data' => [
                    ['utm_medium' => 'medium_ABC', 'visitors' => 8, 'percentage' => 25.0],
                    ['utm_medium' => 'medium_CFG', 'visitors' => 48, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_medium',
                        'label' => 'UTM Medium',
                        'filter' => [
                            'name' => 'visit:utm_medium',
                            'label' => 'UTM Medium is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['utm_medium' => 'medium_ABC', 'visitors' => 4, 'percentage' => 25.0, 'cr' => '50%'],
                    ['utm_medium' => 'medium_CFG', 'visitors' => 12, 'percentage' => 75.0, 'cr' => '25%'],
                ],
                'columns' => [
                    [
                        'name' => 'utm_medium',
                        'label' => 'UTM Medium',
                        'filter' => [
                            'name' => 'visit:utm_medium',
                            'label' => 'UTM Medium is',
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
     * @dataProvider getMediumDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getMediumDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getMediumDataWithGoalReturnsProperValues(
        ?array $mediumDataWithGoal,
        ?array $mediumDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getMediumDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getMediumDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($mediumDataWithGoal, $mediumDataWithoutGoal);
        self::assertSame($expected, $subject->getMediumDataWithGoal('', '', new FilterRepository()));
    }

    public function getMediumDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_medium' => 'source1', 'visitors' => 4],
                ['utm_medium' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['utm_medium' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_medium' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_medium',
                        'label' => 'UTM Medium',
                        'filter' => [
                            'name' => 'visit:utm_medium',
                            'label' => 'UTM Medium is',
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
            'filters' => [['name' => FilterRepository::FILTERVISITUTMMEDIUM, 'value' => 'waldhacker . dev']],
            'endpointData' => [
                ['utm_medium' => 'source1', 'visitors' => 4],
                ['utm_medium' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['utm_medium' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_medium' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_medium',
                        'label' => 'UTM Medium',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without utm_medium are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_medium' => 'source1', 'visitors' => 4],
                ['utm_medium' => '', 'visitors' => 12],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['utm_medium' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_medium' => '', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_medium',
                        'label' => 'UTM Medium',
                        'filter' => [
                            'name' => 'visit:utm_medium',
                            'label' => 'UTM Medium is',
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
                ['utm_medium' => 'source1', 'visitors' => 33],
                ['utm_medium' => 'source2', 'visitors' => null],
                ['utm_medium' => 'source2'],
            ],
            'expected' => [
                'data' => [
                    ['utm_medium' => 'source1', 'visitors' => 33, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'utm_medium',
                        'label' => 'UTM Medium',
                        'filter' => [
                            'name' => 'visit:utm_medium',
                            'label' => 'UTM Medium is',
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
     * @dataProvider getMediumDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getMediumDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers       \Waldhacker\Plausibleio\Filter::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     */
    public function getMediumDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.UTMMedium')->willReturn('UTM Medium');
        $this->languageServiceProphecy->getLL('filter.sourceData.UTMMediumIs')->willReturn('UTM Medium is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:utm_medium',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getMediumDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getMediumData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getMediumDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $sourceDataProviderMock = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getMediumDataWithoutGoal', 'getMediumDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $sourceDataProviderMock->expects($this->once())->method('getMediumDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $sourceDataProviderMock->getMediumData('', '', $filterRepo));

        $sourceDataProviderMock->expects($this->once())->method('getMediumDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $sourceDataProviderMock->getMediumData('', '', new FilterRepository()));
    }

    public function getSourceDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'sourceDataWithGoal' => [
                'data' => [
                    ['utm_source' => 'source_ABC', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_source' => 'source_CFG', 'visitors' => 12, 'percentage' => 75.0],
                ],
            ],
            'sourceDataWithoutGoal' => [
                'data' => [
                    ['utm_source' => 'source_ABC', 'visitors' => 8, 'percentage' => 25.0],
                    ['utm_source' => 'source_CFG', 'visitors' => 48, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_source',
                        'label' => 'UTM Source',
                        'filter' => [
                            'name' => 'visit:utm_source',
                            'label' => 'UTM Source is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['utm_source' => 'source_ABC', 'visitors' => 4, 'percentage' => 25.0, 'cr' => '50%'],
                    ['utm_source' => 'source_CFG', 'visitors' => 12, 'percentage' => 75.0, 'cr' => '25%'],
                ],
                'columns' => [
                    [
                        'name' => 'utm_source',
                        'label' => 'UTM Source',
                        'filter' => [
                            'name' => 'visit:utm_source',
                            'label' => 'UTM Source is',
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
     * @dataProvider getSourceDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getSourceDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getSourceDataWithGoalReturnsProperValues(
        ?array $sourceDataWithGoal,
        ?array $sourceDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getSourceDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getSourceDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($sourceDataWithGoal, $sourceDataWithoutGoal);
        self::assertSame($expected, $subject->getSourceDataWithGoal('', '', new FilterRepository()));
    }

    public function getSourceDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_source' => 'source1', 'visitors' => 12],
                ['utm_source' => 'source2', 'visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['utm_source' => 'source1', 'visitors' => 12, 'percentage' => 75.0],
                    ['utm_source' => 'source2', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_source',
                        'label' => 'UTM Source',
                        'filter' => [
                            'name' => 'visit:utm_source',
                            'label' => 'UTM Source is',
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
            'filters' => [['name' => 'visit:utm_source', 'value' => 'waldhacker . dev']],
            'endpointData' => [
                ['utm_source' => 'source1', 'visitors' => 12],
                ['utm_source' => 'source2', 'visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['utm_source' => 'source1', 'visitors' => 12, 'percentage' => 75.0],
                    ['utm_source' => 'source2', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_source',
                        'label' => 'UTM Source',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without utm_source are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_source' => 'source1', 'visitors' => 12],
                ['utm_source' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['utm_source' => 'source1', 'visitors' => 12, 'percentage' => 75.0],
                    ['utm_source' => '', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_source',
                        'label' => 'UTM Source',
                        'filter' => [
                            'name' => 'visit:utm_source',
                            'label' => 'UTM Source is',
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
                ['utm_source' => 'source1', 'visitors' => 33],
                ['utm_source' => 'source2', 'visitors' => null],
                ['utm_source' => 'source2'],
            ],
            'expected' => [
                'data' => [
                    ['utm_source' => 'source1', 'visitors' => 33, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'utm_source',
                        'label' => 'UTM Source',
                        'filter' => [
                            'name' => 'visit:utm_source',
                            'label' => 'UTM Source is',
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
     * @dataProvider getSourceDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getSourceDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers       \Waldhacker\Plausibleio\Filter::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     */
    public function getSourceDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.UTMSource')->willReturn('UTM Source');
        $this->languageServiceProphecy->getLL('filter.sourceData.UTMSourceIs')->willReturn('UTM Source is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:utm_source',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getSourceDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getSourceData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getSourceDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $sourceDataProviderMock = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getSourceDataWithoutGoal', 'getSourceDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $sourceDataProviderMock->expects($this->once())->method('getSourceDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $sourceDataProviderMock->getSourceData('', '', $filterRepo));

        $sourceDataProviderMock->expects($this->once())->method('getSourceDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $sourceDataProviderMock->getSourceData('', '', new FilterRepository()));
    }

    public function getCampaignDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'campaignDataWithGoal' => [
                'data' => [
                    ['utm_campaign' => 'campaign_ABC', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_campaign' => 'campaign_CFG', 'visitors' => 12, 'percentage' => 75.0],
                ],
            ],
            'campaignDataWithoutGoal' => [
                'data' => [
                    ['utm_campaign' => 'campaign_ABC', 'visitors' => 8, 'percentage' => 25.0],
                    ['utm_campaign' => 'campaign_CFG', 'visitors' => 48, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_campaign',
                        'label' => 'UTM Campaign',
                        'filter' => [
                            'name' => 'visit:utm_campaign',
                            'label' => 'UTM Campaign is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['utm_campaign' => 'campaign_ABC', 'visitors' => 4, 'percentage' => 25.0, 'cr' => '50%'],
                    ['utm_campaign' => 'campaign_CFG', 'visitors' => 12, 'percentage' => 75.0, 'cr' => '25%'],
                ],
                'columns' => [
                    [
                        'name' => 'utm_campaign',
                        'label' => 'UTM Campaign',
                        'filter' => [
                            'name' => 'visit:utm_campaign',
                            'label' => 'UTM Campaign is',
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
     * @dataProvider getCampaignDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getCampaignDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getCampaignDataWithGoalReturnsProperValues(
        ?array $campaignDataWithGoal,
        ?array $campaignDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getCampaignDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getCampaignDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($campaignDataWithGoal, $campaignDataWithoutGoal);
        self::assertSame($expected, $subject->getCampaignDataWithGoal('', '', new FilterRepository()));
    }

    public function getCampaignDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_campaign' => 'source1', 'visitors' => 4],
                ['utm_campaign' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['utm_campaign' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_campaign' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_campaign',
                        'label' => 'UTM Campaign',
                        'filter' => [
                            'name' => 'visit:utm_campaign',
                            'label' => 'UTM Campaign is',
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
            'filters' => [['name' => 'visit:utm_campaign', 'value' => 'waldhacker . dev']],
            'endpointData' => [
                ['utm_campaign' => 'source1', 'visitors' => 4],
                ['utm_campaign' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['utm_campaign' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_campaign' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_campaign',
                        'label' => 'UTM Campaign',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without utm_campaign are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_campaign' => 'source1', 'visitors' => 12],
                ['utm_campaign' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['utm_campaign' => 'source1', 'visitors' => 12, 'percentage' => 75.0],
                    ['utm_campaign' => '', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_campaign',
                        'label' => 'UTM Campaign',
                        'filter' => [
                            'name' => 'visit:utm_campaign',
                            'label' => 'UTM Campaign is',
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
                ['utm_campaign' => 'source1', 'visitors' => 3],
                ['utm_campaign' => 'source2', 'visitors' => null],
                ['utm_campaign' => 'source2'],
            ],
            'expected' => [
                'data' => [
                    ['utm_campaign' => 'source1', 'visitors' => 3, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'utm_campaign',
                        'label' => 'UTM Campaign',
                        'filter' => [
                            'name' => 'visit:utm_campaign',
                            'label' => 'UTM Campaign is',
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
     * @dataProvider getCampaignDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getCampaignDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers       \Waldhacker\Plausibleio\Filter::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     */
    public function getCampaignDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.UTMCampaign')->willReturn('UTM Campaign');
        $this->languageServiceProphecy->getLL('filter.sourceData.UTMCampaignIs')->willReturn('UTM Campaign is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:utm_campaign',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getCampaignDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getCampaignData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getCampaignDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $sourceDataProviderMock = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getCampaignDataWithoutGoal', 'getCampaignDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $sourceDataProviderMock->expects($this->once())->method('getCampaignDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $sourceDataProviderMock->getCampaignData('', '', $filterRepo));

        $sourceDataProviderMock->expects($this->once())->method('getCampaignDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $sourceDataProviderMock->getCampaignData('', '', new FilterRepository()));
    }

    public function getTermDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'campaignDataWithGoal' => [
                'data' => [
                    ['utm_term' => 'term_ABC', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_term' => 'term_CFG', 'visitors' => 12, 'percentage' => 75.0],
                ],
            ],
            'campaignDataWithoutGoal' => [
                'data' => [
                    ['utm_term' => 'term_ABC', 'visitors' => 8, 'percentage' => 25.0],
                    ['utm_term' => 'term_CFG', 'visitors' => 48, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_term',
                        'label' => 'UTM Term',
                        'filter' => [
                            'name' => 'visit:utm_term',
                            'label' => 'UTM Term is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['utm_term' => 'term_ABC', 'visitors' => 4, 'percentage' => 25.0, 'cr' => '50%'],
                    ['utm_term' => 'term_CFG', 'visitors' => 12, 'percentage' => 75.0, 'cr' => '25%'],
                ],
                'columns' => [
                    [
                        'name' => 'utm_term',
                        'label' => 'UTM Term',
                        'filter' => [
                            'name' => 'visit:utm_term',
                            'label' => 'UTM Term is',
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
     * @dataProvider getTermDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getTermDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getTermDataWithGoalReturnsProperValues(
        ?array $termDataWithGoal,
        ?array $termDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getTermDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getTermDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($termDataWithGoal, $termDataWithoutGoal);
        self::assertSame($expected, $subject->getTermDataWithGoal('', '', new FilterRepository()));
    }

    public function getTermDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_term' => 'source1', 'visitors' => 4],
                ['utm_term' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['utm_term' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_term' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_term',
                        'label' => 'UTM Term',
                        'filter' => [
                            'name' => 'visit:utm_term',
                            'label' => 'UTM Term is',
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
            'filters' => [['name' => 'visit:utm_term', 'value' => 'waldhacker . dev']],
            'endpointData' => [
                ['utm_term' => 'source1', 'visitors' => 4],
                ['utm_term' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['utm_term' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_term' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_term',
                        'label' => 'UTM Term',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without utm_campaign are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_term' => 'source1', 'visitors' => 12],
                ['utm_term' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['utm_term' => 'source1', 'visitors' => 12, 'percentage' => 75.0],
                    ['utm_term' => '', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_term',
                        'label' => 'UTM Term',
                        'filter' => [
                            'name' => 'visit:utm_term',
                            'label' => 'UTM Term is',
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
                ['utm_term' => 'source1', 'visitors' => 3],
                ['utm_term' => 'source2', 'visitors' => null],
                ['utm_term' => 'source2'],
            ],
            'expected' => [
                'data' => [
                    ['utm_term' => 'source1', 'visitors' => 3, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'utm_term',
                        'label' => 'UTM Term',
                        'filter' => [
                            'name' => 'visit:utm_term',
                            'label' => 'UTM Term is',
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
     * @dataProvider getTermDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getTermDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers       \Waldhacker\Plausibleio\Filter::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     */
    public function getTermDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.UTMTerm')->willReturn('UTM Term');
        $this->languageServiceProphecy->getLL('filter.sourceData.UTMTermIs')->willReturn('UTM Term is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:utm_term',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getTermDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getTermData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getTermDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $sourceDataProviderMock = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getTermDataWithoutGoal', 'getTermDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $sourceDataProviderMock->expects($this->once())->method('getTermDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $sourceDataProviderMock->getTermData('', '', $filterRepo));

        $sourceDataProviderMock->expects($this->once())->method('getTermDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $sourceDataProviderMock->getTermData('', '', new FilterRepository()));
    }


    public function getContentDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'campaignDataWithGoal' => [
                'data' => [
                    ['utm_content' => 'content_ABC', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_content' => 'content_CFG', 'visitors' => 12, 'percentage' => 75.0],
                ],
            ],
            'campaignDataWithoutGoal' => [
                'data' => [
                    ['utm_content' => 'content_ABC', 'visitors' => 8, 'percentage' => 25.0],
                    ['utm_content' => 'content_CFG', 'visitors' => 48, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_content',
                        'label' => 'UTM Content',
                        'filter' => [
                            'name' => 'visit:utm_content',
                            'label' => 'UTM Content is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['utm_content' => 'content_ABC', 'visitors' => 4, 'percentage' => 25.0, 'cr' => '50%'],
                    ['utm_content' => 'content_CFG', 'visitors' => 12, 'percentage' => 75.0, 'cr' => '25%'],
                ],
                'columns' => [
                    [
                        'name' => 'utm_content',
                        'label' => 'UTM Content',
                        'filter' => [
                            'name' => 'visit:utm_content',
                            'label' => 'UTM Content is',
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
     * @dataProvider getContentDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getContentDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getContentDataWithGoalReturnsProperValues(
        ?array $contentDataWithGoal,
        ?array $contentDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getContentDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getContentDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($contentDataWithGoal, $contentDataWithoutGoal);
        self::assertSame($expected, $subject->getContentDataWithGoal('', '', new FilterRepository()));
    }

    public function getContentDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_content' => 'source1', 'visitors' => 4],
                ['utm_content' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['utm_content' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_content' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_content',
                        'label' => 'UTM Content',
                        'filter' => [
                            'name' => 'visit:utm_content',
                            'label' => 'UTM Content is',
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
            'filters' => [['name' => 'visit:utm_content', 'value' => 'waldhacker . dev']],
            'endpointData' => [
                ['utm_content' => 'source1', 'visitors' => 4],
                ['utm_content' => 'source2', 'visitors' => 12],
            ],
            'expected' => [
                'data' => [
                    ['utm_content' => 'source1', 'visitors' => 4, 'percentage' => 25.0],
                    ['utm_content' => 'source2', 'visitors' => 12, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_content',
                        'label' => 'UTM Content',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without utm_campaign are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['utm_content' => 'source1', 'visitors' => 12],
                ['utm_content' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['utm_content' => 'source1', 'visitors' => 12, 'percentage' => 75.0],
                    ['utm_content' => '', 'visitors' => 4, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'utm_content',
                        'label' => 'UTM Content',
                        'filter' => [
                            'name' => 'visit:utm_content',
                            'label' => 'UTM Content is',
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
                ['utm_content' => 'source1', 'visitors' => 3],
                ['utm_content' => 'source2', 'visitors' => null],
                ['utm_content' => 'source2'],
            ],
            'expected' => [
                'data' => [
                    ['utm_content' => 'source1', 'visitors' => 3, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'utm_content',
                        'label' => 'UTM Content',
                        'filter' => [
                            'name' => 'visit:utm_content',
                            'label' => 'UTM Content is',
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
     * @dataProvider getContentDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getContentDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers       \Waldhacker\Plausibleio\Filter::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
 */
    public function getContentDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.UTMContent')->willReturn('UTM Content');
        $this->languageServiceProphecy->getLL('filter.sourceData.UTMContentIs')->willReturn('UTM Content is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:utm_content',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getContentDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getContentData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getContentDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $sourceDataProviderMock = $this->getMockBuilder(SourceDataProvider::class)
            ->onlyMethods(['getContentDataWithoutGoal', 'getContentDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $sourceDataProviderMock->expects($this->once())->method('getContentDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $sourceDataProviderMock->getContentData('', '', $filterRepo));

        $sourceDataProviderMock->expects($this->once())->method('getContentDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $sourceDataProviderMock->getContentData('', '', new FilterRepository()));
    }
}
