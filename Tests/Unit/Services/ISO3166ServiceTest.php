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

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Services\ISO3166Service;

class ISO3166ServiceTest extends UnitTestCase
{
    public function alpha2ReturnsProperValuesDataProvider(): \Generator
    {
        yield 'existing alpha2 returns related dataset' => [
            'input' => 'DE', 'expected' => ['name' => 'Germany', 'alpha2' => 'DE', 'alpha3' => 'DEU']
        ];

        yield 'non existing alpha2 returns null' => [
            'input' => '_', 'expected' => null
        ];
    }

    /**
     * @test
     * @dataProvider alpha2ReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Services\ISO3166Service::alpha2
     * @covers \Waldhacker\Plausibleio\Services\ISO3166Service::search
     */
    public function alpha2ReturnsProperValues(string $input, ?array $expected): void
    {
        $subject = new ISO3166Service();
        self::assertSame($expected, $subject->alpha2($input));
    }

    public function alpha3ReturnsProperValuesDataProvider(): \Generator
    {
        yield 'existing alpha3 returns related dataset' => [
            'input' => 'DEU', 'expected' => ['name' => 'Germany', 'alpha2' => 'DE', 'alpha3' => 'DEU']
        ];

        yield 'non existing alpha3 returns null' => [
            'input' => '_', 'expected' => null
        ];
    }

    /**
     * @test
     * @dataProvider alpha3ReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Services\ISO3166Service::alpha3
     * @covers \Waldhacker\Plausibleio\Services\ISO3166Service::search
     */
    public function alpha3ReturnsProperValues(string $input, ?array $expected): void
    {
        $subject = new ISO3166Service();
        self::assertSame($expected, $subject->alpha3($input));
    }
}
