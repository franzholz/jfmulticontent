<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function ($extensionKey): void {
    $extensionConfiguration = GeneralUtility::makeInstance(
        ExtensionConfiguration::class
    )->get($extensionKey);
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey] = $extensionConfiguration;

    $listType = 'jfmulticontent_pi1';

    // Page module hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][$extensionKey] = 'JambageCom\\Jfmulticontent\\Hooks\\CmsBackend->getExtensionSummary';

    // Save the content
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$extensionKey] = \JambageCom\Jfmulticontent\Hooks\DataHandler::class;

    ExtensionManagementUtility::addPItoST43(
        $extensionKey,
        'pi1/class.tx_jfmulticontent_pi1.php',
        '_pi1',
        'list_type',
        1
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$extensionKey . 'MigrateFlexformSheetIdentifierUpdate'] =
    \JambageCom\Jfmulticontent\Updates\MigrateFlexformSheetIdentifierUpdate::class;

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

