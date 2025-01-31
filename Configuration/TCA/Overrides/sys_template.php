<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function ($extensionKey): void {
    ExtensionManagementUtility::addStaticFile(
        $extensionKey,
        'Configuration/TypoScript/PluginSetup/',
        'Multiple Content'
    );
}, 'jfmulticontent');
