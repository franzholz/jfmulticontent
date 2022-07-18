<?php
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
 * deprecated: 
 * use the class \JambageCom\Jfmulticontent\Controller\TemplaVoilaPlusController instead
 *
 * @author	Juergen Furrer <juergen.furrer@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_jfmulticontent
 */
class tx_jfmulticontent
{
    public $cObj;

    public function getContentFromTemplavoilaField($content, $conf)
    {
        $controller = GeneralUtility::makeInstance(
            \JambageCom\Jfmulticontent\Controller\TemplaVoilaPlusController::class
        );

        $content =
            $controller->getContentFromField($content, $conf);

        return $content;
    }
}


// XCLASS inclusion code
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/jfmulticontent/class.tx_jfmulticontent.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/jfmulticontent/class.tx_jfmulticontent.php']);
}

