<?php

namespace JambageCom\Jfmulticontent\Hooks;

/***************************************************************
*  Copyright notice
*
*  (c) 2009 Juergen Furrer <juergen.furrer@gmail.com>
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
 * 'cms_layout' for the 'jfmulticontent' extension.
 *
 * @author     Juergen Furrer <juergen.furrer@gmail.com>
 * @package    TYPO3
 * @subpackage tx_jfmulticontent
 */
class CmsBackend
{
    /**
     * Returns information about this extension's pi1 plugin
     *
     * @param  array  $params Parameters to the hook
     * @param  object $pObj   A reference to calling object
     * @return string Information about pi1 plugin
     */
    public function getExtensionSummary($params, &$pObj)
    {
        $result = '';

        if ($params['row']['list_type'] == 'jfmulticontent_pi1') {
            $data = GeneralUtility::xml2array($params['row']['pi_flexform']);

            if (is_array($data) && $data['data']['s_general']['lDEF']['style']['vDEF']) {
                $result = sprintf($GLOBALS['LANG']->sL('LLL:EXT:jfmulticontent/Resources/Private/Language/locallang.xlf:cms_layout.style'), '<strong>' . $data['data']['s_general']['lDEF']['style']['vDEF'] . '</strong><br/>');
            }
            if (!$result) {
                $result = $GLOBALS['LANG']->sL('LLL:EXT:jfmulticontent/Resources/Private/Language/locallang.xlf:cms_layout.not_configured') . '<br/>';
            }
        }

        if (
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('templavoilaplus') ||
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('templavoila')
        ) {
            $result = strip_tags($result);
        }
        return $result;
    }
}
