<?php

declare(strict_types = 1);

namespace Waldhacker\Plausibleio\Tests\Unit\Dashboard\DataProvider;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;


class AbstractDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     */
    public function roundAdaptivePrecisionReturnsProperValue(): void
    {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);
        $subject = $this->getMockForAbstractClass(AbstractDataProvider::class, [$plausibleServiceProphecy->reveal()]);

        self::assertEquals(2, $subject->roundAdaptivePrecision(1.6));
        self::assertEquals(2, $subject->roundAdaptivePrecision(2.2));
        self::assertEquals(0.7, $subject->roundAdaptivePrecision(0.66));
        self::assertEquals(0.09, $subject->roundAdaptivePrecision(0.09348));
        self::assertEquals(0.09, $subject->roundAdaptivePrecision(0.09348));
        self::assertEquals(0.01, $subject->roundAdaptivePrecision(0.01));
        self::assertEquals(0, $subject->roundAdaptivePrecision(0.0069));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     */
    public function calcPercentageReturnsProperValue(): void
    {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);
        $subject = $this->getMockForAbstractClass(AbstractDataProvider::class, [$plausibleServiceProphecy->reveal()]);

        self::assertSame(
            [
                ['device' => 'Tablet', 'visitors' => 3, 'percentage' => 25.0],
                ['device' => 'Desktop', 'visitors' => 9, 'percentage' => 75.0],
            ],
            $subject->calcPercentage([
                ['device' => 'Tablet', 'visitors' => 3,],
                ['device' => 'Desktop', 'visitors' => 9,],
            ])
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRate
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     */
    public function calcConversionRateReturnsProperValue(): void
    {
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $requestFactoryInterfaceProphecy = $this->prophesize(RequestFactoryInterface::class);
        $clientInterfaceProphecy = $this->prophesize(ClientInterface::class);

        $plausibleServiceMock = $this->getMockBuilder(PlausibleService::class)
            ->onlyMethods(['sendAuthorizedRequest'])
            ->setConstructorArgs([
                $requestFactoryInterfaceProphecy->reveal(),
                $clientInterfaceProphecy->reveal(),
                $configurationServiceProphecy->reveal(),
            ])
            ->getMock();

        $plausibleServiceMock->expects($this->exactly(1))
            ->method('sendAuthorizedRequest')
            ->willReturnOnConsecutiveCalls(['visitors' => ['value' => 20]]);

        $subject = $this->getMockForAbstractClass(AbstractDataProvider::class, [$plausibleServiceMock]);

        self::assertSame(
            [
                ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5, 'cr' => '30%'],
                ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6, 'cr' => '50%'],
            ],

            $subject->calcConversionRate(
                'waldhacker.dev',
                '7d',
                [
                    ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5],
                    ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6],
                ]
            )
        );
    }

    public function calcConversionRateOnDataReturnsProperValueDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'browser',
            'browserDataWithoutGoal' => [
                ['browser' => 'IE', 'visitors' => 112],
                ['browser' => 'Safari', 'visitors' => 10],
                ['browser' => 'Firefox', 'visitors' => 48],
                ['browser' => 'Chrome', 'visitors' => 16],
            ],
            'browserDataWithGoal' => [
                ['browser' => 'Firefox', 'visitors' => 12],
                ['browser' => 'Chrome', 'visitors' => 8],
                ['browser' => 'Safari', 'visitors' => 0],
            ],
            'expected' => [
                ['browser' => 'Firefox', 'visitors' => 12, 'cr' => '25%'],
                ['browser' => 'Chrome', 'visitors' => 8, 'cr' => '50%'],
                ['browser' => 'Safari', 'visitors' => 0, 'cr' => '0%'],
            ],
        ];

        yield 'not existing names will skipped' => [
            'browser',
            'browserDataWithoutGoal' => [
                ['browser' => 'Safari', 'visitors' => 10],
                ['browser' => 'Firefox', 'visitors' => 48],
                ['browser' => 'IE', 'visitors' => 16],
            ],
            'browserDataWithGoal' => [
                ['browser' => 'Firefox', 'visitors' => 12],
                ['browser' => 'Chrome', 'visitors' => 8],
                ['browser' => 'Safari', 'visitors' => 0],
            ],
            'expected' => [
                ['browser' => 'Firefox', 'visitors' => 12, 'cr' => '25%'],
                ['browser' => 'Safari', 'visitors' => 0, 'cr' => '0%'],
            ],
        ];

        yield 'visitors on browserDataWithoutGoal with value 0 results in CR 0' => [
            'browser',
            'browserDataWithoutGoal' => [
                ['browser' => 'IE', 'visitors' => 112],
                ['browser' => 'Safari', 'visitors' => 10],
                ['browser' => 'Firefox', 'visitors' => 0],
                ['browser' => 'Chrome', 'visitors' => 16],
            ],
            'browserDataWithGoal' => [
                ['browser' => 'Firefox', 'visitors' => 12],
                ['browser' => 'Chrome', 'visitors' => 8],
                ['browser' => 'Safari', 'visitors' => 2],
            ],
            'expected' => [
                ['browser' => 'Firefox', 'visitors' => 12, 'cr' => '0%'],
                ['browser' => 'Chrome', 'visitors' => 8, 'cr' => '50%'],
                ['browser' => 'Safari', 'visitors' => 2, 'cr' => '20%'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calcConversionRateOnDataReturnsProperValueDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     */
    public function calcConversionRateOnDataReturnsProperValue(
        string $dataName,
        array $dataWithoutGoal,
        array $dataWithGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);
        $subject = $this->getMockForAbstractClass(AbstractDataProvider::class, [$plausibleServiceProphecy->reveal()]);

        self::assertSame($expected, $subject->calcConversionRateOnData($dataName, $dataWithoutGoal, $dataWithGoal));
    }

    public function calcConversionRateOnDataThrowsExceptionDataProvider(): \Generator
    {
        yield 'throw exception if name field in DataWithoutGoal is not set' => [
            'browser',
            'browserDataWithoutGoal' => [
                ['browser' => 'Firefox', 'visitors' => 48],
                ['visitors' => 16],
                ['browser' => 'Safari', 'visitors' => 10],
            ],
            'browserDataWithGoal' => [
                ['browser' => 'Firefox', 'visitors' => 12],
                ['browser' => 'Chrome', 'visitors' => 8],
                ['browser' => 'Safari', 'visitors' => 0],
            ],
            'expected' => [
                ['browser' => 'Firefox', 'visitors' => 12, 'cr' => '25%'],
                ['browser' => 'Safari', 'visitors' => 0, 'cr' => '0%'],
            ],
        ];

        yield 'throw exception if name field in DataWithGoal is not set' => [
            'browser',
            'browserDataWithoutGoal' => [
                ['browser' => 'Firefox', 'visitors' => 48],
                ['browser' => 'Chrome', 'visitors' => 16],
                ['browser' => 'Safari', 'visitors' => 10],
            ],
            'browserDataWithGoal' => [
                ['visitors' => 12],
                ['browser' => 'Chrome', 'visitors' => 8],
                ['browser' => 'Safari', 'visitors' => 0],
            ],
            'expected' => [
                ['browser' => 'Firefox', 'visitors' => 12, 'cr' => '25%'],
                ['browser' => 'Safari', 'visitors' => 0, 'cr' => '0%'],
            ],
        ];

        yield 'throw exception if visitors field in DataWithoutGoal is not set' => [
            'browser',
            'browserDataWithoutGoal' => [
                ['browser' => 'Firefox', 'visitors' => 48],
                ['browser' => 'Chrome'],
                ['browser' => 'Safari', 'visitors' => 10],
            ],
            'browserDataWithGoal' => [
                ['browser' => 'Firefox', 'visitors' => 12],
                ['browser' => 'Chrome', 'visitors' => 8],
                ['browser' => 'Safari', 'visitors' => 0],
            ],
            'expected' => [
                ['browser' => 'Firefox', 'visitors' => 12, 'cr' => '25%'],
                ['browser' => 'Safari', 'visitors' => 0, 'cr' => '0%'],
            ],
        ];

        yield 'throw exception if visitors field in DataWithGoal is not set' => [
            'browser',
            'browserDataWithoutGoal' => [
                ['browser' => 'Firefox', 'visitors' => 48],
                ['browser' => 'Chrome', 'visitors' => 16],
                ['browser' => 'Safari', 'visitors' => 10],
            ],
            'browserDataWithGoal' => [
                ['browser' => 'Firefox'],
                ['browser' => 'Chrome', 'visitors' => 8],
                ['browser' => 'Safari', 'visitors' => 0],
            ],
            'expected' => [
                ['browser' => 'Firefox', 'visitors' => 12, 'cr' => '25%'],
                ['browser' => 'Safari', 'visitors' => 0, 'cr' => '0%'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider calcConversionRateOnDataThrowsExceptionDataProvider
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRateOnData
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     */
    public function calcConversionRateOnDataThrowsException(
        string $dataName,
        array $dataWithoutGoal,
        array $dataWithGoal,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);
        $subject = $this->getMockForAbstractClass(AbstractDataProvider::class, [$plausibleServiceProphecy->reveal()]);

        // a filter without value was specified
        $this->expectException(MissingArrayPathException::class);
        self::assertSame($expected, $subject->calcConversionRateOnData($dataName, $dataWithoutGoal, $dataWithGoal));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     */
    public function dataCleanUpReturnsValidResult(): void
    {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);
        $subject = $this->getMockForAbstractClass(AbstractDataProvider::class, [$plausibleServiceProphecy->reveal()]);

        // all fields are correctly present
        self::assertSame(
            $subject->dataCleanUp(
                ['name', 'value'],
                [
                    ['name' => 'event:page', 'value' => 'page/site'],
                    ['name' => 'visit:browser_version', 'value' => '46.0', 'extra' => 'empty'],
                ]
            ),
            [
                ['name' => 'event:page', 'value' => 'page/site'],
                ['name' => 'visit:browser_version', 'value' => '46.0', 'extra' => 'empty'],
            ]
        );
        // a field is missing
        self::assertSame(
            $subject->dataCleanUp(
                ['name', 'value'],
                [
                    ['' => 'event:page', 'value' => 'page/site'],
                    ['name' => 'visit:browser_version', 'value' => '46.0'],
                ]
            ),
            [
                ['name' => 'visit:browser_version', 'value' => '46.0'],
            ]
        );
        // a field is empty
        self::assertSame(
            $subject->dataCleanUp(
                ['name', 'value'],
                [
                    ['name' => 'event:page', 'value' => ''],
                    ['name' => 'visit:browser_version', 'value' => '46.0'],
                ]
            ),
            [
                ['name' => 'event:page', 'value' => ''],
                ['name' => 'visit:browser_version', 'value' => '46.0'],
            ]
        );
        // a field is empty and strict clean up
        self::assertSame(
            $subject->dataCleanUp(
                ['name', 'value'],
                [
                    ['name' => 'event:page', 'value' => ''],
                    ['name' => 'visit:browser_version', 'value' => '46.0'],
                ],
                true
            ),
            [
                ['name' => 'visit:browser_version', 'value' => '46.0'],
            ],
        );
        // dataArray is empty
        self::assertSame(
            $subject->dataCleanUp(
                ['name', 'value'],
                []
            ),
            []
        );
        // mandatoryFields is empty
        self::assertSame(
            $subject->dataCleanUp(
                [],
                [
                    ['' => 'event:page', 'value' => 'page/site'],
                    ['name' => 'visit:browser_version', 'value' => '46.0'],
                ]
            ),
            [
                ['' => 'event:page', 'value' => 'page/site'],
                ['name' => 'visit:browser_version', 'value' => '46.0'],
            ]
        );
    }
}
