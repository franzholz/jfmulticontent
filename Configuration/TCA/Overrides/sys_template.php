<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {

    if (!defined ('JFMULTICONTENT_EXT')) {
        define('JFMULTICONTENT_EXT', 'jfmulticontent');
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        JFMULTICONTENT_EXT,
        'Configuration/TypoScript/PluginSetup/',
        'Multi content'
    );

});

