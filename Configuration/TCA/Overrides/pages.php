<?php

use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(function () {
    ExtensionManagementUtility::registerPageTSConfigFile(
        'jfmulticontent',
        'Configuration/TsConfig/Page/Mod/Wizards/NewContentElement.tsconfig',
        'Multiple Content Element Wizard'
    );
});

