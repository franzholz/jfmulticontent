<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(function () {

    $table = 'tt_content';

    $listType = JFMULTICONTENT_EXT . '_pi1';

    $GLOBALS['TCA'][$table]['types']['list']['subtypes_excludelist'][$listType] = 'layout,select_key,pages';
    $GLOBALS['TCA'][$table]['types']['list']['subtypes_addlist'][$listType] = 'tx_jfmulticontent_view,tx_jfmulticontent_pages,tx_jfmulticontent_contents,tx_jfmulticontent_irre,pi_flexform';
    // Add reload field to tt_content
    $GLOBALS['TCA'][$table]['ctrl']['requestUpdate'] .= ($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'] ? ',' : '') . 'tx_jfmulticontent_view';

    $confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][JFMULTICONTENT_EXT];
    $colPosOfIrreContent = intval($confArr['colPosOfIrreContent']);

    if (!isset($GLOBALS['TCA'][$table]['columns']['colPos']['config']['items'][$colPosOfIrreContent])) {
        // Add the new colPos to the array, only if the ID does not exist...
        $GLOBALS['TCA'][$table]['columns']['colPos']['config']['items'][$colPosOfIrreContent] = [
                'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.colPosOfIrreContent',
                $colPosOfIrreContent
        ];
    //     $GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['disableNoMatchingValueElement'] = 1; // I have commented this out.
    }

    $temporaryColumns = [
        'tx_jfmulticontent_view' => [
            'exclude' => 1,
            'onChange' => 'reload',
            'label' => 'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.view',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'maxitems' => 1,
                'default' => 'content',
                'items' => [
                    ['LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.view.I.0', 'content'],
                    ['LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.view.I.1', 'page'],
                    ['LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.view.I.2', 'irre'],
                ],
                'itemsProcFunc' => \JambageCom\Jfmulticontent\Hooks\ItemsProcFunc::class . '->getViews',
            ]
        ],
        'tx_jfmulticontent_pages' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:tx_jfmulticontent_view:IN:page',
            'label' => 'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.pages',
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
            'label' => 'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.irre',
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
                    'localizeChildrenAtParentLocalization' => 1,
                    'localizationMode' => 'select',
                ],
            ]
        ],
    ];

    if ($confArr['useStoragePidOnly']) {

        $foreignTableWhere = '';
        if (
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $foreignTableWhere = 'AND {#tt_content}.{#pid} = ###PAGE_TSCONFIG_ID### AND {#tt_content}.{#hidden} = 0 AND {#tt_content}.{#deleted} = 0 AND {#tt_content}.{#sys_language_uid} IN (0,-1) ORDER BY tt_content.uid';
        } else {
            $foreignTableWhere = 'AND tt_content.pid=###PAGE_TSCONFIG_ID### AND tt_content.hidden=0 AND tt_content.deleted=0 AND tt_content.sys_language_uid IN (0,-1) ORDER BY tt_content.uid';
        }

        $temporaryColumns['tx_jfmulticontent_contents'] = [
            'exclude' => 1,
            'displayCond' => 'FIELD:tx_jfmulticontent_view:IN:content',
            'label' => 'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.contents',
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
                            'title'  => 'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.contents_edit'
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
            'label' => 'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.contents',
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
                        'disabled' => $confArr['useStoragePidOnly'] == '1',
                    ],
                    'addRecord' => [
                        'disabled' => $confArr['useStoragePidOnly'] == '1',
                        'pid' => '###PAGE_TSCONFIG_ID###',
                        'options' => [
                            'title'  => 'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.contents_add'
                        ]
                    ],
                    'editPopup' => [
                        'disabled' => false,
                        'options' => [
                            'title'  => 'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.tx_jfmulticontent.contents_edit'
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

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($listType, 'FILE:EXT:' . JFMULTICONTENT_EXT . '/flexform_ds.xml');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'LLL:EXT:' . JFMULTICONTENT_EXT . '/locallang_db.xml:tt_content.list_type_pi1',
            $listType,
            'EXT:' . JFMULTICONTENT_EXT . '/ext_icon.gif'
        ],
        'list_type',
        JFMULTICONTENT_EXT
    );

});

