<?php

namespace JambageCom\Jfmulticontent\Backend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class that renders fields for the extensionmanager configuration
 *
 * @author     Juergen Furrer <juergen.furrer@gmail.com>
 * @package    TYPO3
 * @subpackage tx_jfmulticontent
 */
class TsparserExtensionUpdate
{
    /**
     * Shows the update Message
     *
     * @return	string
     */
    public function render(&$params, &$tsObj)
    {
        $out = '';

        if ($this->checkConfig() === false) {
            $out .= '
    <div style="position:absolute;top:10px;right:10px; width:300px;">
        <div class="typo3-message message-information">
            <div class="message-header">' . $GLOBALS['LANG']->sL('LLL:EXT:jfmulticontent/Resources/Private/Language/locallang.xlf:extmng.updatermsgInstall') . '</div>
            <div class="message-body">
                ' . $GLOBALS['LANG']->sL('LLL:EXT:jfmulticontent/Resources/Private/Language/locallang.xlf:extmng.updatermsg') . '<br />
            </div>
        </div>
    </div>';
        }

        return $out;
    }

    /**
    * Check the config for a given feature
    *
    * @return boolean
    */
    public function checkConfig()
    {
        $confDefault = [
            'useStoragePidOnly',
            'ttNewsCodes',
            'useSelectInsteadCheckbox',
            'useOwnUserFuncForPages',
            'openExternalLink',
            'showEmptyContent',
            'tabSelectByHash',
            'colPosOfIrreContent',
            'style',
            'classInner',
            'frontendErrorMsg',
            'anythingSliderThemeFolder',
            'anythingSliderModes',
            'easyAccordionSkinFolder',
        ];

        $extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('jfmulticontent');

        $confArr = $extensionConfiguration;
        foreach ($confDefault as $val) {
            if (!isset($confArr[$val]) && !isset($_POST['data'][$val])) {
                return false;
            }
        }
        return true;
    }
}
