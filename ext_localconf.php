<?php

defined('TYPO3') || die('Access denied.');

use Psr\Log\LogLevel;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(function ($extensionKey): void {
    // $extensionConfiguration = GeneralUtility::makeInstance(
    //     ExtensionConfiguration::class
    // )->get($extensionKey);
    //
    // // Save the content
    // $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$extensionKey] =
    //     \JambageCom\Jfmulticontent\Hooks\DataHandler::class;
    //
    // if (!empty($extensionConfiguration['FILEWRITER'])) {
    //     $GLOBALS['TYPO3_CONF_VARS']['LOG']['JambageCom']['Jfmulticontent'] = [
    //         'writerConfiguration' => [
    //             // 1. Verwenden des PSR-3 String-Schlüssels ('debug')
    //             LogLevel::DEBUG => [
    //                 // 2. Nutzen des nativen Core-FileWriters
    //                 FileWriter::class => [
    //                     // Der Pfad zu Log-Dateien wird über Environment ausgelesen:
    //                     'logFile' => Environment::getVarPath() . '/log/jfmulticontent.log'
    //                 ]
    //             ]
    //         ],
    //     ];
    // }
}, 'jfmulticontent');

