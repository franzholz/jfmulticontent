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
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
* @author	Juergen Furrer <juergen.furrer@gmail.com>
* @package	TYPO3
* @subpackage	tx_jfmulticontent
*/
class TemplaVoilaPlusController
{
    protected $cObj;

    public function getContentFromField($content, $conf)
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $pageID = $this->cObj->stdWrap($conf['pageID'], $conf['pageID.']);
        $field = $this->cObj->stdWrap($conf['field'], $conf['field.']);

        $row = null;
        if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'contentId')) {
            // SELECT * FROM `pages` WHERE `deleted`=0 AND `hidden`=0 AND `pid`=<mypid> AND `sys_language_uid`=<mylanguageid>
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $row = $queryBuilder->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageID, Connection::PARAM_INT))
                )
                ->andWhere(
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'contentId'), Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchAll();
        }

        if (!is_array($row)) {
            // SELECT * FROM `pages` WHERE `deleted`=0 AND `hidden`=0 AND `uid`=<mypid> AND sys_language_uid=0
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
            $row = $queryBuilder->select('*')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageID, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid',  $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
                ->executeQuery()
                ->fetchAll();
        }

        if (is_array($row)) {
            foreach ($row as $key => $val) {
                $tsfe->register['page_' . $key] = $val;
            }
        }

        $flexformXml = '';
        if (isset($row['tx_templavoilaplus_flex'])) {
            $flexformXml = $row['tx_templavoilaplus_flex'];
        } elseif (isset($row['tx_templavoila_flex'])) {
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
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }
}
