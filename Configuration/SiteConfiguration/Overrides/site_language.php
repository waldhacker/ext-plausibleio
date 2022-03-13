<?php

defined('TYPO3') or die();

call_user_func(static function () {
    $configurationService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Waldhacker\Plausibleio\Services\ConfigurationService::class);

    $GLOBALS['SiteConfiguration']['site_language']['columns']['plausible_baseUrl'] = [
        'label' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:baseUrl.title',
        'description' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:baseUrl.description',
        'config' => [
            'eval' => 'trim',
            'type' => 'input',
            'default' => 'https://plausible.io/',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['columns']['plausible_apiKey'] = [
        'label' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:apiKey.title',
        'description' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:apiKey.description',
        'config' => [
            'eval' => 'trim',
            'type' => 'input',
            'default' => '',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['columns']['plausible_siteId'] = [
        'label' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:siteId.title',
        'description' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:siteId.description',
        'config' => [
            'eval' => 'trim',
            'type' => 'input',
            'default' => '',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['columns']['plausible_includeTrackingScript'] = [
        'label' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:includeTrackingScript.title',
        'description' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:includeTrackingScript.description',
        'onChange' => 'reload',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['columns']['plausible_trackingScriptBaseUrl'] = [
        'label' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:trackingScriptBaseUrl.title',
        'description' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:trackingScriptBaseUrl.description',
        'displayCond' => 'FIELD:plausible_includeTrackingScript:>:0',
        'config' => [
            'eval' => 'trim',
            'type' => 'input',
            'default' => 'https://plausible.io/',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['columns']['plausible_trackingScriptType'] = [
        'label' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:trackingScriptType.title',
        'description' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:trackingScriptType.description',
        'displayCond' => 'FIELD:plausible_includeTrackingScript:>:0',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'items' => [
                ['plausible.js (default)', 'plausible.js'],
                ['plausible.compat.js', 'plausible.compat.js'],
                ['plausible.outbound-links.js', 'plausible.outbound-links.js'],
                // ['plausible.hash.js', 'plausible.hash.js'],
                ['plausible.local.js', 'plausible.local.js'],
                ['plausible.manual.js', 'plausible.manual.js'],
                // ['plausible.exclusions.js', 'plausible.exclusions.js'],
            ],
            'default' => 'plausible.js',
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['columns']['plausible_auto404Tracking'] = [
        'label' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:auto404Tracking.title',
        'description' => 'LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:auto404Tracking.description',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ];

    $GLOBALS['SiteConfiguration']['site_language']['types']['1']['showitem'] .= '
        ,--div--;LLL:EXT:plausibleio/Resources/Private/Language/locallang_tca.xlf:site.tab,
            plausible_baseUrl,
            plausible_apiKey,
            plausible_siteId,
            --palette--;;plausible_trackingScript,
            plausible_auto404Tracking,
    ';

    $GLOBALS['SiteConfiguration']['site_language']['palettes']['plausible_trackingScript']['showitem'] .= '
            plausible_includeTrackingScript,
            --linebreak--,
            plausible_trackingScriptBaseUrl,
            plausible_trackingScriptType
    ';
});
