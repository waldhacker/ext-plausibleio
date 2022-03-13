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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Filter;
use Waldhacker\Plausibleio\FilterRepository;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\Exception\InvalidConfigurationException;

class ConfigurationServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    private ObjectProphecy $languageServiceProphecy;
    private ObjectProphecy $extensionConfigurationProphecy;
    private ObjectProphecy $siteFinderProphecy;
    private ConfigurationService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageServiceProphecy = $this->prophesize(LanguageService::class);
        $this->extensionConfigurationProphecy = $this->prophesize(ExtensionConfiguration::class);
        $this->siteFinderProphecy = $this->prophesize(SiteFinder::class);
        $GLOBALS['LANG'] = $this->languageServiceProphecy->reveal();

        $this->languageServiceProphecy->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->shouldBeCalled();

        $this->subject = new ConfigurationService(
            $this->extensionConfigurationProphecy->reveal(),
            $this->siteFinderProphecy->reveal()
        );
    }

    protected function tearDown(): void
    {
        $GLOBALS['LANG'] = null;
        $GLOBALS['BE_USER'] = null;
        $GLOBALS['TYPO3_CONF_VARS'] = null;

        parent::tearDown();
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrames
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFramesFromConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLabelForTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getTimeFramesReturnsConfiguredFramesWithLabels(): void
    {
        $this->extensionConfigurationProphecy->get('plausibleio', 'timeFrames')->willReturn('30d,3mo');

        $this->languageServiceProphecy->getLL('timeframes.d')->willReturn('%s days');
        $this->languageServiceProphecy->getLL('timeframes.mo')->willReturn('%s months');

        self::assertSame(
            [
                [
                    'value' => '30d',
                    'label' => '30 days',
                ],
                [
                    'value' => '3mo',
                    'label' => '3 months',
                ],
            ],
            $this->subject->getTimeFrames()
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrames
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFramesFromConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLabelForTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getTimeFramesReturnsDefaultFramesWithLabels(): void
    {
        $this->extensionConfigurationProphecy->get('plausibleio', 'timeFrames')->willThrow(ExtensionConfigurationPathDoesNotExistException::class);

        $this->languageServiceProphecy->getLL('timeframes.day')->willReturn('Today');
        $this->languageServiceProphecy->getLL('timeframes.month')->willReturn('This month');
        $this->languageServiceProphecy->getLL('timeframes.d')->willReturn('%s days');
        $this->languageServiceProphecy->getLL('timeframes.mo')->willReturn('%s months');

        self::assertSame(
            [
                [
                    'value' => 'day',
                    'label' => 'Today',
                ],
                [
                    'value' => '7d',
                    'label' => '7 days',
                ],
                [
                    'value' => '30d',
                    'label' => '30 days',
                ],
                [
                    'value' => 'month',
                    'label' => 'This month',
                ],
                [
                    'value' => '6mo',
                    'label' => '6 months',
                ],
                [
                    'value' => '12mo',
                    'label' => '12 months',
                ],
            ],
            $this->subject->getTimeFrames()
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrames
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrameValues
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFramesFromConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLabelForTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getTimeFrameValuesReturnsValues(): void
    {
        $this->extensionConfigurationProphecy->get('plausibleio', 'timeFrames')->willReturn('7d,30d');
        $this->languageServiceProphecy->getLL('timeframes.d')->willReturn('%s days');

        self::assertSame(['7d', '30d'], $this->subject->getTimeFrameValues());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrameValueFromUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getTimeFrameValueFromUserConfigurationReturnsValueFromUserConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->uc = [
            'plausible' => [
                'dashBoardId_98321' => [
                    'timeFrame' => '7d',
                ]
            ]
        ];

        self::assertSame('7d', $this->subject->getTimeFrameValueFromUserConfiguration('dashBoardId_98321'));

        // empty dashBoardId -> dashBoardId = ConfigurationService::DASHBOARD_DEFAULT_ID
        $GLOBALS['BE_USER']->uc = [
            'plausible' => [
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'timeFrame' => '31d',
                ]
            ]
        ];

        self::assertSame('31d', $this->subject->getTimeFrameValueFromUserConfiguration(''));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getTimeFrameValueFromUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getDefaultTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getTimeFrameValueFromUserConfigurationReturnsDefaultFrameFromExtensionConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->uc = [];
        $this->extensionConfigurationProphecy->get('plausibleio', 'defaultTimeFrame')->willReturn('30d');

        self::assertSame('30d', $this->subject->getTimeFrameValueFromUserConfiguration(ConfigurationService::DASHBOARD_DEFAULT_ID));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::persistTimeFrameValueInUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function persistTimeFrameValueInUserConfigurationDoesNothingOnInvalidBackendUserConfiguration(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        $backendUserProphecy->writeUC()->shouldNotBeCalled();
        self::assertNull($this->subject->persistTimeFrameValueInUserConfiguration('7d', ConfigurationService::DASHBOARD_DEFAULT_ID));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::persistTimeFrameValueInUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function persistTimeFrameValueInUserConfigurationPersistValue(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $GLOBALS['BE_USER']->uc = [];

        $backendUserProphecy->writeUC()->shouldBeCalled();
        self::assertNull($this->subject->persistTimeFrameValueInUserConfiguration('7d', 'dashBoardId_98321'));
        self::assertSame([
            'plausible' => [
                'dashBoardId_98321' => [
                    'timeFrame' => '7d',
                ],
            ],
        ], $backendUserProphecy->uc);

        // empty dashBoardId -> dashBoardId = ConfigurationService::DASHBOARD_DEFAULT_ID
        $GLOBALS['BE_USER']->uc = [];
        $backendUserProphecy->writeUC()->shouldBeCalled();
        self::assertNull($this->subject->persistTimeFrameValueInUserConfiguration('7d', ''));
        self::assertSame([
            'plausible' => [
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'timeFrame' => '7d',
                ],
            ],
        ], $backendUserProphecy->uc);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getDefaultTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getDefaultTimeFrameValueReturnsValueFromConfiguration(): void
    {
        $this->extensionConfigurationProphecy->get('plausibleio', 'defaultTimeFrame')->willReturn('7d');
        self::assertSame('7d', $this->subject->getDefaultTimeFrameValue());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getDefaultTimeFrameValue
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getDefaultTimeFrameValueReturnsDefaultFrameValue(): void
    {
        $this->extensionConfigurationProphecy->get('plausibleio', 'defaultTimeFrame')->willThrow(ExtensionConfigurationPathDoesNotExistException::class);
        self::assertSame('30d', $this->subject->getDefaultTimeFrameValue());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getPlausibleSiteIdFromUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getPlausibleSiteIdFromUserConfigurationReturnsValueFromUserConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->uc = [
            'plausible' => [
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'siteId' => 'waldhacker.dev',
                ],
            ],
        ];

        self::assertSame('waldhacker.dev', $this->subject->getPlausibleSiteIdFromUserConfiguration(ConfigurationService::DASHBOARD_DEFAULT_ID));

        // empty dashBoardId -> dashBoardId = ConfigurationService::DASHBOARD_DEFAULT_ID
        $GLOBALS['BE_USER']->uc = [
            'plausible' => [
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'siteId' => 'waldhacker.dev',
                ],
            ],
        ];

        self::assertSame('waldhacker.dev', $this->subject->getPlausibleSiteIdFromUserConfiguration(''));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getPlausibleSiteIdFromUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getPlausibleSiteIdFromUserConfigurationReturnsValueFromFirstAvailableSite(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->uc = [];

        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getFirstAvailablePlausibleSiteId'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())->method('getFirstAvailablePlausibleSiteId')->willReturn('waldhacker.dev');

        self::assertSame('waldhacker.dev', $subject->getPlausibleSiteIdFromUserConfiguration(ConfigurationService::DASHBOARD_DEFAULT_ID));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::persistPlausibleSiteIdInUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function persistPlausibleSiteIdInUserConfigurationDoesNothingOnInvalidBackendUserConfiguration(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        $backendUserProphecy->writeUC()->shouldNotBeCalled();
        self::assertNull($this->subject->persistPlausibleSiteIdInUserConfiguration('waldhacker.dev', ConfigurationService::DASHBOARD_DEFAULT_ID));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::persistFiltersInUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function persistPlausibleFiltersInUserConfigurationDoesNothingOnInvalidBackendUserConfiguration(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();

        $backendUserProphecy->writeUC()->shouldNotBeCalled();
        self::assertNull($this->subject->persistFiltersInUserConfiguration(new FilterRepository(), ConfigurationService::DASHBOARD_DEFAULT_ID));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::persistFiltersInUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
 */
    public function persistFiltersInUserConfigurationPersistValue(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $GLOBALS['BE_USER']->uc = [];

        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTERVISITOS, 'Mac'));

        $backendUserProphecy->writeUC()->shouldBeCalled();
        self::assertNull($this->subject->persistFiltersInUserConfiguration($filterRepo, 'dashBoardId_98321'));
        self::assertSame([
            'plausible' => [
                'dashBoardId_98321' => [
                    'filters' => [
                        [
                            'name' => FilterRepository::FILTERVISITOS,
                            'value' => 'Mac',
                            'label' => '',
                            'labelValue' => '',
                        ]
                    ],
                ],
            ],
        ], $backendUserProphecy->uc);

        // empty dashBoardId -> dashBoardId = ConfigurationService::DASHBOARD_DEFAULT_ID
        $filterRepo->clearFilters();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTERVISITOS, 'Windows'));
        $GLOBALS['BE_USER']->uc = [];
        self::assertNull($this->subject->persistFiltersInUserConfiguration($filterRepo, ''));
        self::assertSame([
            'plausible' => [
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'filters' => [
                        [
                            'name' => FilterRepository::FILTERVISITOS,
                            'value' => 'Windows',
                            'label' => '',
                            'labelValue' => '',
                        ]
                    ],
                ],
            ],
        ], $backendUserProphecy->uc);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getFiltersFromUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getLabel
     * @covers \Waldhacker\Plausibleio\Filter::getLabelValue
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\Filter::getValue
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     */
    public function getFiltersFromUserConfigurationWithDefaultDashboardReturnsValueFromUserConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->uc = [
            'plausible' => [
                'dashBoardId_98321' => [
                    'filters' => [['name' => 'visit:os', 'value' => 'Mac']],
                ],
            ],
        ];

        self::assertSame([['name' => 'visit:os', 'value' => 'Mac', 'label' => '', 'labelValue' => '']], $this->subject->getFiltersFromUserConfiguration('dashBoardId_98321')->getFiltersAsArray());

        // dashboardKey could not be found -> ConfigurationService::DASHBOARD_DEFAULT_ID is used as fallback
        $GLOBALS['BE_USER']->uc = [
            'plausible' => [
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'filters' => [['name' => 'visit:os', 'value' => 'Windows']],
                ],
            ],
        ];

        self::assertSame([['name' => 'visit:os', 'value' => 'Windows', 'label' => '', 'labelValue' => '']], $this->subject->getFiltersFromUserConfiguration('unavailableKey')->getFiltersAsArray());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getFiltersFromUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getLabel
     * @covers \Waldhacker\Plausibleio\Filter::getLabelValue
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\Filter::getValue
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     */
    public function getFiltersFromUserConfigurationWithConcreteDashboardReturnsValueFromUserConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->uc = [
            'plausible' => [
                'dashboard_AAA' => [
                    'filters' => [['name' => 'visit:os', 'value' => 'Mac']],
                ],
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'filters' => [['name' => 'visit:os', 'value' => 'Windows']],
                ],
            ],
        ];

        self::assertSame([['name' => 'visit:os', 'value' => 'Mac', 'label' => '', 'labelValue' => '']], $this->subject->getFiltersFromUserConfiguration('dashboard_AAA')->getFiltersAsArray());
    }

    public function getFiltersFromUserConfigurationReturnsEmptyArrayOnInvalidBackendUserConfigurationDataProvider(): \Generator
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';

        yield 'BE_USER null' => [
            'beUser' => null,
            'uc' => [],
            ];

        yield 'uc not an array' => [
            'beUser' => new BackendUserAuthentication(),
            'uc' => null,
        ];

        yield 'BE_USER null and uc null' => [
            'beUser' => null,
            'uc' => null,
        ];
    }

    /**
     * @test
     * @dataProvider getFiltersFromUserConfigurationReturnsEmptyArrayOnInvalidBackendUserConfigurationDataProvider
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getFiltersFromUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getFiltersFromUserConfigurationReturnsEmptyArrayOnInvalidBackendUserConfiguration(
        ?BackendUserAuthentication $beUser,
        ?array $uc
    ): void {
        $GLOBALS['BE_USER'] = $beUser;
        if ($beUser) {
            $GLOBALS['BE_USER']->uc = $uc;
        }

        self::assertSame([], $this->subject->getFiltersFromUserConfiguration(ConfigurationService::DASHBOARD_DEFAULT_ID)->getFiltersAsArray());
    }

    public function getAllFiltersFromUserConfigurationReturnsEmptyArrayOnInvalidBackendUserConfigurationDataProvider(): \Generator
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';

        yield 'BE_USER null' => [
            'beUser' => null,
            'uc' => [],
        ];

        yield 'uc not an array' => [
            'beUser' => new BackendUserAuthentication(),
            'uc' => null,
        ];

        yield 'BE_USER null and uc null' => [
            'beUser' => null,
            'uc' => null,
        ];
    }

    /**
     * @test
     * @dataProvider getAllFiltersFromUserConfigurationReturnsEmptyArrayOnInvalidBackendUserConfigurationDataProvider
     * @covers       \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers       \Waldhacker\Plausibleio\Services\ConfigurationService::getAllFiltersFromUserConfiguration
     * @covers       \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers       \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getAllFiltersFromUserConfigurationReturnsEmptyArrayOnInvalidBackendUserConfiguration(
        ?BackendUserAuthentication $beUser,
        ?array $uc
    ): void {
        $GLOBALS['BE_USER'] = $beUser;
        if ($beUser) {
            $GLOBALS['BE_USER']->uc = $uc;
        }

        self::assertSame([], $this->subject->getAllFiltersFromUserConfiguration());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getAllFiltersFromUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getAllFiltersFromUserConfigurationReturnsValueFromUserConfiguration(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName'] = '';
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->uc = [
            'plausible' => [
                'dashboard_AAA' => [
                    'filters' => ['name' => 'visit:os==Mac'],
                ],
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'filters' => ['name' => 'visit:os==Windows'],
                ],
            ],
        ];

        self::assertSame(
            [
                'dashboard_AAA' => ['name' => 'visit:os==Mac'],
                ConfigurationService::DASHBOARD_DEFAULT_ID => ['name' => 'visit:os==Windows'],
            ],
            $this->subject->getAllFiltersFromUserConfiguration()
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::persistPlausibleSiteIdInUserConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function persistPlausibleSiteIdInUserConfigurationPersistValue(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $GLOBALS['BE_USER']->uc = [];

        $backendUserProphecy->writeUC()->shouldBeCalled();
        self::assertNull($this->subject->persistPlausibleSiteIdInUserConfiguration('waldhacker.dev', ConfigurationService::DASHBOARD_DEFAULT_ID));
        self::assertSame([
            'plausible' => [
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'siteId' => 'waldhacker.dev'
                ],
            ],
        ], $backendUserProphecy->uc);

        // empty dashBoardId -> dashBoardId = ConfigurationService::DASHBOARD_DEFAULT_ID
        $GLOBALS['BE_USER']->uc = [];
        self::assertNull($this->subject->persistPlausibleSiteIdInUserConfiguration('waldhacker.dev', ''));
        self::assertSame([
            'plausible' => [
                ConfigurationService::DASHBOARD_DEFAULT_ID => [
                    'siteId' => 'waldhacker.dev'
                ],
            ],
        ], $backendUserProphecy->uc);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getFirstAvailablePlausibleSiteId
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getFirstAvailablePlausibleSiteIdReturnsFirstAvailablePlausibleSiteId(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIds'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())->method('getAvailablePlausibleSiteIds')->willReturn(['waldhacker.dev', 'example.com']);

        self::assertSame('waldhacker.dev', $subject->getFirstAvailablePlausibleSiteId());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getFirstAvailablePlausibleSiteId
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyPlausibleSiteIdConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getFirstAvailablePlausibleSiteIdReturnsPlausibleSiteIdFromLegacyConfiguration(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIds', 'getLegacyPlausibleSiteIdConfiguration'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())->method('getAvailablePlausibleSiteIds')->willReturn([]);
        $subject->expects(self::any())->method('getLegacyPlausibleSiteIdConfiguration')->willReturn('waldhacker.dev');

        self::assertSame('waldhacker.dev', $subject->getFirstAvailablePlausibleSiteId());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getFirstAvailablePlausibleSiteId
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyPlausibleSiteIdConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getFirstAvailablePlausibleSiteIdThrowsExceptionIfNoPlausibleSiteIdCouldBeDetermined(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIds', 'getLegacyPlausibleSiteIdConfiguration'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())->method('getAvailablePlausibleSiteIds')->willReturn([]);
        $subject->expects(self::any())->method('getLegacyPlausibleSiteIdConfiguration')->willReturn(null);

        self::expectException(InvalidConfigurationException::class);

        $subject->getFirstAvailablePlausibleSiteId();
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getAvailablePlausibleSiteIds
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getAvailablePlausibleSiteIdsReturnsArrayWithPlausibleSiteIds(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject
            ->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn(['waldhacker.dev' => [], 'example.com' => []]);

        self::assertSame(['waldhacker.dev',  'example.com'], $subject->getAvailablePlausibleSiteIds());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getAvailablePlausibleSiteIdConfigurations
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getPlausibleConfigurationFromSiteLanguage
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getAvailablePlausibleSiteIdConfigurationsReturnsConfigurationsFromAccessibleSiteLanguages(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $site1Prophecy = $this->prophesize(Site::class);
        $site2Prophecy = $this->prophesize(Site::class);

        $siteLanguage1Prophecy = $this->prophesize(SiteLanguage::class);
        $siteLanguage2Prophecy = $this->prophesize(SiteLanguage::class);
        $siteLanguage3Prophecy = $this->prophesize(SiteLanguage::class);
        $siteLanguage4Prophecy = $this->prophesize(SiteLanguage::class);
        $siteLanguage5Prophecy = $this->prophesize(SiteLanguage::class);

        $siteLanguage1Prophecy->getLanguageId()->willReturn(10);
        $siteLanguage2Prophecy->getLanguageId()->willReturn(11);
        $siteLanguage3Prophecy->getLanguageId()->willReturn(12);
        $siteLanguage4Prophecy->getLanguageId()->willReturn(13);
        $siteLanguage5Prophecy->getLanguageId()->willReturn(14);

        $siteLanguage1Prophecy->toArray()->willReturn([
            'plausible_baseUrl' => 'https://example.com/',
            'plausible_apiKey' => 'super-secret-key-1',
            'plausible_siteId' => 'example.com',
            'plausible_includeTrackingScript' => true,
            'plausible_trackingScriptBaseUrl' => 'https://example.com/',
            'plausible_trackingScriptType' => 'plausible.js',
            'plausible_auto404Tracking' => false,
        ]);
        $siteLanguage2Prophecy->toArray()->willReturn([
            'plausible_baseUrl' => 'https://de.example.com/',
            'plausible_apiKey' => 'super-secret-key-2',
            'plausible_siteId' => 'de.example.com',
            'plausible_includeTrackingScript' => true,
            'plausible_trackingScriptBaseUrl' => 'https://de.example.com/',
            'plausible_trackingScriptType' => 'plausible.js',
            'plausible_auto404Tracking' => true,
        ]);
        $siteLanguage3Prophecy->toArray()->willReturn([
            'plausible_baseUrl' => 'https://zz.example.com/',
            'plausible_apiKey' => 'super-secret-key-3',
            'plausible_siteId' => '',
            'plausible_includeTrackingScript' => true,
            'plausible_trackingScriptBaseUrl' => 'https://zz.example.com/',
            'plausible_trackingScriptType' => 'plausible.js',
            'plausible_auto404Tracking' => false,
        ]);
        $siteLanguage4Prophecy->toArray()->willReturn([
            'plausible_baseUrl' => 'https://yy.example.com/',
            'plausible_apiKey' => 'super-secret-key-4',
            'plausible_siteId' => 'yy.example.com',
            'plausible_includeTrackingScript' => true,
            'plausible_trackingScriptBaseUrl' => 'https://yy.example.com/',
            'plausible_trackingScriptType' => 'plausible.js',
            'plausible_auto404Tracking' => false,
        ]);
        $siteLanguage5Prophecy->toArray()->willReturn([
            'plausible_baseUrl' => 'https://dd.example.com/',
            'plausible_apiKey' => 'super-secret-key-5',
            'plausible_siteId' => 'dd.example.com',
            'plausible_includeTrackingScript' => true,
            'plausible_trackingScriptBaseUrl' => 'https://dd.example.com/',
            'plausible_trackingScriptType' => 'plausible.js',
            'plausible_auto404Tracking' => true,
        ]);

        $site1Prophecy->getRootPageId()->willReturn(1);
        $site2Prophecy->getRootPageId()->willReturn(2);

        $site1Prophecy->getLanguages()->willReturn([
            $siteLanguage1Prophecy->reveal(),
            $siteLanguage2Prophecy->reveal(),
            $siteLanguage3Prophecy->reveal(),
            $siteLanguage4Prophecy->reveal()
        ]);
        $site2Prophecy->getLanguages()->willReturn([$siteLanguage4Prophecy->reveal()]);

        $this->siteFinderProphecy->getAllSites()->willReturn([
            $site1Prophecy->reveal(),
            $site2Prophecy->reveal(),
        ]);

        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['isPageAccessible'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject
            ->method('isPageAccessible')
            ->withConsecutive([1], [2])
            ->willReturnOnConsecutiveCalls(true, false);

        $backendUserProphecy->checkLanguageAccess(10)->willReturn(true);
        $backendUserProphecy->checkLanguageAccess(11)->willReturn(true);
        $backendUserProphecy->checkLanguageAccess(12)->willReturn(true);
        $backendUserProphecy->checkLanguageAccess(13)->willReturn(false);
        $backendUserProphecy->checkLanguageAccess(14)->willReturn(false);

        self::assertSame(
            [
                'example.com' => [
                    'apiUrl' => 'https://example.com/',
                    'apiKey' => 'super-secret-key-1',
                    'siteId' => 'example.com',
                    'includeTrackingScript' => true,
                    'trackingScriptBaseUrl' => 'https://example.com/',
                    'trackingScriptType' => 'plausible.js',
                    'auto404Tracking' => false,
                ],
                'de.example.com' => [
                    'apiUrl' => 'https://de.example.com/',
                    'apiKey' => 'super-secret-key-2',
                    'siteId' => 'de.example.com',
                    'includeTrackingScript' => true,
                    'trackingScriptBaseUrl' => 'https://de.example.com/',
                    'trackingScriptType' => 'plausible.js',
                    'auto404Tracking' => true,
                ],
            ],
            $subject->getAvailablePlausibleSiteIdConfigurations()
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getAvailablePlausibleSiteIdConfigurations
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyPlausibleSiteIdConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyApiBaseUrlConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyApiKeyConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getAvailablePlausibleSiteIdConfigurationsReturnsConfigurationsFromLegacyConfiguration(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $this->siteFinderProphecy->getAllSites()->willReturn([]);

        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getLegacyPlausibleSiteIdConfiguration', 'getLegacyApiBaseUrlConfiguration', 'getLegacyApiKeyConfiguration'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())->method('getLegacyPlausibleSiteIdConfiguration')->willReturn('waldhacker.dev');
        $subject->expects(self::any())->method('getLegacyApiBaseUrlConfiguration')->willReturn('https://example.com/');
        $subject->expects(self::any())->method('getLegacyApiKeyConfiguration')->willReturn('super-secret-key');

        self::assertSame(
            [
                'waldhacker.dev' => [
                    'apiUrl' => 'https://example.com/',
                    'apiKey' => 'super-secret-key',
                    'siteId' => 'waldhacker.dev',
                    'includeTrackingScript' => null,
                    'trackingScriptBaseUrl' => null,
                    'trackingScriptType' => null,
                ],
            ],
            $subject->getAvailablePlausibleSiteIdConfigurations()
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getAvailablePlausibleSiteIdConfigurations
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyPlausibleSiteIdConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getBackendUser
     */
    public function getAvailablePlausibleSiteIdConfigurationsThrowsExceptionIfNoSitesCouldBeDetermined(): void
    {
        $backendUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $backendUserProphecy->reveal();
        $this->siteFinderProphecy->getAllSites()->willReturn([]);

        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getLegacyPlausibleSiteIdConfiguration'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())->method('getLegacyPlausibleSiteIdConfiguration')->willReturn(null);

        self::expectException(InvalidConfigurationException::class);

        $subject->getAvailablePlausibleSiteIdConfigurations();
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getPlausibleConfigurationFromSiteLanguage
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getPlausibleConfigurationFromSiteLanguageReturnsValidConfiguration(): void
    {
        $siteLanguageProphecy = $this->prophesize(SiteLanguage::class);
        $siteLanguageProphecy->toArray()->willReturn([
            'plausible_baseUrl' => 'https://example.com/',
            'plausible_apiKey' => 'super-secret-key',
            'plausible_siteId' => 'example.com',
            'plausible_includeTrackingScript' => true,
            'plausible_trackingScriptBaseUrl' => 'https://example.com/',
            'plausible_trackingScriptType' => 'plausible.js',
            'plausible_auto404Tracking' => false,
        ]);

        self::assertSame(
            [
                'apiUrl' => 'https://example.com/',
                'apiKey' => 'super-secret-key',
                'siteId' => 'example.com',
                'includeTrackingScript' => true,
                'trackingScriptBaseUrl' => 'https://example.com/',
                'trackingScriptType' => 'plausible.js',
                'auto404Tracking' => false,
            ],
            $this->subject->getPlausibleConfigurationFromSiteLanguage($siteLanguageProphecy->reveal())
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getPlausibleConfigurationFromSiteLanguage
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getPlausibleConfigurationFromSiteLanguageReturnsNullOnInvalidConfiguration(): void
    {
        $siteLanguageProphecy = $this->prophesize(SiteLanguage::class);
        $siteLanguageProphecy->toArray()->willReturn([
            'plausible_baseUrl' => 'https://example.com/',
            'plausible_apiKey' => 'super-secret-key',
            'plausible_siteId' => '',
            'plausible_includeTrackingScript' => true,
            'plausible_trackingScriptBaseUrl' => 'https://example.com/',
            'plausible_trackingScriptType' => 'plausible.js',
        ]);

        self::assertNull($this->subject->getPlausibleConfigurationFromSiteLanguage($siteLanguageProphecy->reveal()));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getApiBaseUrl
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getApiBaseUrlReturnsUrlIfPlausibleSiteIdIsAvailable(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn(['example.com' => ['apiUrl' => 'https://example.com/']]);

        self::assertSame('https://example.com/', $subject->getApiBaseUrl('example.com'));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getApiBaseUrl
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyApiBaseUrlConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getApiBaseUrlReturnsUrlFromLegacyConfiguration(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations', 'getLegacyApiBaseUrlConfiguration'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn([]);

        $subject->expects(self::any())
            ->method('getLegacyApiBaseUrlConfiguration')
            ->willReturn('https://example.com/');

        self::assertSame('https://example.com/', $subject->getApiBaseUrl('example.com'));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getApiBaseUrl
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyApiBaseUrlConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getApiBaseUrlThrowsExceptionIfApiBaseUrlCouldNotBeDetermined(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations', 'getLegacyApiBaseUrlConfiguration'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn([]);

        $subject->expects(self::any())
            ->method('getLegacyApiBaseUrlConfiguration')
            ->willReturn(null);

        self::expectException(InvalidConfigurationException::class);

        $subject->getApiBaseUrl('example.com');
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getApiKey
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getApiKeyReturnsUrlIfPlausibleSiteIdIsAvailable(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn(['example.com' => ['apiKey' => 'super-secret-key']]);

        self::assertSame('super-secret-key', $subject->getApiKey('example.com'));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getApiKey
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyApiKeyConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getApiKeyReturnsUrlFromLegacyConfiguration(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations', 'getLegacyApiKeyConfiguration'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn([]);

        $subject->expects(self::any())
            ->method('getLegacyApiKeyConfiguration')
            ->willReturn('super-secret-key');

        self::assertSame('super-secret-key', $subject->getApiKey('example.com'));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getApiKey
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLegacyApiKeyConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function getApiKeyThrowsExceptionIfApiBaseUrlCouldNotBeDetermined(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations', 'getLegacyApiKeyConfiguration'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn([]);

        $subject->expects(self::any())
            ->method('getLegacyApiKeyConfiguration')
            ->willReturn(null);

        self::expectException(InvalidConfigurationException::class);

        $subject->getApiKey('example.com');
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::isValidConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function isValidConfigurationReturnsTrueIfConfigurationIsValid(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn(
                [
                'example.com' => [
                    'apiUrl' => 'https://example.com/',
                    'apiKey' => 'super-secret-key',
                    'siteId' => 'example.com',
                ]
            ]
            );

        self::assertTrue($subject->isValidConfiguration('example.com'));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::isValidConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function isValidConfigurationReturnsFalseIfPlausibleSiteIdIsNotAvailable(): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn(
                [
                'example.com' => [
                    'apiUrl' => 'https://example.com/',
                    'apiKey' => 'super-secret-key',
                    'siteId' => 'example.com',
                ]
            ]
            );

        self::assertFalse($subject->isValidConfiguration('waldhacker.dev'));
    }

    public function isValidConfigurationReturnsFalseIfConfigurationIsNotValidDataProvider(): \Generator
    {
        yield 'apiUrl is empty' => [
            [
                'apiUrl' => '',
                'apiKey' => 'super-secret-key',
                'siteId' => 'example.com',
            ]
        ];

        yield 'apiKey is empty' => [
            [
                'apiUrl' => 'https://example.com/',
                'apiKey' => '',
                'siteId' => 'example.com',
            ]
        ];

        yield 'siteId is empty' => [
            [
                'apiUrl' => 'https://example.com/',
                'apiKey' => 'super-secret-key',
                'siteId' => '',
            ]
        ];
    }

    /**
     * @test
     * @dataProvider isValidConfigurationReturnsFalseIfConfigurationIsNotValidDataProvider
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::__construct
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::isValidConfiguration
     * @covers \Waldhacker\Plausibleio\Services\ConfigurationService::getLanguageService
     */
    public function isValidConfigurationReturnsFalseIfConfigurationIsNotValid(array $configuration): void
    {
        $subject = $this->getMockBuilder(ConfigurationService::class)
            ->onlyMethods(['getAvailablePlausibleSiteIdConfigurations'])
            ->setConstructorArgs([
                $this->extensionConfigurationProphecy->reveal(),
                $this->siteFinderProphecy->reveal()
            ])
            ->getMock();

        $subject->expects(self::any())
            ->method('getAvailablePlausibleSiteIdConfigurations')
            ->willReturn(
                [
                'example.com' => $configuration
            ]
            );

        self::assertFalse($subject->isValidConfiguration('example.com'));
    }
}
