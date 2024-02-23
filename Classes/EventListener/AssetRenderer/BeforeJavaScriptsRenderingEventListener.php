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

namespace Waldhacker\Plausibleio\EventListener\AssetRenderer;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class BeforeJavaScriptsRenderingEventListener
{
    private ConfigurationService $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    public function perform(BeforeJavaScriptsRenderingEvent $event): void
    {
        if (
            !$event->isInline()
            || $this->getApplicationType() !== 'FE'
            || $this->getRequest() === null
        ) {
            return;
        }

        $site = $this->getSite();
        $siteLanguage = $this->getLanguage();

        if (!$site || !$siteLanguage) {
            return;
        }

        $plausibleConfiguration = $this->configurationService->getPlausibleConfigurationFromSiteLanguage($siteLanguage);

        if (
            !(bool)($plausibleConfiguration['includeTrackingScript'] ?? false)
            || empty($plausibleConfiguration['trackingScriptBaseUrl'])
            || empty($plausibleConfiguration['trackingScriptType'])
            || empty($plausibleConfiguration['siteId'])
        ) {
            return;
        }

        $event->getAssetCollector()->addJavaScript(
            sprintf(
                'plausible_tracking_script_%s_%s',
                $site->getRootPageId(),
                $siteLanguage->getLanguageId()
            ),
            sprintf(
                '%s/js/%s',
                rtrim($plausibleConfiguration['trackingScriptBaseUrl'], '/'),
                $plausibleConfiguration['trackingScriptType']
            ),
            [
                'async' => 'async',
                'defer' => 'defer',
                'data-domain' => $plausibleConfiguration['siteId'],
            ],
            ['priority' => true]
        );
    }

    private function getSite(): ?SiteInterface
    {
        return $this->getRequest() ? $this->getRequest()->getAttribute('site') : null;
    }

    private function getLanguage(): ?SiteLanguage
    {
        return $this->getRequest() ? $this->getRequest()->getAttribute('language') : null;
    }

    private function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }

    private function getApplicationType(): string
    {
        if (
            $this->getRequest() instanceof ServerRequestInterface
            && ApplicationType::fromRequest($this->getRequest())->isFrontend()
        ) {
            return 'FE';
        }

        return 'BE';
    }
}
