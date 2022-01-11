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

namespace Waldhacker\Plausibleio\Tests\Unit\Services;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Services\LocationCodeService;

class LocationCodeServiceTest extends UnitTestCase
{
    public function codeToCityDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'existing locationId returns related dataset' => [
            'locationId' => 2987642,
            'expected' => ['locationId' => '2987642', 'code' => 'Petit-Noir']
        ];

        yield 'non existing locationId returns null' => [
            'locationId' => 12,
            'expected' => null
        ];
    }

    /**
     *
     * @dataProvider codeToCityDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Services\LocationCodeService::codeToCityData
     */
    public function codeToCityDataReturnsProperValues(int $locationId, ?array $expected): void
    {
        /*
        $extensionManagementUtilityProphecy = $this->prophesize(ExtensionManagementUtility::class);
        $extensionManagementUtilityProphecy->extPath()->willReturn(__DIR__ . '');
        $subject = new LocationCodeService($extensionManagementUtilityProphecy);
        self::assertSame($expected, $subject->codeToCityData($locationId));
        */
    }
}
