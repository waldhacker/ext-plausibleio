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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Filter;
use Waldhacker\Plausibleio\FilterRepository;
use Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class DeviceDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    private ObjectProphecy $languageServiceProphecy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $this->languageServiceProphecy->reveal();
    }

    public function getBrowserDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'browserDataWithGoal' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 12],
                    ['browser' => 'Chrome', 'visitors' => 8],
                ],
            ],
            'browserDataWithoutGoal' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 48],
                    ['browser' => 'Chrome', 'visitors' => 16],
                ],
                'columns' => [
                    [
                        'name' => 'browser',
                        'label' => 'Browser',
                        'filter' => [
                            'name' => 'visit:browser',
                            'label' => 'Browser is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 12, 'cr' => '25%'],
                    ['browser' => 'Chrome', 'visitors' => 8, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'browser',
                        'label' => 'Browser',
                        'filter' => [
                            'name' => 'visit:browser',
                            'label' => 'Browser is',
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
     * @dataProvider getBrowserDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getBrowserDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getBrowserDataWithGoalReturnsProperValues(
        ?array $browserDataWithGoal,
        ?array $browserDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(DeviceDataProvider::class)
            ->onlyMethods(['getBrowserDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getBrowserDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($browserDataWithGoal, $browserDataWithoutGoal);
        self::assertSame($expected, $subject->getBrowserDataWithGoal('', '', new FilterRepository()));
    }

    public function getBrowserDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['browser' => 'Firefox', 'visitors' => 12],
                ['browser' => 'Chrome', 'visitors' => 8],
            ],
            'expected' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 12, 'percentage' => 60.0],
                    ['browser' => 'Chrome', 'visitors' => 8, 'percentage' => 40.0],
                ],
                'columns' => [
                    [
                        'name' => 'browser',
                        'label' => 'Browser',
                        'filter' => [
                            'name' => 'visit:browser',
                            'label' => 'Browser is',
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
            'filters' => [
                ['name' => 'visit:browser', 'value' => 'firefox'],
            ],
            'endpointData' => [
                ['browser_version' => '48.0', 'visitors' => 12],
                ['browser_version' => '46.0', 'visitors' => 8],
            ],
            'expected' => [
                'data' => [
                    ['browser_version' => '48.0', 'visitors' => 12, 'percentage' => 60.0],
                    ['browser_version' => '46.0', 'visitors' => 8, 'percentage' => 40.0],
                ],
                'columns' => [
                    [
                        'name' => 'browser_version',
                        'label' => '${browser} version',
                        'filter' => [
                            'name' => 'visit:browser_version',
                            'label' => '${browser} version is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without browser are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['browser' => 'Firefox', 'visitors' => 8],
                ['browser' => '', 'visitors' => 12],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 8, 'percentage' => 40.0],
                    ['browser' => '', 'visitors' => 12, 'percentage' => 60.0],
                ],
                'columns' => [
                    [
                        'name' => 'browser',
                        'label' => 'Browser',
                        'filter' => [
                            'name' => 'visit:browser',
                            'label' => 'Browser is',
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
                ['browser' => 'Firefox', 'visitors' => 99],
                ['browser' => 'Chrome', 'visitors' => null],
                ['browser' => 'Chrome'],
            ],
            'expected' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 99, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'browser',
                        'label' => 'Browser',
                        'filter' => [
                            'name' => 'visit:browser',
                            'label' => 'Browser is',
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
     * @dataProvider getBrowserDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getBrowserDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
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
    public function getBrowserDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.browser')->willReturn('Browser');
        $this->languageServiceProphecy->getLL('barChart.labels.browserVersion')->willReturn('${browser} version');
        $this->languageServiceProphecy->getLL('filter.deviceData.browserIs')->willReturn('Browser is');
        $this->languageServiceProphecy->getLL('filter.deviceData.browserVersionIs')->willReturn('${browser} version is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $filterRepo->isFilterActivated(FilterRepository::FILTERVISITBROWSER) ? 'visit:browser_version' : 'visit:browser',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new DeviceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getBrowserDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getBrowserData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getBrowserDataCallsMethodsCorrect(): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);

        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $DeviceDataProviderMock = $this->getMockBuilder(DeviceDataProvider::class)
            ->onlyMethods(['getBrowserDataWithoutGoal', 'getBrowserDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $DeviceDataProviderMock->expects($this->once())->method('getBrowserDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $DeviceDataProviderMock->getBrowserData('', '', $filterRepo));

        $DeviceDataProviderMock->expects($this->once())->method('getBrowserDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $DeviceDataProviderMock->getBrowserData('', '', new FilterRepository()));
    }

    public function getOSDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'osDataWithGoal' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 12],
                    ['os' => 'Ubuntu', 'visitors' => 8],
                ],
            ],
            'osDataWithoutGoal' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 48],
                    ['os' => 'Ubuntu', 'visitors' => 16],
                ],
                'columns' => [
                    [
                        'name' => 'os',
                        'label' => 'Operating system',
                        'filter' => [
                            'name' => 'visit:os',
                            'label' => 'Operating system is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 12, 'cr' => '25%'],
                    ['os' => 'Ubuntu', 'visitors' => 8, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'os',
                        'label' => 'Operating system',
                        'filter' => [
                            'name' => 'visit:os',
                            'label' => 'Operating system is',
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
     * @dataProvider getOSDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getOSDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getOSDataWithGoalReturnsProperValues(
        ?array $osDataWithGoal,
        ?array $osDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(DeviceDataProvider::class)
            ->onlyMethods(['getOSDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getOSDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($osDataWithGoal, $osDataWithoutGoal);
        self::assertSame($expected, $subject->getOSDataWithGoal('', '', new FilterRepository()));
    }

    public function getOSDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['os' => 'Windows', 'visitors' => 32],
                ['os' => 'Linux', 'visitors' => 48],
            ],
            'expected' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 32, 'percentage' => 40.0],
                    ['os' => 'Linux', 'visitors' => 48, 'percentage' => 60.0],
                ],
                'columns' => [
                    [
                        'name' => 'os',
                        'label' => 'Operating system',
                        'filter' => [
                            'name' => 'visit:os',
                            'label' => 'Operating system is',
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
            'filters' => [
                ['name' => 'visit:os', 'value' => 'Mac'],
            ],
            'endpointData' => [
                ['os_version' => '10.15', 'visitors' => 32],
                ['os_version' => '10.11', 'visitors' => 48],
            ],
            'expected' => [
                'data' => [
                    ['os_version' => '10.15', 'visitors' => 32, 'percentage' => 40.0],
                    ['os_version' => '10.11', 'visitors' => 48, 'percentage' => 60.0],
                ],
                'columns' => [
                    [
                        'name' => 'os_version',
                        'label' => '${os} version',
                        'filter' => [
                            'name' => 'visit:os_version',
                            'label' => '${os} version is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without os are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['os' => 'Windows', 'visitors' => 5],
                ['os' => '', 'visitors' => 15],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 5, 'percentage' => 25.0],
                    ['os' => '', 'visitors' => 15, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'os',
                        'label' => 'Operating system',
                        'filter' => [
                            'name' => 'visit:os',
                            'label' => 'Operating system is',
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
                ['os' => 'Windows', 'visitors' => 3],
                ['os' => 'Linux', 'visitors' => null],
                ['os' => 'Linux'],
            ],
            'expected' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 3, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'os',
                        'label' => 'Operating system',
                        'filter' => [
                            'name' => 'visit:os',
                            'label' => 'Operating system is',
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
     * @dataProvider getOSDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getOSDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
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
    public function getOSDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.os')->willReturn('Operating system');
        $this->languageServiceProphecy->getLL('barChart.labels.osVersion')->willReturn('${os} version');
        $this->languageServiceProphecy->getLL('filter.deviceData.osIs')->willReturn('Operating system is');
        $this->languageServiceProphecy->getLL('filter.deviceData.osVersionIs')->willReturn('${os} version is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => !$filterRepo->empty() ? 'visit:os_version' : 'visit:os',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filters[0]['name'] . '==' . $filters[0]['value'];
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new DeviceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getOSDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getOSData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
    */
    public function getOSDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);

        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $DeviceDataProviderMock = $this->getMockBuilder(DeviceDataProvider::class)
            ->onlyMethods(['getOSDataWithoutGoal', 'getOSDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $DeviceDataProviderMock->expects($this->once())->method('getOSDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $DeviceDataProviderMock->getOSDataWithGoal('', '', $filterRepo));

        $DeviceDataProviderMock->expects($this->once())->method('getOSDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $DeviceDataProviderMock->getOSDataWithoutGoal('', '', new FilterRepository()));
    }

    public function getDeviceDataWithGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'browserDataWithGoal' => [
                'data' => [
                    ['device' => 'Tablet', 'visitors' => 12],
                    ['device' => 'Desktop', 'visitors' => 8],
                ],
            ],
            'browserDataWithoutGoal' => [
                'data' => [
                    ['device' => 'Tablet', 'visitors' => 48],
                    ['device' => 'Desktop', 'visitors' => 16],
                ],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size',
                        'filter' => [
                            'name' => 'visit:device',
                            'label' => 'Screen Size is',
                        ],
                    ],
                    ['name' => 'visitors', 'label' => 'Visitors'],
                ],
            ],
            'expected' => [
                'data' => [
                    ['device' => 'Tablet', 'visitors' => 12, 'cr' => '25%'],
                    ['device' => 'Desktop', 'visitors' => 8, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size',
                        'filter' => [
                            'name' => 'visit:device',
                            'label' => 'Screen Size is',
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
     * @dataProvider getDeviceDataWithGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getDeviceDataWithGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers       \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers       \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     */
    public function getDeviceDataWithGoalReturnsProperValues(
        ?array $deviceDataWithGoal,
        ?array $deviceDataWithoutGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.conversions')->willReturn('Conversions');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $subject = $this->getMockBuilder(DeviceDataProvider::class)
            ->onlyMethods(['getDeviceDataWithoutGoal'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();
        $subject->method('getDeviceDataWithoutGoal')
            ->willReturnOnConsecutiveCalls($deviceDataWithGoal, $deviceDataWithoutGoal);

        self::assertSame($expected, $subject->getDeviceDataWithGoal('', '', new FilterRepository()));
    }

    public function getDeviceDataWithoutGoalReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['device' => 'Tablet', 'visitors' => 3],
                ['device' => 'Desktop', 'visitors' => 9],
            ],
            'expected' => [
                'data' => [
                    ['device' => 'Tablet', 'visitors' => 3, 'percentage' => 25.0],
                    ['device' => 'Desktop', 'visitors' => 9, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size',
                        'filter' => [
                            'name' => 'visit:device',
                            'label' => 'Screen size is',
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
            'filters' => [
                ['name' => 'visit:device', 'value' => 'Desktop'],
            ],
            'endpointData' => [
                ['device' => 'Desktop', 'visitors' => 3],
                ['device' => 'Desktop', 'visitors' => 9],
            ],
            'expected' => [
                'data' => [
                    ['device' => 'Desktop', 'visitors' => 3, 'percentage' => 25.0],
                    ['device' => 'Desktop', 'visitors' => 9, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size',
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];

        yield 'items without device are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['device' => 'Tablet', 'visitors' => 9],
                ['device' => '', 'visitors' => 3],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['device' => 'Tablet', 'visitors' => 9, 'percentage' => 75.0],
                    ['device' => '', 'visitors' => 3, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size',
                        'filter' => [
                            'name' => 'visit:device',
                            'label' => 'Screen size is',
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
                ['device' => 'Tablet', 'visitors' => 3],
                ['device' => 'Desktop', 'visitors' => null],
                ['device' => 'Desktop'],
            ],
            'expected' => [
                'data' => [['device' => 'Tablet', 'visitors' => 3, 'percentage' => 100]],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size',
                        'filter' => [
                            'name' => 'visit:device',
                            'label' => 'Screen size is',
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
     * @dataProvider getDeviceDataWithoutGoalReturnsProperValuesDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getDeviceDataWithoutGoal
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
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
    public function getDeviceDataWithoutGoalReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.screenSize')->willReturn('Screen Size');
        $this->languageServiceProphecy->getLL('filter.deviceData.screenSizeIs')->willReturn('Screen size is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'visit:device',
            'metrics' => 'visitors',
        ];
        if (!$filterRepo->empty()) {
            $authorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $authorizedRequestParams,)->willReturn($endpointData);

        $subject = new DeviceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getDeviceDataWithoutGoal($plausibleSiteId, $timeFrame, $filterRepo));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getDeviceData
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getDeviceDataCallsMethodsCorrect(): void
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);

        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $DeviceDataProviderMock = $this->getMockBuilder(DeviceDataProvider::class)
            ->onlyMethods(['getDeviceDataWithoutGoal', 'getDeviceDataWithGoal'])
            ->setConstructorArgs([
                $plausibleServiceProphecy->reveal(),
            ])
            ->getMock();

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, 'path'));

        $DeviceDataProviderMock->expects($this->once())->method('getDeviceDataWithGoal')->willReturn(['overviewWithGoalData']);
        self::assertSame(['overviewWithGoalData'], $DeviceDataProviderMock->getDeviceDataWithGoal('', '', $filterRepo));

        $DeviceDataProviderMock->expects($this->once())->method('getDeviceDataWithoutGoal')->willReturn(['overviewWithoutGoalData']);
        self::assertSame(['overviewWithoutGoalData'], $DeviceDataProviderMock->getDeviceDataWithoutGoal('', '', new FilterRepository()));
    }
}
