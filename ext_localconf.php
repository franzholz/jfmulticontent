<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function ($extensionKey): void {
    $extensionConfiguration = GeneralUtility::makeInstance(
        ExtensionConfiguration::class
    )->get($extensionKey);

    // Save the content
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$extensionKey] = \JambageCom\Jfmulticontent\Hooks\DataHandler::class;

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['JambageCom']['Jfmulticontent'] = [
        'writerConfiguration' => [
            \TYPO3\CMS\Core\Log\LogLevel::DEBUG => [
                \JambageCom\Jfmulticontent\Log\Writer\FileWriter::class => [
                    'mode' => $extensionConfiguration['FILEWRITER']
                ]
            ]
        ],
    ];
}, 'jfmulticontent');

