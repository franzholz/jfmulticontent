<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey): void {
    ExtensionManagementUtility::registerPageTSConfigFile(
        $extensionKey,
        'Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig',
        'Multiple Content Element Wizard'
    );
}, 'jfmulticontent');
