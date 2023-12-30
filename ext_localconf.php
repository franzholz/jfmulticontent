<?php

defined('TYPO3') || die('Access denied.');

call_user_func(function ($extensionKey): void {
    if (!defined('T3JQUERY')) {
        define('T3JQUERY', false);
    }

    $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get($extensionKey);
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey] = $extensionConfiguration;

    if ($extensionConfiguration['ttNewsCodes']) {
        // Add the additional CODES to tt_news
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'][] = [
            0 => 'LIST_ACCORDION',
            1 => 'LIST_ACCORDION'
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'][] = [
            0 => 'LIST_SLIDER',
            1 => 'LIST_SLIDER'
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'][] = [
            0 => 'LIST_SLIDEDECK',
            1 => 'LIST_SLIDEDECK'
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['what_to_display'][] = [
            0 => 'LIST_EASYACCORDION',
            1 => 'LIST_EASYACCORDION'
        ];
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraCodesHook'][] = \JambageCom\Jfmulticontent\Hooks\TtNewsExtend::class;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['tt_news']['extraGlobalMarkerHook'][] = \JambageCom\Jfmulticontent\Hooks\TtNewsExtend::class;
    }

    $listType = 'jfmulticontent_pi1';

    // Page module hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$listType][$extensionKey] = 'JambageCom\\Jfmulticontent\\Hooks\\CmsBackend->getExtensionSummary';

    // Save the content
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$extensionKey] = \JambageCom\Jfmulticontent\Hooks\DataHandler::class;

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
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

