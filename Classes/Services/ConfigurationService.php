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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;

class ConfigurationService
{
    private const EXT_KEY = 'plausibleio';
    private const DEFAULT_TIME_FRAME = '30d';
    private ExtensionConfiguration $extensionConfiguration;
    private LanguageService $languageService;

    public function __construct(ExtensionConfiguration $extensionConfiguration, LanguageService $languageService)
    {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->languageService = $languageService;
        $this->languageService->includeLLFile('EXT:' . self::EXT_KEY . '/Resources/Private/Language/locallang.xlf');
    }

    public function getTimeFrames(): array
    {
        $timeFrames = $this->extensionConfiguration->get(self::EXT_KEY, 'timeFrames');
        $timeFrames = array_filter(explode(',', $timeFrames ?? ''));
        //$defaultTimeFrame = $this->extensionConfiguration->get(self::EXT_KEY, 'defaultTimeFrame') ?? DEFAULT_TIME_FRAME;
        if (empty($timeFrames)) {
            $timeFrames = [DEFAULT_TIME_FRAME];
        }
        $availableFrames = [];
        foreach ($timeFrames as $timeFrame) {
            $label = $this->getLabelForTimeFrameValue($timeFrame);
            if (!empty($label)) {
                $availableFrames[] = [
                    'value' => $timeFrame,
                    'label' => $label,
                    //'default' => $defaultTimeFrame === $timeFrame
                ];
            }
        }
        return $availableFrames;
    }

    public function getDefaultTimeFrameValue(): string
    {
        /*
        return array_filter($this->getTimeFrameValues(), static function ($elm) {
            return $elm['default'] ?? [];
        })['value'] ?? DEFAULT_TIME_FRAME;
        */
        return $this->extensionConfiguration->get(self::EXT_KEY, 'defaultTimeFrame') ?? DEFAULT_TIME_FRAME;
    }

    public function getTimeFrameValues(): array
    {
        $frames = [];
        foreach ($this->getTimeFrames() as $frame) {
            $frames[] = $frame['value'] ?? '';
        }
        return $frames;
    }

    public function getApiKey(): string
    {
        return $this->extensionConfiguration->get(self::EXT_KEY, 'apiKey');
    }

    public function getBaseUrl(): string
    {
        return $this->extensionConfiguration->get(self::EXT_KEY, 'baseUrl');
    }

    public function getDefaultSite(): string
    {
        return $this->extensionConfiguration->get(self::EXT_KEY, 'siteId');
    }

    public function isValidConfiguration(): bool
    {
        return $this->extensionConfiguration->get(self::EXT_KEY, 'timeFrames') &&
               $this->extensionConfiguration->get(self::EXT_KEY, 'siteId') &&
               $this->extensionConfiguration->get(self::EXT_KEY, 'baseUrl') &&
               $this->extensionConfiguration->get(self::EXT_KEY, 'apiKey');
    }

    private function getLabelForTimeFrameValue(string $timeFrame): string
    {
        preg_match('/(?<number>\d+)?(?<unit>\w+)/', $timeFrame, $matches);
        $label = $this->languageService->getLL('timeframes.' . $matches['unit']);
        return sprintf($label ?? '', $matches['number'] ?? '');
    }
}
