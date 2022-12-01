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

namespace Waldhacker\Plausibleio\Tests\Unit\EventListener\AssetRenderer;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class BeforeJavaScriptsRenderingEventListenerTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function tearDown(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = null;
        $GLOBALS['TSFE'] = null;

        parent::tearDown();
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::__construct
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::perform
     */
    public function doNothingIfTheEventIsNotAnInlineEvent(): void
    {
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $assetCollectorProphecy = $this->prophesize(AssetCollector::class);
        $beforeJavaScriptsRenderingEvent = new BeforeJavaScriptsRenderingEvent($assetCollectorProphecy->reveal(), false, false);

        $assetCollectorProphecy->addJavaScript(Argument::cetera())->shouldNotBeCalled();

        $subject = new BeforeJavaScriptsRenderingEventListener($configurationServiceProphecy->reveal());
        $subject->perform($beforeJavaScriptsRenderingEvent);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::__construct
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::perform
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::getApplicationType
     */
    public function doNothingIfTheEventIsAnInlineEventButThereIsNoFrontendRequest(): void
    {
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $assetCollectorProphecy = $this->prophesize(AssetCollector::class);
        $beforeJavaScriptsRenderingEvent = new BeforeJavaScriptsRenderingEvent($assetCollectorProphecy->reveal(), true, false);

        $GLOBALS['TYPO3_REQUEST'] = null;

        $assetCollectorProphecy->addJavaScript(Argument::cetera())->shouldNotBeCalled();

        $subject = new BeforeJavaScriptsRenderingEventListener($configurationServiceProphecy->reveal());
        $subject->perform($beforeJavaScriptsRenderingEvent);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::__construct
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::perform
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::getApplicationType
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::getTypoScriptFrontendController
     */
    public function doNothingIfTheEventIsAnInlineEventAndThereIsAFrontendRequestButNoTSFE(): void
    {
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $assetCollectorProphecy = $this->prophesize(AssetCollector::class);
        $serverRequestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
        $beforeJavaScriptsRenderingEvent = new BeforeJavaScriptsRenderingEvent($assetCollectorProphecy->reveal(), true, false);

        $GLOBALS['TYPO3_REQUEST'] = $serverRequestInterfaceProphecy->reveal();
        $GLOBALS['TSFE'] = null;

        $serverRequestInterfaceProphecy->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $assetCollectorProphecy->addJavaScript(Argument::cetera())->shouldNotBeCalled();

        $subject = new BeforeJavaScriptsRenderingEventListener($configurationServiceProphecy->reveal());
        $subject->perform($beforeJavaScriptsRenderingEvent);
    }

    public function doNothingIfThereIsNoOrInvalidPlausibleConfigurationDataProvider(): \Generator
    {
        yield 'includeTrackingScript is false and all options are empty' => [
            'plausibleConfiguration' => ['includeTrackingScript' => false, 'trackingScriptBaseUrl' => '', 'trackingScriptType' => '', 'siteId' => ''],
        ];

        yield 'includeTrackingScript is false and all options are not empty' => [
            'plausibleConfiguration' => ['includeTrackingScript' => false, 'trackingScriptBaseUrl' => 'foo', 'trackingScriptType' => 'bar', 'siteId' => 'baz'],
        ];

        yield 'includeTrackingScript is true and all options are empty' => [
            'plausibleConfiguration' => ['includeTrackingScript' => true, 'trackingScriptBaseUrl' => '', 'trackingScriptType' => '', 'siteId' => ''],
        ];

        yield 'includeTrackingScript is true and only trackingScriptBaseUrl is set' => [
            'plausibleConfiguration' => ['includeTrackingScript' => true, 'trackingScriptBaseUrl' => 'foo', 'trackingScriptType' => '', 'siteId' => ''],
        ];

        yield 'includeTrackingScript is true and only trackingScriptType is set' => [
            'plausibleConfiguration' => ['includeTrackingScript' => true, 'trackingScriptBaseUrl' => '', 'trackingScriptType' => 'foo', 'siteId' => ''],
        ];

        yield 'includeTrackingScript is true and only siteId is set' => [
            'plausibleConfiguration' => ['includeTrackingScript' => true, 'trackingScriptBaseUrl' => '', 'trackingScriptType' => '', 'siteId' => 'foo'],
        ];

        yield 'includeTrackingScript is true and only trackingScriptBaseUrl and trackingScriptType is set' => [
            'plausibleConfiguration' => ['includeTrackingScript' => true, 'trackingScriptBaseUrl' => 'foo', 'trackingScriptType' => 'bar', 'siteId' => ''],
        ];

        yield 'includeTrackingScript is true and only trackingScriptBaseUrl and siteId is set' => [
            'plausibleConfiguration' => ['includeTrackingScript' => true, 'trackingScriptBaseUrl' => 'foo', 'trackingScriptType' => '', 'siteId' => 'bar'],
        ];

        yield 'includeTrackingScript is true and only trackingScriptType and siteId is set' => [
            'plausibleConfiguration' => ['includeTrackingScript' => true, 'trackingScriptBaseUrl' => '', 'trackingScriptType' => 'bar', 'siteId' => 'bar'],
        ];
    }

    /**
     * @test
     * @dataProvider doNothingIfThereIsNoOrInvalidPlausibleConfigurationDataProvider
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::__construct
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::perform
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::getApplicationType
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::getTypoScriptFrontendController
     */
    public function doNothingIfThereIsNoOrInvalidPlausibleConfiguration(array $plausibleConfiguration): void
    {
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $assetCollectorProphecy = $this->prophesize(AssetCollector::class);
        $serverRequestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
        $typoScriptFrontendControllerProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $siteProphecy = $this->prophesize(Site::class);
        $siteLanguageProphecy = $this->prophesize(SiteLanguage::class);
        $beforeJavaScriptsRenderingEvent = new BeforeJavaScriptsRenderingEvent($assetCollectorProphecy->reveal(), true, false);

        $GLOBALS['TYPO3_REQUEST'] = $serverRequestInterfaceProphecy->reveal();
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerProphecy->reveal();

        $serverRequestInterfaceProphecy->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $typoScriptFrontendControllerProphecy->getSite()->willReturn($siteProphecy->reveal());
        $typoScriptFrontendControllerProphecy->getLanguage()->willReturn($siteLanguageProphecy->reveal());

        $configurationServiceProphecy->getPlausibleConfigurationFromSiteLanguage($siteLanguageProphecy->reveal())->willReturn($plausibleConfiguration);

        $assetCollectorProphecy->addJavaScript(Argument::cetera())->shouldNotBeCalled();

        $subject = new BeforeJavaScriptsRenderingEventListener($configurationServiceProphecy->reveal());
        $subject->perform($beforeJavaScriptsRenderingEvent);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::__construct
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::perform
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::getApplicationType
     * @covers \Waldhacker\Plausibleio\EventListener\AssetRenderer\BeforeJavaScriptsRenderingEventListener::getTypoScriptFrontendController
     */
    public function addJavaScriptIfPlausibleConfigurationIsValid(): void
    {
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $assetCollectorProphecy = $this->prophesize(AssetCollector::class);
        $serverRequestInterfaceProphecy = $this->prophesize(ServerRequestInterface::class);
        $typoScriptFrontendControllerProphecy = $this->prophesize(TypoScriptFrontendController::class);
        $siteProphecy = $this->prophesize(Site::class);
        $siteLanguageProphecy = $this->prophesize(SiteLanguage::class);
        $beforeJavaScriptsRenderingEvent = new BeforeJavaScriptsRenderingEvent($assetCollectorProphecy->reveal(), true, false);

        $GLOBALS['TYPO3_REQUEST'] = $serverRequestInterfaceProphecy->reveal();
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerProphecy->reveal();

        $siteLanguageProphecy->getLanguageId()->willReturn(23);
        $serverRequestInterfaceProphecy->getAttribute('applicationType')->willReturn(SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $typoScriptFrontendControllerProphecy->getSite()->willReturn($siteProphecy->reveal());
        $typoScriptFrontendControllerProphecy->getLanguage()->willReturn($siteLanguageProphecy->reveal());
        $siteProphecy->getRootPageId()->willReturn(42);

        $configurationServiceProphecy->getPlausibleConfigurationFromSiteLanguage($siteLanguageProphecy->reveal())->willReturn([
            'includeTrackingScript' => true,
            'trackingScriptBaseUrl' => 'https://plausible.io/',
            'trackingScriptType' => 'plausible.outbound-links.js',
            'siteId' => 'waldhacker.dev',
        ]);

        $assetCollectorProphecy->addJavaScript(
            'plausible_tracking_script_42_23',
            'https://plausible.io/js/plausible.outbound-links.js',
            [
                'async' => 'async',
                'defer' => 'defer',
                'data-domain' => 'waldhacker.dev',
            ],
            ['priority' => true]
        )->shouldBeCalled();

        $subject = new BeforeJavaScriptsRenderingEventListener($configurationServiceProphecy->reveal());
        $subject->perform($beforeJavaScriptsRenderingEvent);
    }
}
