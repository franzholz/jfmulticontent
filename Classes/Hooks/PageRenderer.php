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

use TYPO3\CMS\Core\Utility\GeneralUtility;
 
/**
 * This class implements a all needed functions to add Javascripts and Stylesheets to a page
 *
 * @author     Juergen Furrer <juergen.furrer@gmail.com>
 * @package    TYPO3
 * @subpackage tx_jfmulticontent
 */
class PageRenderer
{
	public $conf = array();
	public $extKey = 'jfmulticontent';
	private $jsFiles = array();
	private $js = array();
	private $cssFiles = array();
	private $cssFilesInc = array();
	private $css = array();

	/**
	 * Set the configuration for the pagerenderer
	 * @param array $conf
	 */
	public function setConf($conf) {
		$this->conf = $conf;
	}

	/**
	* Include all defined resources (JS / CSS)
	*
	* @return void
	*/
	public function addResources() {
        $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class) ;
		// Fix moveJsFromHeaderToFooter (add all scripts to the footer)
		if ($GLOBALS['TSFE']->config['config']['moveJsFromHeaderToFooter']) {
			$allJsInFooter = true;
		} else {
			$allJsInFooter = false;
		}

		// add all defined JS files
		if (count($this->jsFiles) > 0) {
			foreach ($this->jsFiles as $jsToLoad) {
				if (T3JQUERY === true) {
					$conf = array(
						'jsfile' => $jsToLoad,
						'tofooter' => ($this->conf['jsInFooter'] || $allJsInFooter),
						'jsminify' => $this->conf['jsMinify'],
					);
					tx_t3jquery::addJS('', $conf);
				} else {
					$file = $this->getPath($jsToLoad);
					if ($file) {
                        if ($this->conf['jsInFooter'] || $allJsInFooter) {
                            $pageRenderer->addJsFooterFile($file, 'text/javascript', $this->conf['jsMinify']);
                        } else {
                            $pageRenderer->addJsFile($file, 'text/javascript', $this->conf['jsMinify']);
                        }
					} else {
                        $logger = $this->getLogger();
                        $logger->error('File "' . $jsToLoad . '" does not exist!', []);
					}
				}
			}
		}
		// add all defined JS script
		if (count($this->js) > 0) {
			foreach ($this->js as $jsToPut) {
				$temp_js .= $jsToPut;
			}
			$conf = array();
			$conf['jsdata'] = $temp_js;
			if (T3JQUERY === true && class_exists(\TYPO3\CMS\Core\Utility\VersionNumberUtility::class) && \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($this->getExtensionVersion('t3jquery')) >= 1002000) {
				$conf['tofooter'] = ($this->conf['jsInFooter'] || $allJsInFooter);
				$conf['jsminify'] = $this->conf['jsMinify'];
				$conf['jsinline'] = $this->conf['jsInline'];
				tx_t3jquery::addJS('', $conf);
			} else {
				// Add script only once
				$hash = md5($temp_js);
				if ($this->conf['jsInline']) {
					$GLOBALS['TSFE']->inlineJS[$hash] = $temp_js;
				} else {
					if ($this->conf['jsInFooter'] || $allJsInFooter) {
						$pageRenderer->addJsFooterInlineCode($hash, $temp_js, $this->conf['jsMinify']);
					} else {
						$pageRenderer->addJsInlineCode($hash, $temp_js, $this->conf['jsMinify']);
					}
				}
			}
		}
		// add all defined CSS files
		if (count($this->cssFiles) > 0) {
			foreach ($this->cssFiles as $cssToLoad) {
				// Add script only once
				$file = $this->getPath($cssToLoad);
				if ($file) {
                    $pageRenderer->addCssFile($file, 'stylesheet', 'all', '', $this->conf['cssMinify']);
				} else {
                    $logger = $this->getLogger();
                    $logger->error('File "' . $cssToLoad . '" does not exist!', []);
				}
			}
		}
		// add all defined CSS files for IE
		if (count($this->cssFilesInc) > 0) {
			foreach ($this->cssFilesInc as $cssToLoad) {
				// Add script only once
				$file = $this->getPath($cssToLoad['file']);
				if ($file) {
					// Theres no possibility to add conditions for IE by pagerenderer, so this will be added in additionalHeaderData
                    $headerKey = 'cssFile_' . $this->extKey . '_' . $file;
                    $headerData = '<!--[if ' . $cssToLoad['rule'] . ']><link rel="stylesheet" type="text/css" href="' . $file . '" media="all" /><![endif]-->' . CRLF;
                    if (version_compare(TYPO3_version, '9.5.0', '>=')) {
                        $pageRenderer->addHeaderData($headerData);
                    } else {
                        $GLOBALS['TSFE']->additionalHeaderData[$headerKey] = $headerData;
					}
				} else {
                    $logger = $this->getLogger();
                    $logger->error('File "' . $cssToLoad['file'] . '" does not exist!', []);
				}
			}
		}
		// add all defined CSS Script
		if (count($this->css) > 0) {
			foreach ($this->css as $cssToPut) {
				$temp_css .= $cssToPut;
			}
			$hash = md5($temp_css);
            $pageRenderer->addCssInlineBlock($hash, $temp_css, $this->conf['cssMinify']);
		}
	}

	/**
	 * Return the webbased path
	 *
	 * @param string $path
	 * return string
	 */
	public function getPath($path = '') {
        $result = '';
        if (
            version_compare(TYPO3_version, '9.4.0', '>=')
        ) {
            $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
            $result = $sanitizer->sanitize($path);
        } else {
            $result = $GLOBALS['TSFE']->tmpl->getFileName($path);
        }

		return $result;
	}

	/**
	 * Add additional JS file
	 *
	 * @param string $script
	 * @param boolean $first
	 * @return void
	 */
	public function addJsFile($script = '', $first = false) {
		if ($this->getPath($script) && ! in_array($script, $this->jsFiles)) {
			if ($first === true) {
				$this->jsFiles = array_merge(array($script), $this->jsFiles);
			} else {
				$this->jsFiles[] = $script;
			}
		}
	}

	/**
	 * Add JS to header
	 *
	 * @param string $script
	 * @return void
	 */
	public function addJS($script = '') {
		if (! in_array($script, $this->js)) {
			$this->js[] = $script;
		}
	}

	/**
	 * Add additional CSS file
	 *
	 * @param string $script
	 * @return void
	 */
	public function addCssFile($script = '') {
		if ($this->getPath($script) && !in_array($script, $this->cssFiles)) {
			$this->cssFiles[] = $script;
		}
	}

	/**
	 * Add additional CSS file to include into IE only
	 *
	 * @param string $script
	 * @param string $include for example use "lte IE 7"
	 * @return void
	 */
	public function addCssFileInc($script = '', $include = 'IE') {
		if ($this->getPath($script) && ! in_array($script, $this->cssFiles) && $include) {
			$this->cssFilesInc[] = array(
				'file' => $script,
				'rule' => $include,
			);
		}
	}

	/**
	 * Add CSS to header
	 *
	 * @param string $script
	 * @return void
	 */
	public function addCSS($script = '') {
		if (! in_array($script, $this->css)) {
			$this->css[] = $script;
		}
	}

	/**
	 * Returns the version of an extension (in 4.4 its possible to this with \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion)
	 * @param string $key
	 * @return string
	 */
	public function getExtensionVersion($key) {
		if (! \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($key)) {
			return '';
		}
		$_EXTKEY = $key;
		include(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($key) . 'ext_emconf.php');
		return $EM_CONF[$key]['version'];
	}

    /**
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected function getLogger()
    {
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $result = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
        return $result;
    }
}

