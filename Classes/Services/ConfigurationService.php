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

namespace Waldhacker\Plausibleio\Services;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use Waldhacker\Plausibleio\Services\Exception\InvalidConfigurationException;

class ConfigurationService
{
    private const EXT_KEY = 'plausibleio';
    private const DEFAULT_TIME_FRAME = '30d';
    private const DEFAULT_TIME_FRAMES = 'day,7d,30d,month,6mo,12mo';
    private ExtensionConfiguration $extensionConfiguration;
    private SiteFinder $siteFinder;

    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        SiteFinder $siteFinder
    ) {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->siteFinder = $siteFinder;
        if ($this->getLanguageService() !== null) {
            $this->getLanguageService()->includeLLFile('EXT:' . self::EXT_KEY . '/Resources/Private/Language/locallang.xlf');
        }
    }

    public function getTimeFrames(): array
    {
        $timeFrames = array_filter(explode(',', $this->getDefaultTimeFramesFromConfiguration()));
        $availableFrames = [];
        foreach ($timeFrames as $timeFrame) {
            $label = $this->getLabelForTimeFrameValue($timeFrame);
            if (!empty($label)) {
                $availableFrames[] = [
                    'value' => $timeFrame,
                    'label' => $label,
                ];
            }
        }
        return $availableFrames;
    }

    public function getTimeFrameValues(): array
    {
        $frames = [];
        foreach ($this->getTimeFrames() as $frame) {
            $frames[] = $frame['value'] ?? '';
        }
        return $frames;
    }

    public function getTimeFrameValueFromUserConfiguration(): string
    {
        $userConfiguration = $this->getBackendUser() !== null && is_array($this->getBackendUser()->uc)
                             ? $this->getBackendUser()->uc
                             : [];
        $timeFrameValue = $userConfiguration['plausible']['timeFrame'] ?? null;

        if ($timeFrameValue === null) {
            $timeFrameValue = $this->getDefaultTimeFrameValue();
        }

        return $timeFrameValue;
    }

    public function persistTimeFrameValueInUserSession(string $timeFrameValue): void
    {
        if ($this->getBackendUser() === null || !is_array($this->getBackendUser()->uc)) {
            return;
        }

        $userConfiguration = $this->getBackendUser()->uc['plausible'] ?? [];
        $userConfiguration['timeFrame'] = $timeFrameValue;
        $this->getBackendUser()->uc['plausible'] = $userConfiguration;
        $this->getBackendUser()->writeUC();
    }

    public function getDefaultTimeFrameValue(): string
    {
        $value = null;
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'defaultTimeFrame');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
        }

        return empty($value) ? DEFAULT_TIME_FRAME : $value;
    }

    public function getPlausibleSiteIdFromUserConfiguration(): string
    {
        $userConfiguration = $this->getBackendUser() !== null && is_array($this->getBackendUser()->uc)
                             ? $this->getBackendUser()->uc
                             : [];
        $plausibleSiteId = $userConfiguration['plausible']['siteId'] ?? null;

        if ($plausibleSiteId === null) {
            $plausibleSiteId = $this->getFirstAvailablePlausibleSiteId();
        }

        return $plausibleSiteId;
    }

    public function persistPlausibleSiteIdInUserSession(string $plausibleSiteId): void
    {
        if ($this->getBackendUser() === null || !is_array($this->getBackendUser()->uc)) {
            return;
        }

        $userConfiguration = $this->getBackendUser()->uc['plausible'] ?? [];
        $userConfiguration['siteId'] = $plausibleSiteId;
        $this->getBackendUser()->uc['plausible'] = $userConfiguration;
        $this->getBackendUser()->writeUC();
    }

    public function getFirstAvailablePlausibleSiteId(): string
    {
        $availablePlausibleSiteIds = $this->getAvailablePlausibleSiteIds();
        $plausibleSiteId = array_shift($availablePlausibleSiteIds);

        if ($plausibleSiteId === null) {
            $plausibleSiteId = $this->getLegacyPlausibleSiteIdConfiguration();
        }

        if ($plausibleSiteId === null) {
            throw new InvalidConfigurationException('No plausible site id could be determined.', 1636815141);
        }

        return $plausibleSiteId;
    }

    public function getAvailablePlausibleSiteIds(): array
    {
        return array_keys($this->getAvailablePlausibleSiteIdConfigurations());
    }

    public function getAvailablePlausibleSiteIdConfigurations(): array
    {
        $backendUser = $this->getBackendUser();
        $plausibleSiteIds = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            if (!$this->isPageAccessible($site->getRootPageId())) {
                continue;
            }

            foreach ($site->getLanguages() as $siteLanguage) {
                $plausibleConfiguration = $this->getPlausibleConfigurationFromSiteLanguage($siteLanguage);
                if (
                    empty($plausibleConfiguration)
                    || $backendUser === null
                    || !$backendUser->checkLanguageAccess($siteLanguage->getLanguageId())
                ) {
                    continue;
                }
                $plausibleSiteId = $plausibleConfiguration['siteId'];
                $plausibleSiteIds[$plausibleSiteId] = $plausibleConfiguration;
            }
        }

        if (empty($plausibleSiteIds)) {
            $plausibleSiteId = $this->getLegacyPlausibleSiteIdConfiguration();
            if ($plausibleSiteId === null) {
                throw new InvalidConfigurationException('No plausible site id could be determined.', 1636815144);
            }

            $plausibleSiteIds = [$plausibleSiteId => [
                'apiUrl' => $this->getLegacyApiBaseUrlConfiguration(),
                'apiKey' => $this->getLegacyApiKeyConfiguration(),
                'siteId' => $plausibleSiteId,
                'includeTrackingScript' => null,
                'trackingScriptBaseUrl' => null,
                'trackingScriptType' => null,
            ]];
        }

        return $plausibleSiteIds;
    }

    public function getPlausibleConfigurationFromSiteLanguage(SiteLanguage $siteLanguage): ?array
    {
        $languageData = $siteLanguage->toArray();
        if (empty($languageData['plausible_siteId'])) {
            return null;
        }

        return [
            'apiUrl' => $languageData['plausible_baseUrl'],
            'apiKey' => $languageData['plausible_apiKey'],
            'siteId' => $languageData['plausible_siteId'],
            'includeTrackingScript' => $languageData['plausible_includeTrackingScript'],
            'trackingScriptBaseUrl' => $languageData['plausible_trackingScriptBaseUrl'],
            'trackingScriptType' => $languageData['plausible_trackingScriptType'],
        ];
    }

    public function getApiBaseUrl(string $plausibleSiteId): string
    {
        $apiBaseUrl = $this->getAvailablePlausibleSiteIdConfigurations()[$plausibleSiteId]['apiUrl']
            ?? $this->getLegacyApiBaseUrlConfiguration();

        if ($apiBaseUrl === null) {
            throw new InvalidConfigurationException(sprintf('No plausible api base url could be determined for plausible site "%s".', $plausibleSiteId), 1636815142);
        }

        return $apiBaseUrl;
    }

    public function getApiKey(string $plausibleSiteId): string
    {
        $apiKey = $this->getAvailablePlausibleSiteIdConfigurations()[$plausibleSiteId]['apiKey']
            ?? $this->getLegacyApiKeyConfiguration();

        if ($apiKey === null) {
            throw new InvalidConfigurationException(sprintf('No plausible api key could be determined for plausible site "%s".', $plausibleSiteId), 1636815143);
        }

        return $apiKey;
    }

    public function isValidConfiguration(string $plausibleSiteId): bool
    {
        $availablePlausibleSiteIds = $this->getAvailablePlausibleSiteIdConfigurations();
        if (!isset($availablePlausibleSiteIds[$plausibleSiteId])) {
            return false;
        }
        $plausibleConfiguration = $availablePlausibleSiteIds[$plausibleSiteId];

        return !empty($plausibleConfiguration['apiUrl'])
               && !empty($plausibleConfiguration['apiKey'])
               && !empty($plausibleConfiguration['siteId']);
    }

    private function getDefaultTimeFramesFromConfiguration(): string
    {
        $value = null;
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'timeFrames');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
        }

        return empty($value) ? DEFAULT_TIME_FRAMES : $value;
    }

    private function getLegacyPlausibleSiteIdConfiguration(): ?string
    {
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'siteId');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
            $value = null;
        }

        if ($value !== null) {
            trigger_error(
                'Setting apiKey within "$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTENSIONS\'][\'plausibleio\'][\'siteId\']" is deprecated and will stop working in Version 3. Use the site configuration instead.',
                E_USER_DEPRECATED
            );
        }

        return $value;
    }

    private function getLegacyApiBaseUrlConfiguration(): ?string
    {
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'baseUrl');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
            $value = null;
        }

        if ($value !== null) {
            trigger_error(
                'Setting apiKey within "$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTENSIONS\'][\'plausibleio\'][\'baseUrl\']" is deprecated and will stop working in Version 3. Use the site configuration instead.',
                E_USER_DEPRECATED
            );
        }

        return $value;
    }

    private function getLegacyApiKeyConfiguration(): ?string
    {
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'apiKey');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
            $value = null;
        }

        if ($value !== null) {
            trigger_error(
                'Setting apiKey within "$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTENSIONS\'][\'plausibleio\'][\'apiKey\']" is deprecated and will stop working in Version 3. Use the site configuration instead.',
                E_USER_DEPRECATED
            );
        }

        return $value;
    }

    private function getLabelForTimeFrameValue(string $timeFrame): string
    {
        preg_match('/(?<number>\d+)?(?<unit>\w+)/', $timeFrame, $matches);

        $label = null;
        if ($this->getLanguageService() !== null) {
            $label = $this->getLanguageService()->getLL('timeframes.' . $matches['unit']);
        }

        return sprintf($label ?? '', $matches['number'] ?? '');
    }

    private function isPageAccessible(int $pageId): bool
    {
        $backendUser = $this->getBackendUser();
        if ($backendUser === null) {
            return false;
        }

        $pageRow = BackendUtility::readPageAccess(
            $pageId,
            $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
        );

        return $pageRow !== false
               && $backendUser->doesUserHaveAccess($pageRow, Permission::PAGE_SHOW);
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
