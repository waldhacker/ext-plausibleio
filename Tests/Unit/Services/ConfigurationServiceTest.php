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

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class ConfigurationServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    private $languageService;
    private $extensionConfiguration;
    private ConfigurationService $configurationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->languageService = $this->prophesize(LanguageService::class);
        $this->extensionConfiguration = $this->prophesize(ExtensionConfiguration::class);
        $this->configurationService = new ConfigurationService(
            $this->extensionConfiguration->reveal(),
            $this->languageService->reveal()
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrames
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLabelForTimeFrameValue
     */
    public function getTimeFramesReturnsDefaultTimeFrame(): void
    {
        $this->languageService->getLL('timeframes.d')->willReturn('%s days');
        $timeFrames = $this->configurationService->getTimeFrames();
        self::assertSame(
            [
                [
                    'value' => '30d',
                    'label' => '30 days',
                    'default' => true,
                ],
            ],
            $timeFrames
        );
    }
    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrames
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLabelForTimeFrameValue
     */
    public function getTimeFramesReturnsConfiguredFramesWithLabels(): void
    {
        $this->extensionConfiguration->get('plausibleio', 'timeFrames')->willReturn('30d,3m');
        $this->extensionConfiguration->get('plausibleio', 'defaultTimeFrame')->willReturn('30d');

        $this->languageService->getLL('timeframes.d')->willReturn('%s days');
        $this->languageService->getLL('timeframes.m')->willReturn('%s months');
        $timeFrames = $this->configurationService->getTimeFrames();
        self::assertSame(
            [
                [
                    'value' => '30d',
                    'label' => '30 days',
                    'default' => true,
                ],
                [
                    'value' => '3m',
                    'label' => '3 months',
                    'default' => false,
                ],
            ],
            $timeFrames
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getDefaultTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLabelForTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrameValues
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrames
     */
    public function getDefaultTimeFrameValue(): void
    {
        $defaultTimeFrameValue = $this->configurationService->getDefaultTimeFrameValue();
        self::assertSame('30d', $defaultTimeFrameValue);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrameValues
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLabelForTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrames
     */
    public function getTimeFrameValuesReturnsValues(): void
    {
        $this->languageService->getLL('timeframes.d')->willReturn('%s days');
        $timeFrameValues = $this->configurationService->getTimeFrameValues();
        self::assertSame(['30d'], $timeFrameValues);
    }
}
