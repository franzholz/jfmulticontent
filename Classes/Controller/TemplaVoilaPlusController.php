<?php

namespace JambageCom\Jfmulticontent\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 2011 Juergen Furrer <juergen.furrer@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
* @author	Juergen Furrer <juergen.furrer@gmail.com>
* @package	TYPO3
* @subpackage	tx_jfmulticontent
*/
class TemplaVoilaPlusController
{
    public $cObj;

    public function getContentFromField($content, $conf)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $pageID = $this->cObj->stdWrap($conf['pageID'], $conf['pageID.']);
        $field = $this->cObj->stdWrap($conf['field'], $conf['field.']);

        $row = NULL;
        if ($tsfe->sys_language_content) {
            if (
                version_compare(TYPO3_version, '9.0.0', '>=')
            ) {
                // SELECT * FROM `pages_language_overlay` WHERE `deleted`=0 AND `hidden`=0 AND `pid`=<mypid> AND `sys_language_uid`=<mylanguageid>
                $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('pages_language_overlay');
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer::class));
                $row = $queryBuilder->select('*')
                    ->from('pages_language_overlay')
                    ->where(
                        $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageID, \PDO::PARAM_INT))
                    )
                    ->andWhere(
                        $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($tsfe->sys_language_content, \PDO::PARAM_INT))
                    )
                    ->execute()
                    ->fetchAll();
            } else {
                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages_language_overlay', 'deleted=0 AND hidden=0 AND pid=' . intval($pageID) . ' AND sys_language_uid=' . $tsfe->sys_language_content, '', '', 1);
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                $GLOBALS['TYPO3_DB']->sql_free_result($res);
            }
        }

        if (!is_array($row)) {
            if (
                version_compare(TYPO3_version, '9.0.0', '>=')
            ) {
                // SELECT * FROM `pages` WHERE `deleted`=0 AND `hidden`=0 AND `uid`=<mypid> 
                $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('pages');
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer::class));
                $row = $queryBuilder->select('*')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageID, \PDO::PARAM_INT))
                    )
                    ->execute()
                    ->fetchAll();
            } else {
                $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'pages', 'deleted=0 AND hidden=0 AND uid='.intval($pageID), '', '', 1);
                $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
                $GLOBALS['TYPO3_DB']->sql_free_result($res);
            }
        }

        if (is_array($row)) {
            foreach ($row as $key => $val) {
                $tsfe->register['page_' . $key] = $val;
            }
        }

        $flexformXml = '';
        if (isset($row['tx_templavoilaplus_flex'])) {
            $flexformXml = $row['tx_templavoilaplus_flex'];
        } else if (isset($row['tx_templavoila_flex'])) {
            $flexformXml = $row['tx_templavoila_flex'];
        }
        $page_flex_array = GeneralUtility::xml2array($flexformXml);

        $content_ids = [];
        if (isset($page_flex_array['data'])) {
            if (isset($page_flex_array['data']['sDEF'])) {
                if (count($page_flex_array['data']['sDEF']['lDEF']) > 0) {
                    foreach ($page_flex_array['data']['sDEF']['lDEF'] as $key => $fields) {
                        if ($key == $field) {
                            $content_ids = array_merge($content_ids, GeneralUtility::trimExplode(',', $fields['vDEF']));
                        }
                    }
                }
            }
        }

        $content = null;
        foreach ($content_ids as $content_id) {
            $tsfe->register['uid'] = $content_id;
            $content .= $this->cObj->cObjGetSingle($conf['contentRender'], $conf['contentRender.']);
        }

        return $content;
    }
    
    /**
    * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
    */
    protected function getTypoScriptFrontendController ()
    {
        return $GLOBALS['TSFE'];
    }
}

