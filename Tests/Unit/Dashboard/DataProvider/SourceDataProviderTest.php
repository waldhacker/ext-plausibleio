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

    public function getAllSourcesDataReturnsProperValuesDataProvider(): \Generator
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
            'filters' => [['name' => 'visit:source', 'value' => 'waldhacker.dev']],
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
     * @dataProvider getAllSourcesDataReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getAllSourcesData
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
    public function getAllSourcesDataReturnsProperValues(
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
        self::assertSame($expected, $subject->getAllSourcesData($plausibleSiteId, $timeFrame, $filterRepo));
    }

    public function getMediumDataReturnsProperValuesDataProvider(): \Generator
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
            'filters' => [['name' => 'visit:utm_medium', 'value' => 'waldhacker . dev']],
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
     * @dataProvider getMediumDataReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getMediumData
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
    public function getMediumDataReturnsProperValues(
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
        self::assertSame($expected, $subject->getMediumData($plausibleSiteId, $timeFrame, $filterRepo));
    }

    public function getSourceDataReturnsProperValuesDataProvider(): \Generator
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
     * @dataProvider getSourceDataReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getSourceData
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
    public function getSourceDataReturnsProperValues(
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
        self::assertSame($expected, $subject->getSourceData($plausibleSiteId, $timeFrame, $filterRepo));
    }

    public function getCampaignDataReturnsProperValuesDataProvider(): \Generator
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
     * @dataProvider getCampaignDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getCampaignData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers       \Waldhacker\Plausibleio\Filter::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::count
     * @covers       \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     */
    public function getCampaignDataReturnsProperValues(
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
        self::assertSame($expected, $subject->getCampaignData($plausibleSiteId, $timeFrame, $filterRepo));
    }

    public function getTermDataReturnsProperValuesDataProvider(): \Generator
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
     * @dataProvider getTermDataReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getTermData
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
    public function getTermDataReturnsProperValues(
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
        self::assertSame($expected, $subject->getTermData($plausibleSiteId, $timeFrame, $filterRepo));
    }

    public function getContentDataReturnsProperValuesDataProvider(): \Generator
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
     * @dataProvider getContentDataReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getContentData
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
    public function getContentDataReturnsProperValues(
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
        self::assertSame($expected, $subject->getContentData($plausibleSiteId, $timeFrame, $filterRepo));
    }
}
