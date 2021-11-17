<?php

defined('TYPO3') or die();

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'plausibleio',
        'setup',
        "@import 'EXT:plausibleio/Configuration/TypoScript/setup.typoscript'"
    );
});
