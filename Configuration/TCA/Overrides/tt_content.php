<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {
    $extensionKey = 'jfmulticontent';
    $table = 'tt_content';
    $listType = $extensionKey . '_pi1';

    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout,pages';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'tx_jfmulticontent_view,tx_jfmulticontent_pages,tx_jfmulticontent_contents,tx_jfmulticontent_irre,pi_flexform';
    // Add reload field to tt_content
    if (!isset($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'])) {
        $GLOBALS['TCA'][$table]['ctrl']['requestUpdate'] = '';
    }
    $GLOBALS['TCA'][$table]['ctrl']['requestUpdate'] .= ($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'] ? ',' : '') . 'tx_jfmulticontent_view';

    $confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey];
    $colPosOfIrreContent = intval($confArr['colPosOfIrreContent']);

    if (
        !isset($GLOBALS['TCA'][$table]['columns']['colPos']['config']['items'][$colPosOfIrreContent])
    ) {
        // Add the new colPos to the array, only if the ID does not exist...
        $GLOBALS['TCA'][$table]['columns']['colPos']['config']['items'][$colPosOfIrreContent] = [
                'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.colPosOfIrreContent',
                $colPosOfIrreContent
        ];
    //     $GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['disableNoMatchingValueElement'] = 1; // I have commented this out.
    }

    $temporaryColumns = [
        'tx_jfmulticontent_view' => [
            'exclude' => 1,
            'onChange' => 'reload',
            'label' => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.view',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'maxitems' => 1,
                'default' => 'content',
                'items' => [
                    ['LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.view.I.0', 'content'],
                    ['LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.view.I.1', 'page'],
                    ['LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.view.I.2', 'irre'],
                ],
                'itemsProcFunc' => \JambageCom\Jfmulticontent\Hooks\ItemsProcFunc::class . '->getViews',
            ]
        ],
        'tx_jfmulticontent_pages' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:tx_jfmulticontent_view:IN:page',
            'label' => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.pages',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 12,
                'minitems' => 0,
                'maxitems' => 1000,
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest',
                    ],
                ],
            ]
        ],
        'tx_jfmulticontent_irre' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:tx_jfmulticontent_view:IN:irre',
            'label' => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.irre',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tt_content',
                'foreign_field' => 'tx_jfmulticontent_irre_parentid',
                'foreign_sortby' => 'sorting',
                'foreign_label' => 'header',
                'maxitems' => 1000,
                'appearance' => [
                    'showSynchronizationLink' => false,
                    'showAllLocalizationLink' => false,
                    'showPossibleLocalizationRecords' => false,
                    'showRemovedLocalizationRecords' => false,
                    'expandSingle' => true,
                    'newRecordLinkAddTitle' => true,
                    'useSortable' => true,
                ],
                'behaviour' => [
                    'localizationMode' => 'select',
                ],
            ]
        ],
    ];

    if (!empty($confArr['useStoragePidOnly'])) {

        $foreignTableWhere = 'AND {#tt_content}.{#pid} = ###PAGE_TSCONFIG_ID### AND {#tt_content}.{#hidden} = 0 AND {#tt_content}.{#deleted} = 0 AND {#tt_content}.{#sys_language_uid} IN (0,-1) ORDER BY tt_content.uid';

        $temporaryColumns['tx_jfmulticontent_contents'] = [
            'exclude' => 1,
            'displayCond' => 'FIELD:tx_jfmulticontent_view:IN:content',
            'label' => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.contents',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tt_content',
                'foreign_table_where' => $foreignTableWhere,
                'size' => 6,
                'autoSizeMax' => 20,
                'minitems' => 0,
                'maxitems' => 1000,
                'fieldControl' => [ 
                    'editPopup' => [ 
                        'disabled' => false,
                        'options' => [
                            'title'  => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.contents_edit'
                        ]
                    ],
                    'addRecord' => [ 
                        'disabled' => true,
                    ],
                    'listModule' => [ 
                        'disabled' => false,
                    ],
                ]
            ]
        ];

    } else {
        $temporaryColumns['tx_jfmulticontent_contents'] = [
            'exclude' => 1,
            'displayCond' => 'FIELD:tx_jfmulticontent_view:IN:content',
            'label' => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.contents',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tt_content',
                'size' => 12,
                'minitems' => 0,
                'maxitems' => 1000,
                'suggestOptions' => [ 
                'default' => [ 
                    'pidList' => '###PAGE_TSCONFIG_ID###',
                ],
                ],
                'fieldControl' => [
                    'elementBrowser' => [
                        'disabled' => '0',
                    ],
                    'addRecord' => [
                        'disabled' => '0',
                        'pid' => '###PAGE_TSCONFIG_ID###',
                        'options' => [
                            'title'  => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.contents_add'
                        ]
                    ],
                    'editPopup' => [
                        'disabled' => false,
                        'options' => [
                            'title'  => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.tx_jfmulticontent.contents_edit'
                        ]
                    ],
                    'listModule' => [
                        'disabled' => false
                    ],
                ]
            ]
        ];
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns($table, $temporaryColumns);

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . $extensionKey . '/Configuration/FlexForms/flexform_ds.xml');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
            $listType,
            'EXT:' . $extensionKey . '/Resources/Public/Icons/Extension.gif'
        ],
        'list_type',
        $extensionKey
    );
    
});

