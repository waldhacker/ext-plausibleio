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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use Waldhacker\Plausibleio\Services\Exception\InvalidConfigurationException;

use function PHPUnit\Framework\isEmpty;

class ConfigurationService
{
    private const EXT_KEY = 'plausibleio';
    private const DEFAULT_TIME_FRAME = '30d';
    private const DEFAULT_TIME_FRAMES = 'day,7d,30d,month,6mo,12mo';
    const DASHBOARD_DEFAULT_ID = 'default';
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
        $timeFrames = array_filter(explode(',', $this->getTimeFramesFromConfiguration()));
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

    public function getTimeFrameValueFromUserConfiguration(string $dashBoardId): string
    {
        $userConfiguration = $this->getBackendUser() !== null && is_array($this->getBackendUser()->uc)
                             ? $this->getBackendUser()->uc
                             : [];
        if (ArrayUtility::isValidPath($userConfiguration, 'plausible/' . $dashBoardId . '/timeFrame')) {
            $timeFrameValue = $userConfiguration['plausible'][$dashBoardId]['timeFrame'] ?? null;
        } else {
            $timeFrameValue = $userConfiguration['plausible'][self::DASHBOARD_DEFAULT_ID]['timeFrame'] ?? null;
        }

        if ($timeFrameValue === null) {
            $timeFrameValue = $this->getDefaultTimeFrameValue();
        }

        return $timeFrameValue;
    }

    public function persistTimeFrameValueInUserConfiguration(string $timeFrameValue, string $dashBoardId): void
    {
        if ($this->getBackendUser() === null || !is_array($this->getBackendUser()->uc)) {
            return;
        }
        if (empty($dashBoardId)) {
            $dashBoardId = self::DASHBOARD_DEFAULT_ID;
        }

        $userConfiguration = $this->getBackendUser()->uc['plausible'] ?? [];
        $userConfiguration[$dashBoardId]['timeFrame'] = $timeFrameValue;
        $this->getBackendUser()->uc['plausible'] = $userConfiguration;
        $this->getBackendUser()->writeUC();
    }

    public function persistFiltersInUserConfiguration(array $filters, string $dashBoardId): void
    {
        if ($this->getBackendUser() === null || !is_array($this->getBackendUser()->uc)) {
            return;
        }
        if (empty($dashBoardId)) {
            $dashBoardId = self::DASHBOARD_DEFAULT_ID;
        }

        $userConfiguration = $this->getBackendUser()->uc['plausible'] ?? [];
        $userConfiguration[$dashBoardId]['filters'] = $filters;
        $this->getBackendUser()->uc['plausible'] = $userConfiguration;
        $this->getBackendUser()->writeUC();
    }

    public function getFiltersFromUserConfiguration(string $dashBoardId): array
    {
        if (empty($dashBoardId)) {
            $dashBoardId = self::DASHBOARD_DEFAULT_ID;
        }

        $userConfiguration = $this->getBackendUser() !== null && is_array($this->getBackendUser()->uc)
            ? $this->getBackendUser()->uc
            : [];

        // In case there is no configuration for this dashboard key, use a possibly
        // existing configuration from the default key. The default key should only
        // be used if the dashboard key could not be determined on the browser side.
        // The default key is considered here because when the dashboard and widgets
        // are initialised (***Widget->getEventData()), the dashboard key does not
        // come from the browser side but from the database and thus a key is always
        // available, even if it may not exist or cannot be determined on the browser
        // side. If no key could be determined on the browser side, this is indicated
        // by the fact that the key from the database could not be found in the filter
        // array.
        if (ArrayUtility::isValidPath($userConfiguration, 'plausible/' . $dashBoardId . '/filters')) {
            $filters = $userConfiguration['plausible'][$dashBoardId]['filters'] ?? [];
        } else {
            $filters = $userConfiguration['plausible'][self::DASHBOARD_DEFAULT_ID]['filters'] ?? [];
        }

        return $filters;
    }

    public function getAllFiltersFromUserConfiguration(): array
    {
        $userConfiguration = $this->getBackendUser() !== null && is_array($this->getBackendUser()->uc)
            ? $this->getBackendUser()->uc
            : [];

        $dashboardConfiguration = $userConfiguration['plausible'] ?? [];
        $filters = [];

        foreach ($dashboardConfiguration as $key => $config) {
            if (isset($config['filters'])) {
                $filters[$key] = $config['filters'];
            }
        }

        return $filters;
    }

    public function getDefaultTimeFrameValue(): string
    {
        $value = null;
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'defaultTimeFrame');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
        }

        return empty($value) ? self::DEFAULT_TIME_FRAME : $value;
    }

    public function getPlausibleSiteIdFromUserConfiguration(string $dashBoardId): string
    {
        $userConfiguration = $this->getBackendUser() !== null && is_array($this->getBackendUser()->uc)
                             ? $this->getBackendUser()->uc
                             : [];
        if (empty($dashBoardId)) {
            $dashBoardId = self::DASHBOARD_DEFAULT_ID;
        }

        if (ArrayUtility::isValidPath($userConfiguration, 'plausible/' . $dashBoardId . '/siteId')) {
            $plausibleSiteId = $userConfiguration['plausible'][$dashBoardId]['siteId'] ?? null;
        } else {
            $plausibleSiteId = $userConfiguration['plausible'][self::DASHBOARD_DEFAULT_ID]['siteId'] ?? null;
        }

        if ($plausibleSiteId === null) {
            $plausibleSiteId = $this->getFirstAvailablePlausibleSiteId();
        }

        return $plausibleSiteId;
    }

    public function persistPlausibleSiteIdInUserConfiguration(string $plausibleSiteId, string $dashBoardId): void
    {
        if ($this->getBackendUser() === null || !is_array($this->getBackendUser()->uc)) {
            return;
        }
        if (empty($dashBoardId)) {
            $dashBoardId = self::DASHBOARD_DEFAULT_ID;
        }

        $userConfiguration = $this->getBackendUser()->uc['plausible'] ?? [];
        $userConfiguration[$dashBoardId]['siteId'] = $plausibleSiteId;
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
            'auto404Tracking' => $languageData['plausible_auto404Tracking'],
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

    private function getTimeFramesFromConfiguration(): string
    {
        $value = null;
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'timeFrames');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
        }

        return empty($value) ? self::DEFAULT_TIME_FRAMES : $value;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getLegacyPlausibleSiteIdConfiguration(): ?string
    {
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'siteId');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
            $value = null;
        }

        if ($value !== null) {
            trigger_error(
                'Setting siteId within "$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTENSIONS\'][\'plausibleio\'][\'siteId\']" is deprecated and will stop working in Version 3. Use the site configuration instead.',
                E_USER_DEPRECATED
            );
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getLegacyApiBaseUrlConfiguration(): ?string
    {
        try {
            $value = $this->extensionConfiguration->get(self::EXT_KEY, 'baseUrl');
        } catch (ExtensionConfigurationPathDoesNotExistException $e) {
            $value = null;
        }

        if ($value !== null) {
            trigger_error(
                'Setting baseUrl within "$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTENSIONS\'][\'plausibleio\'][\'baseUrl\']" is deprecated and will stop working in Version 3. Use the site configuration instead.',
                E_USER_DEPRECATED
            );
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getLegacyApiKeyConfiguration(): ?string
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

    /**
     * @codeCoverageIgnore
     */
    protected function isPageAccessible(int $pageId): bool
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
