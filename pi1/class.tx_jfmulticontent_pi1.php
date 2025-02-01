<?php
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
use Quellenform\LibJquery\Hooks\PageRendererHook;

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Compatibility\AbstractPlugin;

/**
 * Plugin 'Multiple Content' for the 'jfmulticontent' extension.
 *
 * @author     Juergen Furrer <juergen.furrer@gmail.com>
 * @package    TYPO3
 * @subpackage tx_jfmulticontent
 */
class tx_jfmulticontent_pi1 extends AbstractPlugin
{
    public $prefixId      = 'tx_jfmulticontent_pi1';
    public $scriptRelPath = 'pi1/class.tx_jfmulticontent_pi1.php';
    public $extKey        = 'jfmulticontent';
    public $pi_checkCHash = true;
    public $conf = [];
    private $lConf = [];
    private $confArr = [];
    private $templateFile = null;
    private $templateFileJS = null;
    private $templatePart = null;
    private $additionalMarker = [];
    private $contentKey = null;
    private $contentCount = null;
    private $contentClass = [];
    private $classes = [];
    private $contentWrap = [];
    private $titles = [];
    private $attributes = [];
    private $cElements = [];
    private $rels = [];
    private $content_id = [];
    private $piFlexForm = [];
    private $pagerenderer = null;

    /**
     * The main method of the PlugIn
     *
     * @param	string		$content: The PlugIn content
     * @param	array		$conf: The PlugIn configuration
     * @return	The content that is displayed on the website
     */
    public function main(
        string $content,
        array $conf,
        ServerRequestInterface $request,
    ) : string
    {
        $this->conf = $conf;
        $this->pi_setPiVarDefaults();
        $this->pi_loadLL('LLL:EXT:' . $this->extKey . '/Resources/Private/Language/Pi1/locallang.xlf');

        $context = GeneralUtility::makeInstance(Context::class);
        /** @var LanguageAspect $languageAspect */
        $languageAspect = $context->getAspect('language');
        $versioningWorkspaceId = $context->getPropertyFromAspect('workspace', 'id');
        $tsfe = $this->getTypoScriptFrontendController();
        $this->setFlexFormData();
        $jQueryAvailable = false;
        if (class_exists(PageRendererHook::class)) {
            $jQueryAvailable = true;
        }

        // get the config from EXT
        $this->confArr = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey];
        $parser = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $this->pagerenderer = GeneralUtility::makeInstance(\JambageCom\Jfmulticontent\Hooks\MultiPageRenderer::class);
        $this->pagerenderer->setConf($this->conf);

        // Plugin or template?
        if (
            isset($this->cObj->data['list_type']) &&
            $this->cObj->data['list_type'] == $this->extKey . '_pi1'
        ) {
            // It's a content, all data from flexform

            $this->lConf['style'] = $this->getFlexformData('s_general', 'style');

            if ($this->lConf['style'] != 'typoscript') {
                $this->lConf['columnOrder'] = $this->getFlexformData('s_general', 'columnOrder', in_array($this->lConf['style'], ['2column', '3column', '4column', '5column']));
                $this->lConf['column1']     = $this->getFlexformData('s_general', 'column1', in_array($this->lConf['style'], ['2column', '3column', '4column', '5column']));
                $this->lConf['column2']     = $this->getFlexformData('s_general', 'column2', in_array($this->lConf['style'], ['2column', '3column', '4column', '5column']));
                $this->lConf['column3']     = $this->getFlexformData('s_general', 'column3', in_array($this->lConf['style'], ['3column', '4column', '5column']));
                $this->lConf['column4']     = $this->getFlexformData('s_general', 'column4', in_array($this->lConf['style'], ['4column', '5column']));
                $this->lConf['column5']     = $this->getFlexformData('s_general', 'column5', in_array($this->lConf['style'], ['5column']));
                $this->lConf['equalize']    = $this->getFlexformData('s_general', 'equalize', in_array($this->lConf['style'], ['1column', '2column', '3column', '4column', '5column']));

                $debuglog = ($this->lConf['style'] == 'tab');
                $this->lConf['tabCollapsible']   = $this->getFlexformData('s_general', 'tabCollapsible', $debuglog);
                $this->lConf['tabOpen']          = $this->getFlexformData('s_general', 'tabOpen', $debuglog);
                $this->lConf['tabRandomContent'] = $this->getFlexformData('s_general', 'tabRandomContent', $debuglog);
                $this->lConf['tabEvent']         = $this->getFlexformData('s_general', 'tabEvent', $debuglog);
                $this->lConf['tabHeightStyle']   = $this->getFlexformData('s_general', 'tabHeightStyle', $debuglog);
                $this->lConf['tabCookieExpires'] = $this->getFlexformData('s_general', 'tabCookieExpires', $debuglog);
                $this->lConf['tabCookieRoot']    = $this->getFlexformData('s_general', 'tabCookieRoot', $debuglog);
                $this->lConf['tabHideEffect']             = $this->getFlexformData('s_general', 'tabHideEffect', $debuglog);
                $this->lConf['tabHideTransition']         = $this->getFlexformData('s_general', 'tabHideTransition', $debuglog);
                $this->lConf['tabHideTransitiondir']      = $this->getFlexformData('s_general', 'tabHideTransitiondir', $debuglog);
                $this->lConf['tabHideTransitionduration'] = $this->getFlexformData('s_general', 'tabHideTransitionduration', $debuglog);
                $this->lConf['tabShowEffect']             = $this->getFlexformData('s_general', 'tabShowEffect', $debuglog);
                $this->lConf['tabShowTransition']         = $this->getFlexformData('s_general', 'tabShowTransition', $debuglog);
                $this->lConf['tabShowTransitiondir']      = $this->getFlexformData('s_general', 'tabShowTransitiondir', $debuglog);
                $this->lConf['tabShowTransitionduration'] = $this->getFlexformData('s_general', 'tabShowTransitionduration', $debuglog);

                $debuglog = ($this->lConf['style'] == 'accordion');
                $this->lConf['accordionCollapsible']        = $this->getFlexformData('s_general', 'accordionCollapsible', $debuglog);
                $this->lConf['accordionClosed']             = $this->getFlexformData('s_general', 'accordionClosed', $debuglog);
                $this->lConf['accordionOpen']               = $this->getFlexformData('s_general', 'accordionOpen', $debuglog);
                $this->lConf['accordionRandomContent']      = $this->getFlexformData('s_general', 'accordionRandomContent', $debuglog);
                $this->lConf['accordionEvent']              = $this->getFlexformData('s_general', 'accordionEvent', $debuglog);
                $this->lConf['accordionHeightStyle']        = $this->getFlexformData('s_general', 'accordionHeightStyle', $debuglog);
                $this->lConf['accordionAnimate']            = $this->getFlexformData('s_general', 'accordionAnimate', $debuglog);
                $this->lConf['accordionTransition']         = $this->getFlexformData('s_general', 'accordionTransition', $debuglog);
                $this->lConf['accordionTransitiondir']      = $this->getFlexformData('s_general', 'accordionTransitiondir', $debuglog);
                $this->lConf['accordionTransitionduration'] = $this->getFlexformData('s_general', 'accordionTransitionduration', $debuglog);

                $debuglog = ($this->lConf['style'] == 'slider');
                $this->lConf['sliderWidth']              = $this->getFlexformData('s_general', 'sliderWidth', $debuglog);
                $this->lConf['sliderHeight']             = $this->getFlexformData('s_general', 'sliderHeight', $debuglog);
                $this->lConf['sliderResizeContents']     = $this->getFlexformData('s_general', 'sliderResizeContents', $debuglog);
                $this->lConf['sliderTheme']              = $this->getFlexformData('s_general', 'sliderTheme', $debuglog);
                $this->lConf['sliderMode']               = $this->getFlexformData('s_general', 'sliderMode', $debuglog);
                $this->lConf['sliderOpen']               = $this->getFlexformData('s_general', 'sliderOpen', $debuglog);
                $this->lConf['sliderRandomContent']      = $this->getFlexformData('s_general', 'sliderRandomContent', $debuglog);
                $this->lConf['sliderHashTags']           = $this->getFlexformData('s_general', 'sliderHashTags', $debuglog);
                $this->lConf['sliderBuildArrows']        = $this->getFlexformData('s_general', 'sliderBuildArrows', $debuglog);
                $this->lConf['sliderToggleArrows']       = $this->getFlexformData('s_general', 'sliderToggleArrows', $debuglog);
                $this->lConf['sliderNavigation']         = $this->getFlexformData('s_general', 'sliderNavigation', $debuglog);
                $this->lConf['sliderStartStop']          = $this->getFlexformData('s_general', 'sliderStartStop', $debuglog);
                $this->lConf['sliderPanelFromHeader']    = $this->getFlexformData('s_general', 'sliderPanelFromHeader', $debuglog);
                $this->lConf['sliderToggleControls']     = $this->getFlexformData('s_general', 'sliderToggleControls', $debuglog);
                $this->lConf['sliderAutoStart']          = $this->getFlexformData('s_general', 'sliderAutoStart', $debuglog);
                $this->lConf['sliderPauseOnHover']       = $this->getFlexformData('s_general', 'sliderPauseOnHover', $debuglog);
                $this->lConf['sliderAllowRapidChange']   = $this->getFlexformData('s_general', 'sliderAllowRapidChange', $debuglog);
                $this->lConf['sliderResumeOnVideoEnd']   = $this->getFlexformData('s_general', 'sliderResumeOnVideoEnd', $debuglog);
                $this->lConf['sliderStopAtEnd']          = $this->getFlexformData('s_general', 'sliderStopAtEnd', $debuglog);
                $this->lConf['sliderPlayRtl']            = $this->getFlexformData('s_general', 'sliderPlayRtl', $debuglog);
                $this->lConf['sliderTransition']         = $this->getFlexformData('s_general', 'sliderTransition', $debuglog);
                $this->lConf['sliderTransitiondir']      = $this->getFlexformData('s_general', 'sliderTransitiondir', $debuglog);
                $this->lConf['sliderTransitionduration'] = $this->getFlexformData('s_general', 'sliderTransitionduration', $debuglog);
                $this->lConf['sliderAutoplay']           = $this->getFlexformData('s_general', 'sliderAutoplay', $debuglog);

                $debuglog = ($this->lConf['style'] == 'slidedeck');
                $this->lConf['slidedeckHeight']             = $this->getFlexformData('s_general', 'slidedeckHeight', $debuglog);
                $this->lConf['slidedeckTransition']         = $this->getFlexformData('s_general', 'slidedeckTransition', $debuglog);
                $this->lConf['slidedeckTransitiondir']      = $this->getFlexformData('s_general', 'slidedeckTransitiondir', $debuglog);
                $this->lConf['slidedeckTransitionduration'] = $this->getFlexformData('s_general', 'slidedeckTransitionduration', $debuglog);
                $this->lConf['slidedeckStart']              = $this->getFlexformData('s_general', 'slidedeckStart', $debuglog);
                $this->lConf['slidedeckActivecorner']       = $this->getFlexformData('s_general', 'slidedeckActivecorner', $debuglog);
                $this->lConf['slidedeckIndex']              = $this->getFlexformData('s_general', 'slidedeckIndex', $debuglog);
                $this->lConf['slidedeckScroll']             = $this->getFlexformData('s_general', 'slidedeckScroll', $debuglog);
                $this->lConf['slidedeckKeys']               = $this->getFlexformData('s_general', 'slidedeckKeys', $debuglog);
                $this->lConf['slidedeckHidespines']         = $this->getFlexformData('s_general', 'slidedeckHidespines', $debuglog);

                $debuglog = ($this->lConf['style'] == 'easyaccordion');
                $this->lConf['easyaccordionSkin']     = $this->getFlexformData('s_general', 'easyaccordionSkin', $debuglog);
                $this->lConf['easyaccordionOpen']     = $this->getFlexformData('s_general', 'easyaccordionOpen', $debuglog);
                $this->lConf['easyaccordionWidth']    = $this->getFlexformData('s_general', 'easyaccordionWidth', $debuglog);
                $this->lConf['easyaccordionSlideNum'] = $this->getFlexformData('s_general', 'easyaccordionSlideNum', $debuglog);

                $debuglog = ($this->lConf['style'] == 'booklet');
                $this->lConf['bookletWidth']         = $this->getFlexformData('s_general', 'bookletWidth', $debuglog);
                $this->lConf['bookletHeight']        = $this->getFlexformData('s_general', 'bookletHeight', $debuglog);
                $this->lConf['bookletSpeed']         = $this->getFlexformData('s_general', 'bookletSpeed', $debuglog);
                $this->lConf['bookletStartingPage']  = $this->getFlexformData('s_general', 'bookletStartingPage', $debuglog);
                $this->lConf['bookletRTL']           = $this->getFlexformData('s_general', 'bookletRTL', $debuglog);
                $this->lConf['bookletTransition']    = $this->getFlexformData('s_general', 'bookletTransition', $debuglog);
                $this->lConf['bookletTransitiondir'] = $this->getFlexformData('s_general', 'bookletTransitiondir', $debuglog);
                $this->lConf['bookletPagePadding']   = $this->getFlexformData('s_general', 'bookletPagePadding', $debuglog);
                $this->lConf['bookletPageNumbers']   = $this->getFlexformData('s_general', 'bookletPageNumbers', $debuglog);
                $this->lConf['bookletManual']        = $this->getFlexformData('s_general', 'bookletManual', $debuglog);
                $this->lConf['bookletShadows']       = $this->getFlexformData('s_general', 'bookletShadows', $debuglog);
                $this->lConf['bookletClosed']        = $this->getFlexformData('s_general', 'bookletClosed', $debuglog);
                $this->lConf['bookletCovers']        = $this->getFlexformData('s_general', 'bookletCovers', $debuglog);
                $this->lConf['bookletAutoCenter']    = $this->getFlexformData('s_general', 'bookletAutoCenter', $debuglog);
                $this->lConf['bookletHash']          = $this->getFlexformData('s_general', 'bookletHash', $debuglog);
                $this->lConf['bookletKeyboard']      = $this->getFlexformData('s_general', 'bookletKeyboard', $debuglog);
                $this->lConf['bookletAuto']          = $this->getFlexformData('s_general', 'bookletAuto', $debuglog);
                $this->lConf['bookletDelay']         = $this->getFlexformData('s_general', 'bookletDelay', $debuglog);
                $this->lConf['bookletOverlays']      = $this->getFlexformData('s_general', 'bookletOverlays', $debuglog);
                $this->lConf['bookletArrows']        = $this->getFlexformData('s_general', 'bookletArrows', $debuglog);
                $this->lConf['bookletArrowsHide']    = $this->getFlexformData('s_general', 'bookletArrows', $debuglog);
                $this->lConf['bookletHovers']        = $this->getFlexformData('s_general', 'bookletHovers', $debuglog);

                $this->lConf['delayDuration'] = $this->getFlexformData('s_general', 'delayDuration', in_array($this->lConf['style'], ['slider', 'slidedeck', 'easyaccordion']));
                $this->lConf['autoplayCycle'] = $this->getFlexformData('s_general', 'autoplayCycle', ($this->lConf['style'] == 'slidedeck'));

                // columns
                $this->conf['config.']['column1']     = $this->lConf['column1'];
                $this->conf['config.']['column2']     = $this->lConf['column2'];
                $this->conf['config.']['column3']     = $this->lConf['column3'];
                $this->conf['config.']['column4']     = $this->lConf['column4'];
                $this->conf['config.']['column5']     = $this->lConf['column5'];
                $this->conf['config.']['columnOrder'] = $this->lConf['columnOrder'];
                if ($this->lConf['equalize'] < 2) {
                    $this->conf['config.']['equalize'] = $this->lConf['equalize'];
                }

                // tab
                if ($this->lConf['tabCollapsible'] < 2) {
                    $this->conf['config.']['tabCollapsible'] = $this->lConf['tabCollapsible'];
                }
                if ($this->lConf['tabOpen'] >= 0) {
                    $this->conf['config.']['tabOpen'] = $this->lConf['tabOpen'];
                }
                if ($this->lConf['tabRandomContent'] < 2) {
                    $this->conf['config.']['tabRandomContent'] = $this->lConf['tabRandomContent'];
                }
                if (strlen($this->lConf['tabCookieExpires']) > 0) {
                    $this->conf['config.']['tabCookieExpires'] = $this->lConf['tabCookieExpires'];
                }
                if ($this->lConf['tabCookieRoot'] < 2) {
                    $this->conf['config.']['tabCookieRoot'] = $this->lConf['tabCookieRoot'];
                }
                if ($this->lConf['tabHideEffect']) {
                    $this->conf['config.']['tabHideEffect'] = $this->lConf['tabHideEffect'];
                }
                if ($this->lConf['tabHideTransition']) {
                    $this->conf['config.']['tabHideTransition'] = $this->lConf['tabHideTransition'];
                }
                if ($this->lConf['tabHideTransitiondir']) {
                    $this->conf['config.']['tabHideTransitiondir'] = $this->lConf['tabHideTransitiondir'];
                }
                if ($this->lConf['tabHideTransitionduration'] > 0) {
                    $this->conf['config.']['tabHideTransitionduration'] = $this->lConf['tabHideTransitionduration'];
                }
                if ($this->lConf['tabShowEffect']) {
                    $this->conf['config.']['tabShowEffect'] = $this->lConf['tabShowEffect'];
                }
                if ($this->lConf['tabShowTransition']) {
                    $this->conf['config.']['tabShowTransition'] = $this->lConf['tabShowTransition'];
                }
                if ($this->lConf['tabShowTransitiondir']) {
                    $this->conf['config.']['tabShowTransitiondir'] = $this->lConf['tabShowTransitiondir'];
                }
                if ($this->lConf['tabShowTransitionduration'] > 0) {
                    $this->conf['config.']['tabShowTransitionduration'] = $this->lConf['tabShowTransitionduration'];
                }
                if (in_array($this->lConf['tabEvent'], ['click', 'mouseover'])) {
                    $this->conf['config.']['tabEvent'] = $this->lConf['tabEvent'];
                }
                if (in_array($this->lConf['tabHeightStyle'], ['auto', 'fill', 'content'])) {
                    $this->conf['config.']['tabHeightStyle'] = $this->lConf['tabHeightStyle'];
                }

                // accordion
                if ($this->lConf['accordionCollapsible'] < 2) {
                    $this->conf['config.']['accordionCollapsible'] = $this->lConf['accordionCollapsible'];
                }
                if ($this->lConf['accordionClosed'] < 2) {
                    $this->conf['config.']['accordionClosed'] = $this->lConf['accordionClosed'];
                }
                if ($this->lConf['accordionOpen'] > 0) {
                    $this->conf['config.']['accordionOpen'] = $this->lConf['accordionOpen'];
                }
                if ($this->lConf['accordionRandomContent'] < 2) {
                    $this->conf['config.']['accordionRandomContent'] = $this->lConf['accordionRandomContent'];
                }
                if ($this->lConf['accordionEvent']) {
                    $this->conf['config.']['accordionEvent'] = $this->lConf['accordionEvent'];
                }
                if (in_array($this->lConf['accordionHeightStyle'], ['auto', 'fill', 'content'])) {
                    $this->conf['config.']['accordionHeightStyle'] = $this->lConf['accordionHeightStyle'];
                }
                if ($this->lConf['accordionAnimate'] < 2) {
                    $this->conf['config.']['accordionAnimate'] = $this->lConf['accordionAnimate'];
                }
                if ($this->lConf['accordionTransition']) {
                    $this->conf['config.']['accordionTransition'] = $this->lConf['accordionTransition'];
                }
                if ($this->lConf['accordionTransitiondir']) {
                    $this->conf['config.']['accordionTransitiondir'] = $this->lConf['accordionTransitiondir'];
                }
                if ($this->lConf['accordionTransitionduration'] > 0) {
                    $this->conf['config.']['accordionTransitionduration'] = $this->lConf['accordionTransitionduration'];
                }
                // slider
                if ($this->lConf['sliderWidth']) {
                    $this->conf['config.']['sliderWidth'] = $this->lConf['sliderWidth'];
                }
                if ($this->lConf['sliderHeight']) {
                    $this->conf['config.']['sliderHeight'] = $this->lConf['sliderHeight'];
                }
                if ($this->lConf['sliderResizeContents'] < 2) {
                    $this->conf['config.']['sliderResizeContents'] = $this->lConf['sliderResizeContents'];
                }
                if ($this->lConf['sliderTheme']) {
                    $this->conf['config.']['sliderTheme'] = $this->lConf['sliderTheme'];
                }
                if ($this->lConf['sliderMode']) {
                    $this->conf['config.']['sliderMode'] = $this->lConf['sliderMode'];
                }
                if ($this->lConf['sliderOpen'] > 0) {
                    $this->conf['config.']['sliderOpen'] = $this->lConf['sliderOpen'];
                }
                if ($this->lConf['sliderRandomContent'] < 2) {
                    $this->conf['config.']['sliderRandomContent'] = $this->lConf['sliderRandomContent'];
                }
                if ($this->lConf['sliderHashTags'] < 2) {
                    $this->conf['config.']['sliderHashTags'] = $this->lConf['sliderHashTags'];
                }
                if ($this->lConf['sliderBuildArrows'] < 2) {
                    $this->conf['config.']['sliderBuildArrows'] = $this->lConf['sliderBuildArrows'];
                }
                if ($this->lConf['sliderToggleArrows'] < 2) {
                    $this->conf['config.']['sliderToggleArrows'] = $this->lConf['sliderToggleArrows'];
                }
                if ($this->lConf['sliderNavigation'] < 2) {
                    $this->conf['config.']['sliderNavigation'] = $this->lConf['sliderNavigation'];
                }
                if ($this->lConf['sliderStartStop'] < 2) {
                    $this->conf['config.']['sliderStartStop'] = $this->lConf['sliderStartStop'];
                }
                if ($this->lConf['sliderPanelFromHeader'] < 2) {
                    $this->conf['config.']['sliderPanelFromHeader'] = $this->lConf['sliderPanelFromHeader'];
                }
                if ($this->lConf['sliderToggleControls'] < 2) {
                    $this->conf['config.']['sliderToggleControls'] = $this->lConf['sliderToggleControls'];
                }
                if ($this->lConf['sliderAutoStart'] < 2) {
                    $this->conf['config.']['sliderAutoStart'] = $this->lConf['sliderAutoStart'];
                }
                if ($this->lConf['sliderPauseOnHover'] < 2) {
                    $this->conf['config.']['sliderPauseOnHover'] = $this->lConf['sliderPauseOnHover'];
                }
                if ($this->lConf['sliderAllowRapidChange'] < 2) {
                    $this->conf['config.']['sliderAllowRapidChange'] = $this->lConf['sliderAllowRapidChange'];
                }
                if ($this->lConf['sliderResumeOnVideoEnd'] < 2) {
                    $this->conf['config.']['sliderResumeOnVideoEnd'] = $this->lConf['sliderResumeOnVideoEnd'];
                }
                if ($this->lConf['sliderStopAtEnd'] < 2) {
                    $this->conf['config.']['sliderStopAtEnd'] = $this->lConf['sliderStopAtEnd'];
                }
                if ($this->lConf['sliderPlayRtl'] < 2) {
                    $this->conf['config.']['sliderPlayRtl'] = $this->lConf['sliderPlayRtl'];
                }
                if ($this->lConf['sliderTransition']) {
                    $this->conf['config.']['sliderTransition'] = $this->lConf['sliderTransition'];
                }
                if ($this->lConf['sliderTransitiondir']) {
                    $this->conf['config.']['sliderTransitiondir'] = $this->lConf['sliderTransitiondir'];
                }
                if ($this->lConf['sliderTransitionduration'] > 0) {
                    $this->conf['config.']['sliderTransitionduration'] = $this->lConf['sliderTransitionduration'];
                }
                if ($this->lConf['sliderAutoplay'] < 2) {
                    $this->conf['config.']['sliderAutoplay'] = $this->lConf['sliderAutoplay'];
                }
                // slidedeck
                if ($this->lConf['slidedeckHeight'] > 0) {
                    $this->conf['config.']['slidedeckHeight'] = $this->lConf['slidedeckHeight'];
                }
                if ($this->lConf['slidedeckTransition']) {
                    $this->conf['config.']['slidedeckTransition'] = $this->lConf['slidedeckTransition'];
                }
                if ($this->lConf['slidedeckTransitiondir']) {
                    $this->conf['config.']['slidedeckTransitiondir'] = $this->lConf['slidedeckTransitiondir'];
                }
                if ($this->lConf['slidedeckTransitionduration'] > 0) {
                    $this->conf['config.']['slidedeckTransitionduration'] = $this->lConf['slidedeckTransitionduration'];
                }
                if ($this->lConf['slidedeckStart'] > 0) {
                    $this->conf['config.']['slidedeckStart'] = $this->lConf['slidedeckStart'];
                }
                if ($this->lConf['slidedeckActivecorner'] < 2) {
                    $this->conf['config.']['slidedeckActivecorner'] = $this->lConf['slidedeckActivecorner'];
                }
                if ($this->lConf['slidedeckIndex'] < 2) {
                    $this->conf['config.']['slidedeckIndex'] = $this->lConf['slidedeckIndex'];
                }
                if ($this->lConf['slidedeckScroll'] < 2) {
                    $this->conf['config.']['slidedeckScroll'] = $this->lConf['slidedeckScroll'];
                }
                if ($this->lConf['slidedeckKeys'] < 2) {
                    $this->conf['config.']['slidedeckKeys'] = $this->lConf['slidedeckKeys'];
                }
                if ($this->lConf['slidedeckHidespines'] < 2) {
                    $this->conf['config.']['slidedeckHidespines'] = $this->lConf['slidedeckHidespines'];
                }
                // easyAccordion
                if ($this->lConf['easyaccordionSkin']) {
                    $this->conf['config.']['easyaccordionSkin'] = $this->lConf['easyaccordionSkin'];
                }
                if ($this->lConf['easyaccordionOpen'] > 0) {
                    $this->conf['config.']['easyaccordionOpen'] = $this->lConf['easyaccordionOpen'];
                }
                if ($this->lConf['easyaccordionWidth'] > 0) {
                    $this->conf['config.']['easyaccordionWidth'] = $this->lConf['easyaccordionWidth'];
                }
                if ($this->lConf['easyaccordionSlideNum'] < 2) {
                    $this->conf['config.']['easyaccordionSlideNum'] = $this->lConf['easyaccordionSlideNum'];
                }
                // booklet
                if ($this->lConf['bookletWidth'] > 0) {
                    $this->conf['config.']['bookletWidth'] = $this->lConf['bookletWidth'];
                }
                if ($this->lConf['bookletHeight'] > 0) {
                    $this->conf['config.']['bookletHeight'] = $this->lConf['bookletHeight'];
                }
                if ($this->lConf['bookletSpeed'] > 0) {
                    $this->conf['config.']['bookletSpeed'] = $this->lConf['bookletSpeed'];
                }
                if ($this->lConf['bookletStartingPage'] > 0) {
                    $this->conf['config.']['bookletStartingPage'] = $this->lConf['bookletStartingPage'];
                }
                if ($this->lConf['bookletRTL'] < 2) {
                    $this->conf['config.']['bookletRTL'] = $this->lConf['bookletRTL'];
                }
                if ($this->lConf['bookletTransition']) {
                    $this->conf['config.']['bookletTransition']    = $this->lConf['bookletTransition'];
                }
                if ($this->lConf['bookletTransitiondir']) {
                    $this->conf['config.']['bookletTransitiondir'] = $this->lConf['bookletTransitiondir'];
                }
                if ($this->lConf['bookletPagePadding'] != '') {
                    $this->conf['config.']['bookletPagePadding'] = $this->lConf['bookletPagePadding'];
                }
                if ($this->lConf['bookletPageNumbers'] < 2) {
                    $this->conf['config.']['bookletPageNumbers'] = $this->lConf['bookletPageNumbers'];
                }
                if ($this->lConf['bookletManual'] < 2) {
                    $this->conf['config.']['bookletManual'] = $this->lConf['bookletManual'];
                }
                if ($this->lConf['bookletShadows'] < 2) {
                    $this->conf['config.']['bookletShadows'] = $this->lConf['bookletShadows'];
                }
                if ($this->lConf['bookletClosed'] < 2) {
                    $this->conf['config.']['bookletClosed'] = $this->lConf['bookletClosed'];
                }
                if ($this->lConf['bookletCovers'] < 2) {
                    $this->conf['config.']['bookletCovers'] = $this->lConf['bookletCovers'];
                }
                if ($this->lConf['bookletAutoCenter'] < 2) {
                    $this->conf['config.']['bookletAutoCenter'] = $this->lConf['bookletAutoCenter'];
                }
                if ($this->lConf['bookletHash'] < 2) {
                    $this->conf['config.']['bookletHash'] = $this->lConf['bookletHash'];
                }
                if ($this->lConf['bookletKeyboard'] < 2) {
                    $this->conf['config.']['bookletKeyboard'] = $this->lConf['bookletKeyboard'];
                }
                if ($this->lConf['bookletAuto'] < 2) {
                    $this->conf['config.']['bookletAuto'] = $this->lConf['bookletAuto'];
                }
                if ($this->lConf['bookletDelay'] < 2) {
                    $this->conf['config.']['bookletDelay'] = $this->lConf['bookletDelay'];
                }
                if ($this->lConf['bookletOverlays'] < 2) {
                    $this->conf['config.']['bookletOverlays'] = $this->lConf['bookletOverlays'];
                }
                if ($this->lConf['bookletArrows'] < 2) {
                    $this->conf['config.']['bookletArrows'] = $this->lConf['bookletArrows'];
                }
                if ($this->lConf['bookletArrowsHide'] < 2) {
                    $this->conf['config.']['bookletArrowsHide'] = $this->lConf['bookletArrowsHide'];
                }
                if ($this->lConf['bookletHovers'] < 2) {
                    $this->conf['config.']['bookletHovers'] = $this->lConf['bookletHovers'];
                }
                // autoplay
                if ($this->lConf['delayDuration'] > 0) {
                    $this->conf['config.']['delayDuration'] = $this->lConf['delayDuration'];
                }
                if ($this->lConf['autoplayCycle'] < 2) {
                    $this->conf['config.']['autoplayCycle'] = $this->lConf['autoplayCycle'];
                }

                $this->conf['config.']['style'] = $this->lConf['style'];
            }

            $this->lConf['titles']     = $this->getFlexformData('s_title', 'titles') ?? '';
            $this->lConf['attributes'] = $this->getFlexformData('s_attribute', 'attributes');

            $this->lConf['options']         = $this->getFlexformData('s_special', 'options');
            $this->lConf['optionsOverride'] = $this->getFlexformData('s_special', 'optionsOverride');

            $this->conf['config.']['view'] = 'content';
            if (!empty($this->cObj->data['tx_jfmulticontent_view'])) {
                $this->conf['config.']['view'] = $this->cObj->data['tx_jfmulticontent_view'];
            }

            // define the titles to overwrite
            if (trim($this->lConf['titles'])) {
                $this->titles = GeneralUtility::trimExplode(chr(10), $this->lConf['titles']);
            }
            // define the attributes
            if (trim($this->lConf['attributes'])) {
                $this->attributes = GeneralUtility::trimExplode(chr(10), $this->lConf['attributes']);
            }

            // options
            if ($this->lConf['optionsOverride'] || trim($this->lConf['options'])) {
                $this->conf['config.'][$this->lConf['style'] . 'Options'] = $this->lConf['options'];
                $this->conf['config.'][$this->lConf['style'] . 'OptionsOverride'] = $this->lConf['optionsOverride'];
            }

            $view = '';
            if (isset($this->conf['config.']['view'])) {
                $view = $this->conf['views.'][$this->conf['config.']['view'] . '.'] ?? '';
            }

            if (
                $this->conf['config.']['view'] == 'page'
            ) {
                // get the page ID's
                $page_ids = GeneralUtility::trimExplode(',', $this->cObj->data['tx_jfmulticontent_pages'], true);

                // get the informations for every page
                for ($a = 0; $a < count($page_ids); $a++) {

                    $tsfe->register['pid'] = $page_ids[$a];

                    if (
                        $this->confArr['useOwnUserFuncForPages']
                    ) {
                        $innerContent = '';
                        if (isset($view['content'])) {
                            // TemplaVoilaPlus will render the content with a userFunc
                            $innerContent = $this->cObj->cObjGetSingle($view['content'], $view['content.'] ?? '');
                        }
                        $this->cElements[] = $innerContent;
                        $relContent = '';
                        if (isset($view['rel'])) {
                            $relContent = $this->cObj->cObjGetSingle($view['rel'], $view['rel.'] ?? '');
                        }
                        $this->rels[] = $relContent;
                    } else {
                        $row = null;
                        if ($languageAspect->getContentId()) {
                            // SELECT * FROM `pages` WHERE `deleted`=0 AND `hidden`=0 AND `pid`=<mypid> AND `sys_language_uid`=<mylanguageid>
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
                            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
                            $statement = $queryBuilder->select('*')
                                ->from('pages')
                                ->where(
                                    $queryBuilder->expr()->eq(
                                        'pid',
                                        $queryBuilder->createNamedParameter(
                                            $page_ids[$a],
                                            Connection::PARAM_INT
                                        )
                                    )
                                )
                                ->andWhere(
                                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageAspect->getContentId(), Connection::PARAM_INT))
                                )
                                ->setMaxResults(1)
                                ->executeQuery();
                            $row = $statement->fetchAssociative();
                        }

                        if (!is_array($row)) {
                            // SELECT * FROM `pages` WHERE `deleted`=0 AND `hidden`=0 AND `uid`=<mypid>
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
                            $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
                            $statement = $queryBuilder->select('*')
                                ->from('pages')
                                ->where(
                                    $queryBuilder->expr()->eq(
                                        'uid',
                                        $queryBuilder->createNamedParameter(
                                            $page_ids[$a],
                                            Connection::PARAM_INT
                                        )
                                    ),
                                    $queryBuilder->expr()->eq(
                                        'sys_language_uid',
                                        $queryBuilder->createNamedParameter(
                                            0,
                                            Connection::PARAM_INT
                                        )
                                    )
                                )
                                ->setMaxResults(1)
                                ->executeQuery();
                            $row = $statement->fetchAssociative();
                        }

                        if (is_array($row)) {
                            foreach ($row as $key => $val) {
                                $tsfe->register['page_' . $key] = $val;
                            }
                        }
                        if (is_array($view)) {
                            $innerContent = '';
                            if (isset($view['content'])) {
                                $innerContent = $this->cObj->cObjGetSingle($view['content'], $view['content.'] ?? '');
                            }
                            $this->cElements[] = $innerContent;
                            $relContent = '';
                            if (isset($view['rel'])) {
                                 $relContent = $this->cObj->cObjGetSingle($view['rel'], $view['rel.'] ?? '');
                            }
                            $this->rels[] = $relContent;
                        }
                        $this->content_id[$a] = $page_ids[$a];
                    }

                    if (
                        !isset($this->titles[$a]) ||
                        $this->titles[$a] == ''
                    ) {
                        if (isset($view['title'])) {
                            $this->titles[$a] = $this->cObj->cObjGetSingle($view['title'], $view['title.'] ?? '');
                        } else {
                            $this->titles[$a] = '';
                        }
                    }
                }
            } elseif ($this->conf['config.']['view'] == 'content') {
                // get the content ID's
                $content_ids =
                    GeneralUtility::trimExplode(',', $this->cObj->data['tx_jfmulticontent_contents'], true);

                // get the informations for every content
                for ($a = 0; $a < count($content_ids); $a++) {
                    debug ($content_ids[$a], '$content_ids['. $a . ']');

                    // SELECT * FROM `tt_content` WHERE `deleted`=0 AND `hidden`=0 AND `uid`=<mycontentid>
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
                    $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
                    $statement = $queryBuilder->select('*')
                        ->from('tt_content')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter(
                                    $content_ids[$a],
                                    Connection::PARAM_INT
                                )
                            )
                        )
                        ->setMaxResults(1)
                        ->executeQuery();
                    $row = $statement->fetchAssociative();

                    if (is_array($row)) {
                        if ($languageAspect->getContentId()) {
                            $row = $tsfe->sys_page->getRecordOverlay('tt_content', $row, $languageAspect->getContentId(), $languageAspect->getLegacyOverlayType());
                        } elseif ($versioningWorkspaceId) {
                            $tsfe->sys_page->versionOL('tt_content', $row);
                        }
                    }

                    if (is_array($row)) {
                        $tsfe->register['uid'] = !empty($row['_LOCALIZED_UID']) ? $row['_LOCALIZED_UID'] : $row['uid'];
                        $tsfe->register['title'] = (isset($this->titles[$a]) && strlen(trim($this->titles[$a])) > 0 ? $this->titles[$a] : $row['header']);
                    }

                    if (
                        isset($view['title']) &&
                        (
                            !isset($this->titles[$a]) ||
                            $this->titles[$a] == ''
                        )
                    ) {
                        $this->titles[$a] = $this->cObj->cObjGetSingle($view['title'], $view['title.'] ?? '');
                        $tsfe->register['title'] = $this->titles[$a];
                    }

                    $innerContent = '';
                    if (isset($view['content'])) {
                        $innerContent = $this->cObj->cObjGetSingle($view['content'], $view['content.'] ?? '');
                    }
                    $this->cElements[] = $innerContent;
                    $relContent = '';
                    if (isset($view['rel'])) {
                        $relContent = $this->cObj->cObjGetSingle($view['rel'] ?? '', $view['rel.'] ?? []);
                    }
                    $this->rels[] = $relContent;
                    $this->content_id[$a] = $content_ids[$a];
                }
            } elseif ($this->conf['config.']['view'] == 'irre') {
                // get the content ID's
                $elementUID = !empty($this->cObj->data['_LOCALIZED_UID']) ? $this->cObj->data['_LOCALIZED_UID'] : $this->cObj->data['uid'];
                if ($versioningWorkspaceId) {
                    $elementUID = $this->cObj->data['_ORIG_uid'];
                }

                // SELECT * FROM `tt_content` WHERE `deleted`=0 AND `hidden`=0 AND `tx_jfmulticontent_irre_parentid`=<myrecordid>
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
                $statement = $queryBuilder->select('*')
                    ->from('tt_content')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'tx_jfmulticontent_irre_parentid',
                            $queryBuilder->createNamedParameter(
                                $elementUID,
                                Connection::PARAM_INT
                            )
                        )
                    )
                    ->orderBy('sorting', 'ASC')
                    ->executeQuery();
                $a = 0;
                while ($row = $statement->fetchAssociative()) {
                    $this->addIRREContent($a, $context, $row, $view);
                }
            }

            // HOOK for additional views
            if (
                isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['jfmulticontent']['getViews']) &&
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['jfmulticontent']['getViews'])
            ) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['jfmulticontent']['getViews'] as $_classRef) {
                    $_procObj = GeneralUtility::makeInstance($_classRef);
                    if ($this->conf['config.']['view'] == $_procObj->getIdentifier()) {
                        if (!method_exists($_procObj, 'isActive') || (method_exists($_procObj, 'isActive') && $_procObj->isActive())) {
                            // If the methode 'isActive' not exists, this will be true...
                            $_procObj->main($this->content, $this->conf, $this);
                            $this->titles = $_procObj->getTitles();
                            $innerContent = $_procObj->getElements();
                            $this->cElements[] = $innerContent;
                            $this->content_id = $_procObj->getIds();
                            if (method_exists($_procObj, 'getRels')) {
                                $this->rels = $_procObj->getRels();
                            }
                        }
                    }
                }
            }
            // define the key of the element
            $this->setContentKey('jfmulticontent_c' . $this->cObj->data['uid']);
        } else {
            // TS config will be used
            // define the key of the element
            if ($this->conf['config.']['contentKey']) {
                $this->setContentKey($this->conf['config.']['contentKey']);
            } else {
                $this->setContentKey('jfmulticontent_ts1');
            }
            // Render the contents
            if (count($this->conf['contents.']) > 0) {
                foreach ($this->conf['contents.'] as $key => $contents) {
                    $title = trim($this->cObj->cObjGetSingle($contents['title'], $contents['title.']));
                    $innerContent = trim($this->cObj->cObjGetSingle($contents['content'], $contents['content.']));
                    if ($innerContent) {
                        $this->titles[] = $title;
                        $this->cElements[] = $innerContent;
                        $this->rels[] = $this->cObj->cObjGetSingle($contents['rel'], $contents['rel.']);
                        $this->content_id[] = $this->cObj->stdWrap($contents['id'], $contents['id.']);
                    }
                }
            }
        }
        $this->contentCount = count($this->cElements);
        // return false, if there is no element
        if ($this->contentCount == 0) {
            return false;
        }

        // The template
        $incFile = (empty($this->conf['templateFile']) ? '' : GeneralUtility::getFileAbsFileName($this->conf['templateFile']));

        if (file_exists($incFile)) {
            $this->templateFile = file_get_contents($incFile);
        }
        if (!$this->templateFile) {
            $fileName = 'EXT:' . $this->extKey . '/Resources/Private/Templates/tx_jfmulticontent_pi1.tmpl';
            $incFile = GeneralUtility::getFileAbsFileName($fileName);
            $this->templateFile = file_get_contents($incFile);
        }
        // The template for JS
        $incFile = (empty($this->conf['templateFileJS']) ? '' : $incFile = GeneralUtility::getFileAbsFileName($this->conf['templateFileJS']));
        if (file_exists($incFile)) {
            $this->templateFileJS = file_get_contents($incFile);
        }
        if (!$this->templateFileJS) {
            $fileName = 'EXT:' . $this->extKey . '/Resources/Private/Templates/tx_jfmulticontent_pi1.js';
            $incFile = GeneralUtility::getFileAbsFileName($fileName);
            $this->templateFileJS = file_get_contents($incFile);
        }

        // define the jQuery mode and function
        if (!empty($this->conf['jQueryNoConflict'])) {
            $jQueryNoConflict = 'jQuery.noConflict();';
        } else {
            $jQueryNoConflict = '';
        }

        // style
        switch ($this->conf['config.']['style']) {
            case '2column' : {
                $this->templatePart = 'TEMPLATE_COLUMNS';
                $this->contentCount = 2;
                $this->classes = [
                    $this->conf['config.']['column1'],
                    $this->conf['config.']['column2'],
                ];
                $this->contentClass = GeneralUtility::trimExplode('|*|', $this->conf['2columnClasses'] ?? '');
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['columnWrap.']['wrap'] ?? '');
                break;
            }
            case '3column' : {
                $this->templatePart = 'TEMPLATE_COLUMNS';
                $this->contentCount = 3;
                $this->classes = [
                    $this->conf['config.']['column1'],
                    $this->conf['config.']['column2'],
                    $this->conf['config.']['column3'],
                ];
                $this->contentClass = GeneralUtility::trimExplode('|*|', $this->conf['3columnClasses'] ?? '');
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['columnWrap.']['wrap'] ?? '');
                break;
            }
            case '4column' : {
                $this->templatePart = 'TEMPLATE_COLUMNS';
                $this->contentCount = 4;
                $this->classes = [
                    $this->conf['config.']['column1'],
                    $this->conf['config.']['column2'],
                    $this->conf['config.']['column3'],
                    $this->conf['config.']['column4'],
                ];
                $this->contentClass = GeneralUtility::trimExplode('|*|', $this->conf['4columnClasses'] ?? '');
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['columnWrap.']['wrap'] ?? '');
                break;
            }
            case '5column' : {
                $this->templatePart = 'TEMPLATE_COLUMNS';
                $this->contentCount = 5;
                $this->classes = [
                    $this->conf['config.']['column1'],
                    $this->conf['config.']['column2'],
                    $this->conf['config.']['column3'],
                    $this->conf['config.']['column4'],
                    $this->conf['config.']['column5'],
                ];
                $this->contentClass = GeneralUtility::trimExplode('|*|', $this->conf['5columnClasses'] ?? '');
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['columnWrap.']['wrap'] ?? '');
                break;
            }
            case 'tab' : {
                // jQuery Tabs
                $this->templatePart = 'TEMPLATE_TAB';
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['tabWrap.']['wrap']);
                // the id attribute is not permitted in tabs-style
                if (count($this->attributes) > 0) {
                    foreach ($this->attributes as $key => $attribute) {
                        if (preg_match('/id=[\"|\'](.*?)[\"|\']/i', $attribute, $preg)) {
                            $this->attributes[$key] = trim(str_replace($preg[0], '', $attribute));
                        }
                    }
                }
                $this->pagerenderer->addJS($jQueryNoConflict);
                $options = [];
                if ($this->conf['config.']['tabCollapsible']) {
                    $options['collapsible'] = 'collapsible:true';
                    if (!$this->conf['config.']['tabOpen']) {
                        $options['active'] = 'active:false';
                    }
                }
                if ($this->conf['config.']['tabRandomContent']) {
                    $options['active'] = 'active:Math.floor(Math.random()*' . $this->contentCount . ')';
                } elseif (is_numeric($this->conf['config.']['tabOpen'])) {
                    $options['active'] = 'active:' . ($this->conf['config.']['tabOpen'] - 1);
                }
                if (in_array($this->conf['config.']['tabEvent'], ['click', 'mouseover'])) {
                    $options['event'] = 'event:\'' . $this->conf['config.']['tabEvent'] . '\'';
                }
                if (in_array($this->conf['config.']['tabHeightStyle'], ['auto', 'fill', 'content'])) {
                    $options['heightStyle'] = 'heightStyle:\'' . $this->conf['config.']['tabHeightStyle'] . '\'';
                }

                // Add Cookies script, if cookie is active
                if (
                    $this->conf['config.']['tabCookieExpires'] > 0 &&
                    $this->conf['config.']['tabOpen'] != -1
                ) {
                    if ($jQueryAvailable) {
                        // nothing
                    } else {
                        $this->pagerenderer->addJsFile($this->conf['jQueryCookies']);
                    }
                    unset($options['active']);
                    $cookie_path = GeneralUtility::getIndpEnv('REQUEST_URI');
                    if ($this->lConf['tabCookieRoot'] || preg_match('/^\/index.php/i', $cookie_path)) {
                        $cookie_path = '/';
                    }
                    $options['activate'] = "activate:function(e,ui) { jQuery.cookie('{$this->getContentKey()}', ui.newTab.index(), { expires: " . $this->conf['config.']['tabCookieExpires'] . ", path:'$cookie_path' }); }";
                    $options['active'] = "active:jQuery.cookie('{$this->getContentKey()}')";
                }

                if ($this->conf['config.']['tabHideEffect'] == 'none') {
                    $options['hide'] = 'hide:false';
                } elseif ($this->conf['config.']['tabHideEffect']) {
                    $fx = [];
                    $fx[] = "effect:'{$this->conf['config.']['tabHideEffect']}'";
                    if (is_numeric($this->conf['config.']['tabHideTransitionduration'])) {
                        $fx[] = "duration:'{$this->conf['config.']['tabHideTransitionduration']}'";
                    }
                    if ($this->conf['config.']['tabHideTransition']) {
                        $fx[] = "easing:'" . (in_array($this->conf['config.']['tabHideTransition'], ["swing", "linear"]) ? "" : "ease{$this->conf['config.']['tabHideTransitiondir']}") . "{$this->conf['config.']['tabHideTransition']}'";
                    }
                    $options['hide'] = "hide:{" . implode(', ', $fx) . "}";
                }

                if ($this->conf['config.']['tabShowEffect'] == 'none') {
                    $options['show'] = "show:false";
                } elseif ($this->conf['config.']['tabShowEffect']) {
                    $fx = [];
                    $fx[] = "effect:'{$this->conf['config.']['tabShowEffect']}'";
                    if (is_numeric($this->conf['config.']['tabShowTransitionduration'])) {
                        $fx[] = "duration:'{$this->conf['config.']['tabShowTransitionduration']}'";
                    }
                    if ($this->conf['config.']['tabShowTransition']) {
                        $fx[] = "easing:'" . (in_array($this->conf['config.']['tabShowTransition'], ["swing", "linear"]) ? "" : "ease{$this->conf['config.']['tabShowTransitiondir']}") . "{$this->conf['config.']['tabShowTransition']}'";
                    }
                    $options['show'] = "show:{" . implode(', ', $fx) . "}";
                }

                if (isset($this->conf['config.']['tabOptions'])) {
                    // overwrite all options if set
                    if (!empty($this->conf['config.']['tabOptionsOverride'])) {
                        $options = [$this->conf['config.']['tabOptions']];
                    } else {
                        $options['options'] = $this->conf['config.']['tabOptions'];
                    }
                }

                // get the Template of the Javascript
                $markerArray = [];
                // get the template
                if (!$templateCode = trim($parser->getSubpart($this->templateFileJS, '###TEMPLATE_TAB_JS###'))) {
                    $templateCode = $this->outputError('Template TEMPLATE_TAB_JS is missing', true);
                }

                // open tab by hash
                if ($this->confArr['tabSelectByHash']) {
                    $tabSelector = trim($parser->getSubpart($templateCode, '###TAB_SELECT_BY_HASH###'));
                } else {
                    $tabSelector = null;
                }
                $templateCode = trim($parser->substituteSubpart($templateCode, '###TAB_SELECT_BY_HASH###', $tabSelector, 0));

                // app the open-link-template
                if ($this->confArr['openExternalLink']) {
                    $openExtLink = trim($parser->getSubpart($templateCode, '###OPEN_EXTERNAL_LINK###'));
                } else {
                    $openExtLink = null;
                }
                $templateCode = trim($parser->substituteSubpart($templateCode, '###OPEN_EXTERNAL_LINK###', $openExtLink, 0));

                // Replace default values
                $markerArray['KEY'] = $this->getContentKey();
                $markerArray['PREG_QUOTE_KEY'] = preg_quote($this->getContentKey(), '/');
                $markerArray['OPTIONS'] = implode(', ', $options);
                $templateCode = $parser->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

                // Add all CSS and JS files
                if ($jQueryAvailable) {
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                    $this->pagerenderer->addJsFile($this->conf['jQueryUI']);
                } else {
                    $this->pagerenderer->addJsFile($this->conf['jQueryLibrary'], true);
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                    $this->pagerenderer->addJsFile($this->conf['jQueryUI']);
                }
                $this->pagerenderer->addCssFile($this->conf['jQueryUIstyle']);
                $this->pagerenderer->addJS($templateCode);
                break;
            }
            case 'accordion' : {
                // jQuery Accordion
                $this->templatePart = 'TEMPLATE_ACCORDION';
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['accordionWrap.']['wrap']);
                $this->pagerenderer->addJS($jQueryNoConflict);
                $options = [];
                if ($this->conf['config.']['accordionCollapsible']) {
                    $options['collapsible'] = 'collapsible:true';
                }

                if (!empty($this->conf['config.']['accordionClosed'])) {
                    $options['active'] = 'active:false';
                    $options['collapsible'] = 'collapsible:true';
                } elseif (!empty($this->conf['config.']['accordionRandomContent'])) {
                    $options['active'] = 'active:Math.floor(Math.random()*' . $this->contentCount . ')';
                } elseif ($this->conf['config.']['accordionOpen'] > 0) {
                    $options['active'] = 'active:' . ($this->conf['config.']['accordionOpen'] - 1);
                }

                if (in_array($this->conf['config.']['accordionEvent'], ['click', 'mouseover'])) {
                    $options['event'] = "event:'{$this->conf['config.']['accordionEvent']}'";
                }

                if (in_array($this->conf['config.']['accordionHeightStyle'], ['auto', 'fill', 'content'])) {
                    $options['heightStyle'] = "heightStyle:'{$this->conf['config.']['accordionHeightStyle']}'";
                }
                // get the Template of the Javascript
                $markerArray = [];
                $markerArray['KEY']            = $this->getContentKey();
                $markerArray['CONTENT_COUNT']  = $this->contentCount;
                $markerArray['EASING']         = (in_array($this->conf['config.']['accordionTransition'], ['swing', 'linear']) ? '' : 'ease' . $this->conf['config.']['accordionTransitiondir'] . $this->conf['config.']['accordionTransition']);
                $markerArray['TRANS_DURATION'] = (is_numeric($this->conf['config.']['accordionTransitionduration']) ? $this->conf['config.']['accordionTransitionduration'] : 1000);

                // get the template for the Javascript
                if (!$templateCode = trim($parser->getSubpart($this->templateFileJS, '###TEMPLATE_ACCORDION_JS###'))) {
                    $templateCode = $this->outputError('Template TEMPLATE_ACCORDION_JS is missing', true);
                }
                $easingAnimation = null;
                if (empty($this->conf['config.']['accordionAnimate'])) {
                    $options['animate'] = 'animate:false';
                } else {
                    $fx = [];
                    if (is_numeric($this->conf['config.']['accordionTransitionduration'])) {
                        $fx[] = "duration:'{$this->conf['config.']['accordionTransitionduration']}'";
                    }
                    if ($this->conf['config.']['accordionTransition']) {
                        $fx[] = 'easing:\'' . (in_array($this->conf['config.']['accordionTransition'], ['swing', 'linear']) ? '' : 'ease' . $this->conf['config.']['accordionTransitiondir']) . $this->conf['config.']['accordionTransition'] . '\'';
                    }
                    $options['animate'] = 'animate:{' . implode(', ', $fx) . '}';
                }

                // app the open-link-template
                if (!empty($this->confArr['openExternalLink'])) {
                    $openExtLink = trim($parser->getSubpart($templateCode, '###OPEN_EXTERNAL_LINK###'));
                } else {
                    $openExtLink = null;
                }
                $templateCode = trim($parser->substituteSubpart($templateCode, '###OPEN_EXTERNAL_LINK###', $openExtLink, 0));

                // open tab by hash
                if (!empty($this->confArr['tabSelectByHash'])) {
                    $tabSelector = trim($parser->getSubpart($templateCode, '###TAB_SELECT_BY_HASH###'));
                } else {
                    $tabSelector = null;
                }
                $templateCode = trim($parser->substituteSubpart($templateCode, '###TAB_SELECT_BY_HASH###', $tabSelector, 0));

                // overwrite all options if set
                if (!empty($this->conf['config.']['accordionOptionsOverride'])) {
                    $options = [$this->conf['config.']['accordionOptions']];
                } else {
                    if (!empty($this->conf['config.']['accordionOptions'])) {
                        $options['options'] = $this->conf['config.']['accordionOptions'];
                    }
                }

                // Replace default values
                $markerArray['OPTIONS'] = implode(', ', $options);
                // Replace all markers
                $templateCode = $parser->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

                // Add all CSS and JS files

                if ($jQueryAvailable) {
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                    $this->pagerenderer->addJsFile($this->conf['jQueryUI']);
                } else {
                    $this->pagerenderer->addJsFile($this->conf['jQueryLibrary'], true);
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                    $this->pagerenderer->addJsFile($this->conf['jQueryUI']);
                }
                $this->pagerenderer->addCssFile($this->conf['jQueryUIstyle']);
                $this->pagerenderer->addJS(trim($templateCode));
                break;
            }
            case 'slider' : {
                // anythingslider
                $this->templatePart = 'TEMPLATE_SLIDER';
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['sliderWrap.']['wrap']);
                $this->pagerenderer->addJS($jQueryNoConflict);
                //
                if ($this->conf['config.']['sliderTransition']) {
                    $options[] = "easing: '".(in_array($this->conf['config.']['sliderTransition'], ['swing', 'linear']) ? '' : "ease{$this->conf['config.']['sliderTransitiondir']}")."{$this->conf['config.']['sliderTransition']}'";
                }
                if ($this->conf['config.']['sliderTransitionduration'] > 0) {
                    $options[] = "animationTime: {$this->conf['config.']['sliderTransitionduration']}";
                }
                if ($this->conf['config.']['sliderAutoplay']) {
                    $options[] = 'autoPlay: true';
                } else {
                    $options[] = 'autoPlay: false';
                }
                if ($this->conf['config.']['delayDuration'] > 0) {
                    $options[] = "delay: {$this->conf['config.']['delayDuration']}";
                    $options[] = 'startStopped: ' . ($this->conf['config.']['sliderAutoStart'] ? 'false' : 'true');
                    $options[] = 'stopAtEnd: ' . ($this->conf['config.']['sliderStopAtEnd'] ? 'true' : 'false');
                } else {
                    // Toggle only if not autoplay
                    $options[] = 'toggleArrows: ' . ($this->conf['config.']['sliderToggleArrows'] ? 'true' : 'false');
                    $options[] = 'toggleControls: ' . ($this->conf['config.']['sliderToggleControls'] ? 'true' : 'false');
                }
                $sliderWidth = trim($this->conf['config.']['sliderWidth']);
                $sliderHeight = trim($this->conf['config.']['sliderHeight']);
                if ($sliderWidth || $sliderHeight) {
                    if (is_numeric($sliderWidth)) {
                        $sliderWidth .= 'px';
                    }
                    if (is_numeric($sliderHeight)) {
                        $sliderHeight .= 'px';
                    }
                    $this->pagerenderer->addCSS("#{$this->getContentKey()} {\n" .
($sliderWidth ? "	width: {$sliderWidth};\n" : "") .
($sliderHeight ? "	height: {$sliderHeight};\n" : "") .
"}");
                }
                if ($this->conf['config.']['sliderResizeContents']) {
                    $options[] = 'resizeContents: true';
                }
                $this->pagerenderer->addCssFile($this->conf['sliderCSS']);
                $this->pagerenderer->addCssFileInc($this->conf['sliderCSSie7'], 'lte IE 7');

                if ($this->conf['config.']['sliderTheme']) {
                    $options[] = 'theme: \'' . static::slashJS($this->conf['config.']['sliderTheme']) . '\'';
                    if (substr($this->confArr['anythingSliderThemeFolder'], 0, 4) === 'EXT:') {
                        [$extKey, $local] = explode('/', substr($this->confArr['anythingSliderThemeFolder'], 4), 2);
                        $anythingSliderThemeFolder =
                            PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath($extKey)) . $local;
                    } else {
                        $anythingSliderThemeFolder = $this->confArr['anythingSliderThemeFolder'];
                    }
                    $this->pagerenderer->addCssFile(static::slashJS($anythingSliderThemeFolder) . $this->conf['config.']['sliderTheme'] . '/style.css');
                }
                if ($this->conf['config.']['sliderMode']) {
                    $options[] = "mode: '" . $this->conf['config.']['sliderMode'] . "'";
                }
                $options[] = 'buildArrows: ' . ($this->conf['config.']['sliderBuildArrows'] ? 'true' : 'false');
                $options[] = 'allowRapidChange: ' . ($this->conf['config.']['sliderAllowRapidChange'] ? 'true' : 'false');
                $options[] = 'resumeOnVideoEnd: ' . ($this->conf['config.']['sliderResumeOnVideoEnd'] ? 'true' : 'false');
                $options[] = 'playRtl: ' . ($this->conf['config.']['sliderPlayRtl'] ? 'true' : 'false');
                $options[] = 'hashTags: ' . ($this->conf['config.']['sliderHashTags'] ? 'true' : 'false');
                $options[] = 'pauseOnHover: ' . ($this->conf['config.']['sliderPauseOnHover'] ? 'true' : 'false');
                $options[] = 'buildNavigation: ' . ($this->conf['config.']['sliderNavigation'] ? 'true' : 'false');
                $options[] = 'buildStartStop: ' . ($this->conf['config.']['sliderStartStop'] ? 'true' : 'false');

                $options[] = 'startText: \'' . static::slashJS($this->pi_getLL('slider_start')) . '\'';
                $options[] = 'stopText: \'' . static::slashJS($this->pi_getLL('slider_stop')) . '\'';
                if ($this->pi_getLL('slider_forward')) {
                    $options[] = 'forwardText: \'' . static::slashJS($this->pi_getLL('slider_forward')) . '\'';
                }
                if ($this->pi_getLL('slider_back')) {
                    $options[] = 'backText: \'' . static::slashJS($this->pi_getLL('slider_back')) . '\'';
                }

                // define the paneltext
                if ($this->conf['config.']['sliderPanelFromHeader']) {
                    $tab = [];
                    for ($a = 0; $a < $this->contentCount; $a++) {
                        $tab[] = 'if(i==' . ($a + 1) . ') return ' . GeneralUtility::quoteJSvalue($this->titles[$a]) . ';';
                    }
                    $options[] = 'navigationFormatter: function(i,p){' . PHP_EOL . implode(PHP_EOL . '			', $tab) . PHP_EOL . '		}';
                } elseif (trim($this->pi_getLL('slider_panel'))) {
                    $options[] = 'navigationFormatter: function(i,p){ var str = \'' . (static::slashJS($this->pi_getLL('slider_panel'))) . '\'; return str.replace(\'%i%\',i); }';
                }
                if ($this->conf['config.']['sliderRandomContent']) {
                    $options[] = "startPanel: Math.floor(Math.random()*" . ($this->contentCount + 1) . ")";
                } elseif ($this->conf['config.']['sliderOpen'] > 1) {
                    $options[] = "startPanel: " . ($this->conf['config.']['sliderOpen'] < $this->contentCount ? $this->conf['config.']['sliderOpen'] : $this->contentCount);
                }

                // overwrite all options if set
                if ($this->conf['config.']['sliderOptionsOverride']) {
                    $options = [$this->conf['config.']['sliderOptions']];
                } else {
                    if ($this->conf['config.']['sliderOptions']) {
                        $options[] = $this->conf['config.']['sliderOptions'];
                    }
                }

                // get the Template of the Javascript
                $markerArray = [];
                // get the template
                if (!$templateCode = trim($parser->getSubpart($this->templateFileJS, '###TEMPLATE_SLIDER_JS###'))) {
                    $templateCode = $this->outputError('Template TEMPLATE_SLIDER_JS is missing', true);
                }

                // Replace default values
                $markerArray['KEY'] = $this->getContentKey();
                $markerArray['OPTIONS'] = implode(', ', $options);
                $templateCode = $parser->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

                // Add all CSS and JS files

                if ($jQueryAvailable) {
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                } else {
                    $this->pagerenderer->addJsFile($this->conf['jQueryLibrary'], true);
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                }
                $this->pagerenderer->addJsFile($this->conf['sliderJS']);
                if ($this->conf['config.']['sliderResumeOnVideoEnd']) {
                    $this->pagerenderer->addJsFile($this->conf['sliderJSvideo']);
                }
                $this->pagerenderer->addJS($templateCode);
                break;
            }
            case 'slidedeck' : {
                // SlideDeck
                $this->templatePart = 'TEMPLATE_SLIDEDECK';
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['slidedeckWrap.']['wrap']);
                $this->pagerenderer->addJS($jQueryNoConflict);
                $options = [];
                if ($this->conf['config.']['slidedeckTransitionduration']) {
                    $options['speed'] = "speed: {$this->conf['config.']['slidedeckTransitionduration']}";
                }
                if ($this->conf['config.']['slidedeckTransition']) {
                    $options['transition'] = 'transition: \'' . (in_array($this->conf['config.']['slidedeckTransition'], ['swing', 'linear']) ? '' : 'ease' . $this->conf['config.']['slidedeckTransitiondir']) . $this->conf['config.']['slidedeckTransition'] . '\'';
                }
                if ($this->conf['config.']['slidedeckStart']) {
                    $options['start'] = "start: {$this->conf['config.']['slidedeckStart']}";
                }
                $options['activeCorner'] = "activeCorner: ".($this->conf['config.']['slidedeckActivecorner'] ? 'true' : 'false');
                $options['index']        = "index: ".($this->conf['config.']['slidedeckIndex'] ? 'true' : 'false');
                $options['scroll']       = "scroll: ".($this->conf['config.']['slidedeckScroll'] ? 'true' : 'false');
                $options['keys']         = "keys: ".($this->conf['config.']['slidedeckKeys'] ? 'true' : 'false');
                $options['hideSpines']   = "hideSpines: ".($this->conf['config.']['slidedeckHidespines'] ? 'true' : 'false');
                if ($this->conf['config.']['delayDuration'] > 0) {
                    $options['autoPlay']         = "autoPlay: true";
                    $options['autoPlayInterval'] = "autoPlayInterval: {$this->conf['config.']['delayDuration']}";
                    $options['cycle']            = "cycle: ".($this->conf['config.']['autoplayCycle'] ? 'true' : 'false');
                }

                // overwrite all options if set
                if ($this->conf['config.']['slidedeckOptionsOverride']) {
                    $options = [$this->conf['config.']['slidedeckOptions']];
                } else {
                    if ($this->conf['config.']['slidedeckOptions']) {
                        $options['options'] = $this->conf['config.']['slidedeckOptions'];
                    }
                }

                // get the template for the Javascript
                if (!$templateCode = trim($parser->getSubpart($this->templateFileJS, '###TEMPLATE_SLIDEDECK_JS###'))) {
                    $templateCode = $this->outputError('Template TEMPLATE_SLIDEDECK_JS is missing', true);
                }
                // Replace default values
                $markerArray = [];
                $markerArray['KEY']     = $this->getContentKey();
                $markerArray['HEIGHT']  = ($this->conf['config.']['slidedeckHeight'] > 0 ? $this->conf['config.']['slidedeckHeight'] : 300);
                $markerArray['OPTIONS'] = implode(', ', $options);
                // Replace all markers
                $templateCode = $parser->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

                // Add all CSS and JS files

                if ($jQueryAvailable) {
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                } else {
                    $this->pagerenderer->addJsFile($this->conf['jQueryLibrary'], true);
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                }
                $this->pagerenderer->addJsFile($this->conf['slidedeckJS']);
                $this->pagerenderer->addCssFile($this->conf['slidedeckCSS']);
                if ($this->conf['config.']['slidedeckScroll']) {
                    $this->pagerenderer->addJsFile($this->conf['jQueryMouseWheel']);
                }
                $this->pagerenderer->addJS(trim($templateCode));
                break;
            }
            case 'easyaccordion' : {
                // easyaccordion
                $this->templatePart = 'TEMPLATE_EASYACCORDION';
                $this->additionalMarker['SKIN'] = $this->conf['config.']['easyaccordionSkin'];
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['easyaccordionWrap.']['wrap']);
                $this->pagerenderer->addJS($jQueryNoConflict);
                $options = [];
                if ($this->conf['config.']['delayDuration'] > 0) {
                    $options['autoStart']     = 'autoStart: true';
                    $options['slideInterval'] = "slideInterval: {$this->conf['config.']['delayDuration']}";
                }
                $options['slideNum'] = 'slideNum: ' . ($this->conf['config.']['easyaccordionSlideNum'] ? 'true' : 'false');

                // overwrite all options if set
                if ($this->conf['config.']['optionsOverride']) {
                    $options = [$this->conf['config.']['slideOptions']];
                } else {
                    if ($this->conf['config.']['slideOptions']) {
                        $options['options'] = $this->conf['config.']['slideOptions'];
                    }
                }

                // get the template for the Javascript
                if (!$templateCode = trim($parser->getSubpart($this->templateFileJS, '###TEMPLATE_EASYACCORDION_JS###'))) {
                    $templateCode = $this->outputError('Template TEMPLATE_EASYACCORDION_JS is missing', true);
                }
                // Replace default values
                $markerArray = [];
                $markerArray['KEY']     = $this->getContentKey();
                $markerArray['WIDTH']   = ($this->conf['config.']['easyaccordionWidth'] > 0 ? $this->conf['config.']['easyaccordionWidth'] : 600);
                $markerArray['OPTIONS'] = implode(', ', $options);
                // Replace all markers
                $templateCode = $parser->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

                // Add all CSS and JS files

                if ($jQueryAvailable) {
                    $this->pagerenderer->addJsFile($this->conf['jQueryLibrary'], true);
                }
                $this->pagerenderer->addJsFile($this->conf['easyaccordionJS']);
                $this->pagerenderer->addCssFile($this->conf['easyaccordionCSS']);
                $this->pagerenderer->addCssFile($this->confArr['easyAccordionSkinFolder'] . $this->conf['config.']['easyaccordionSkin'] . '/style.css');
                $this->pagerenderer->addJS(trim($templateCode));
                break;
            }
            case 'booklet' : {
                // easyaccordion
                $this->templatePart = 'TEMPLATE_BOOKLET';
                $this->contentWrap = GeneralUtility::trimExplode('|*|', $this->conf['bookletWrap.']['wrap']);
                $this->pagerenderer->addJS($jQueryNoConflict);
                $options = [];
                if (is_numeric($this->conf['config.']['bookletWidth'])) {
                    $options['width'] = 'width: ' . $this->conf['config.']['bookletWidth'];
                }
                if (is_numeric($this->conf['config.']['bookletHeight'])) {
                    $options['height'] = 'height: ' . $this->conf['config.']['bookletHeight'];
                }
                if (is_numeric($this->conf['config.']['bookletSpeed'])) {
                    $options['speed'] = 'speed: ' . $this->conf['config.']['bookletSpeed'];
                }
                if (is_numeric($this->conf['config.']['bookletStartingPage'])) {
                    $options['startingPage'] = 'startingPage: ' . $this->conf['config.']['bookletStartingPage'];
                }
                if ($this->conf['config.']['bookletRTL']) {
                    $options['direction'] = 'direction: \'RTL\'';
                }
                if ($this->conf['config.']['bookletTransition']) {
                    $options['transition'] = "easing: '" . (in_array($this->conf['config.']['bookletTransition'], ["swing", "linear"]) ? "" : "ease{$this->conf['config.']['bookletTransitiondir']}") . "{$this->conf['config.']['bookletTransition']}'";
                }
                if (is_numeric($this->conf['config.']['bookletPagePadding'])) {
                    $options['pagePadding'] = 'pagePadding: ' . $this->conf['config.']['bookletPagePadding'];
                }
                $options['pageNumbers'] = 'pageNumbers: ' . ($this->conf['config.']['bookletPageNumbers'] ? 'true' : 'false');
                $options['manual']      = 'manual: ' . ($this->conf['config.']['bookletManual'] ? 'true' : 'false');
                $options['shadows']     = "shadows: " . ($this->conf['config.']['bookletShadows'] ? 'true' : 'false');
                $options['closed']      = "closed: " . ($this->conf['config.']['bookletClosed'] ? 'true' : 'false');
                $options['covers']      = "covers: " . ($this->conf['config.']['bookletCovers'] ? 'true' : 'false');
                $options['autoCenter']  = "autoCenter: " . ($this->conf['config.']['bookletAutoCenter'] ? 'true' : 'false');
                $options['hash']        = "hash: " . ($this->conf['config.']['bookletHash'] ? 'true' : 'false');
                $options['keyboard']    = "keyboard: " . ($this->conf['config.']['bookletKeyboard'] ? 'true' : 'false');
                $options['overlays']    = "overlays: " . ($this->conf['config.']['bookletOverlays'] ? 'true' : 'false');
                $options['arrows']      = "arrows: " . ($this->conf['config.']['bookletArrows'] ? 'true' : 'false');
                $options['arrowsHide']  = "arrowsHide: " . ($this->conf['config.']['bookletArrowsHide'] ? 'true' : 'false');
                $options['hovers']      = "hovers: " . ($this->conf['config.']['bookletHovers'] ? 'true' : 'false');

                // overwrite all options if set
                if ($this->conf['config.']['bookletOptionsOverride']) {
                    $options = [$this->conf['config.']['bookletOptions']];
                } else {
                    if ($this->conf['config.']['bookletOptions']) {
                        $options['options'] = $this->conf['config.']['bookletOptions'];
                    }
                }

                // get the template for the Javascript
                if (!$templateCode = trim($parser->getSubpart($this->templateFileJS, '###TEMPLATE_BOOKLET_JS###'))) {
                    $templateCode = $this->outputError('Template TEMPLATE_BOOKLET_JS is missing', true);
                }

                // Replace default values
                $markerArray = [];
                $markerArray['KEY']     = $this->getContentKey();
                $markerArray['OPTIONS'] = implode(",\n		", $options);

                // Replace all markers
                $templateCode = $parser->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

                // Add all CSS and JS files

                if ($jQueryAvailable) {
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                } else {
                    $this->pagerenderer->addJsFile($this->conf['jQueryLibrary'], true);
                    $this->pagerenderer->addJsFile($this->conf['jQueryEasing']);
                }
                $this->pagerenderer->addJsFile($this->conf['bookletJS']);
                $this->pagerenderer->addCssFile($this->conf['bookletCSS']);
                $this->pagerenderer->addJS(trim($templateCode));
                break;
            }
            default: {
                return $this->outputError('NO VALID TEMPLATE SELECTED', false);
            }
        }

        // add the CSS file
        if (isset($this->conf['cssFile'])) {
            $this->pagerenderer->addCssFile($this->conf['cssFile']);
        }

        // Add the ressources
        if (empty($this->conf['disableJs'])) {
            $this->pagerenderer->addResources();
        }

        // Render the Template
        $out = $this->renderTemplate();
        return $this->pi_wrapInBaseClass($out);
    }

    /**
     * add a IRRE content record
     * @param integer $a index
     * @param string $context The context in which the method is called (e.g. typoLink).
     * @param array $row record
     * @return void
     */
    public function addIRREContent(&$a, $context, $row, $view): void
    {
        $tsfe = $this->getTypoScriptFrontendController();
        /** @var LanguageAspect $languageAspect */
        $languageAspect = $context->getAspect('language');
        $versioningWorkspaceId = $context->getPropertyFromAspect('workspace', 'id');

        if ($languageAspect->getContentId()) {
            $row = $tsfe->sys_page->getRecordOverlay('tt_content', $row, $languageAspect->getContentId(), $languageAspect->getLegacyOverlayType());
        } elseif ($versioningWorkspaceId) {
            $tsfe->sys_page->versionOL('tt_content', $row);
        }
        $uid = $row['_LOCALIZED_UID'] ?: $row['uid'];
        if ($row['t3ver_oid']) {
            $uid = $row['t3ver_oid'];
        }
        $tsfe->register['uid'] = $uid;
        $tsfe->register['title'] = (strlen(trim($this->titles[$a])) > 0 ? $this->titles[$a] : $row['header']);
        if (
                $this->titles[$a] == '' ||
                !isset($this->titles[$a])
        ) {
            if (isset($view['title'])) {
                $this->titles[$a] = $this->cObj->cObjGetSingle($view['title'], $view['title.'] ?? '');
            } else {
                $this->titles[$a] = '';
            }
            $tsfe->register['title'] = $this->titles[$a];
        }
        $innerContent = '';
        if (isset($view['content'])) {
            $innerContent = $this->cObj->cObjGetSingle($view['content'], $view['content.'] ?? '');
        }
        $this->cElements[] = $innerContent;
        $relContent = '';
        if (isset($view['rel'])) {
            $relContent = $this->cObj->cObjGetSingle($view['rel'], $view['rel.'] ?? '');
        }
        $this->rels[] = $relContent;
        $this->content_id[$a] = $row['uid'];
        $a++;
    }

    /**
     * Set the contentKey
     * @param string $contentKey
     */
    public function setContentKey($contentKey = null): void
    {
        $this->contentKey = ($contentKey == null ? $this->extKey : $contentKey);
    }

    /**
     * Get the contentKey
     * @return string
     */
    public function getContentKey()
    {
        return $this->contentKey;
    }

    /**
     * Render the template with the defined contents
     *
     * @return string
     */
    public function renderTemplate()
    {
        $tsfe = $this->getTypoScriptFrontendController();
        $parser = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        // set the register:key for TS manipulation
        $tsfe->register['key'] = $this->getContentKey();

        $markerArray = $this->additionalMarker;

        // Define string with all classes
        $markerArray['COLUMN_CLASSES'] = implode('', $this->classes);
        $tsfe->register['COLUMN_CLASSES'] = $markerArray['COLUMN_CLASSES'];

        // get the template
        if (!$templateCode = $parser->getSubpart($this->templateFile, '###' . $this->templatePart . '###')) {
            $templateCode = $this->outputError('Template ' . $this->templatePart . ' is missing', false);
        }
        // Replace default values
        $markerArray['KEY'] = $this->getContentKey();
        // replace equalizeClass
        if (!empty($this->conf['config.']['equalize'])) {
            $markerArray['EQUALIZE_CLASS'] =
                ' ' .
                $this->cObj->stdWrap(
                    $this->conf['equalizeClass'],
                    $this->conf['equalizeClass.'] ?? []
                );
        } else {
            $markerArray['EQUALIZE_CLASS'] = '';
        }
        $templateCode = $parser->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

        // Get the title template
        $titleCode = $parser->getSubpart($templateCode, '###TITLES###');
        // Get the column template
        $columnCode = $parser->getSubpart($templateCode, '###COLUMNS###');
        // Define the contentWrap
        switch (count($this->contentWrap)) {
            case 1 : {
                $contentWrap_array = [
                    $this->contentWrap[0],
                    $this->contentWrap[0],
                    $this->contentWrap[0],
                ];
                break;
            }
            case 2 : {
                $contentWrap_array = [
                    $this->contentWrap[0],
                    $this->contentWrap[0],
                    $this->contentWrap[1],
                ];
                break;
            }
            case 3 : {
                $contentWrap_array = $this->contentWrap;
                break;
            }
            default: {
                $contentWrap_array = [
                    null,
                    null,
                    null
                ];
                break;
            }
        }
        if (
            isset($this->conf['config.']['easyaccordionOpen']) &&
            $this->conf['config.']['easyaccordionOpen'] > $this->contentCount
        ) {
            $this->conf['config.']['easyaccordionOpen'] = $this->contentCount;
        }

        $titles = '';
        $columns = '';

        // fetch all contents
        for ($a = 0; $a < $this->contentCount; $a++) {
            $markerArray = [];
            // get the attribute if exist
            $markerArray['ATTRIBUTE'] = '';
            if (!empty($this->attributes[$a])) {
                $markerArray['ATTRIBUTE'] .= ' ' . $this->attributes[$a];
            }
            // if the attribute does not have a class entry, the class will be wraped for yaml (c33l, c33l, c33r)
            if (
                !empty($this->classes[$a]) &&
                isset($this->contentClass[$a]) &&
                !preg_match('/class\=/i', $markerArray['ATTRIBUTE'])
            ) {
                // wrap the class
                $markerArray['ATTRIBUTE'] .= $this->cObj->stdWrap($this->classes[$a], ['wrap' => ' class="' . $this->contentClass[$a] . '"', 'required' => 1]);
            }
            // Set the active class for the active slide
            if (
                isset($this->conf['config.']['easyaccordionOpen']) &&
                ($a + 1) ==  $this->conf['config.']['easyaccordionOpen']
            ) {
                $markerArray['EASYACCORDION_ACTIVE'] = 'class="active"';
            } else {
                $markerArray['EASYACCORDION_ACTIVE'] = '';
            }

            // render the content
            $markerArray['CONTENT'] = '';
            $markerArray['CONTENT_ID'] = $this->content_id[$a] ?? '';
            $markerArray['ID']         = $a + 1;
            $markerArray['TITLE']      = null;

            // Title will be selected if not COLUMNS (TAB, ACCORDION and SLIDER)
            if ($this->templatePart != 'TEMPLATE_COLUMNS') {
                // overwrite the title if set in $this->titles
                $markerArray['TITLE'] = $this->titles[$a];
            }

            $tsfe->register['content_id'] = $markerArray['CONTENT_ID'];
            $tsfe->register['id']         = $markerArray['ID'];
            $tsfe->register['title']      = $markerArray['TITLE'];

            if (isset($this->conf['tabKey'])) {
                $markerArray['TAB_KEY'] =
                    $this->cObj->cObjGetSingle($this->conf['tabKey'], $this->conf['tabKey.']);
            }

            // define the used wrap
            if ($a == 0) {
                $wrap = $contentWrap_array[0];
            } elseif (($a + 1) == $this->contentCount) {
                $wrap = $contentWrap_array[2];
            } else {
                $wrap = $contentWrap_array[1];
            }
            $addContent = false;
            // override the CONTENT
            if (
                $this->templatePart == 'TEMPLATE_COLUMNS' &&
                $this->conf['config.']['columnOrder']
            ) {
                switch ($this->conf['config.']['columnOrder']) {
                    case 1: {
                        // left to right, top to down
                        foreach ($this->cElements as $key => $cElements) {
                            $test = ($key - $a) / $this->contentCount;
                            if (intval($test) == $test) {
                                $markerArray['CONTENT'] .= $this->cObj->stdWrap($this->cElements[$key], ['wrap' => $wrap]);
                                $addContent = true;
                            }
                        }
                        break;
                    }
                    case 2: {
                        // right to left, top to down
                        foreach ($this->cElements as $key => $cElements) {
                            $test = ($key - ($this->contentCount - ($a + 1))) / $this->contentCount;
                            if (intval($test) == $test) {
                                $markerArray['CONTENT'] .= $this->cObj->stdWrap($this->cElements[$key], ['wrap' => $wrap]);
                                $addContent = true;
                            }
                        }
                        break;
                    }
                    case 3: {
                        // top to down, left to right

                        break;
                    }
                    case 4: {
                        // top to down, right to left

                        break;
                    }
                }
            } else {
                // wrap the content
                $markerArray['CONTENT'] =
                    $this->cObj->stdWrap($this->cElements[$a] ?? '', ['wrap' => $wrap]);
                $addContent = true;
            }
            $markerArray['REL'] = htmlspecialchars($this->rels[$a] ?? '');
            // Generate the QUOTE_TITLE
            $markerArray['DEFAULT_QUOTE_TITLE']   =
                htmlspecialchars($parser->substituteMarkerArray($this->pi_getLL('default_quote_title_template'), $markerArray, '###|###', 0));
            $markerArray['TAB_QUOTE_TITLE']       =
                htmlspecialchars($parser->substituteMarkerArray($this->pi_getLL('tab_quote_title_template'), $markerArray, '###|###', 0));
            $markerArray['ACCORDION_QUOTE_TITLE'] =
                htmlspecialchars($parser->substituteMarkerArray($this->pi_getLL('accordion_quote_title_template'), $markerArray, '###|###', 0));

            if (isset($this->conf['additionalContentMarkers'])) {
                $additonalMarkerArray = [];
                // get additional markers
                $additionalMarkers = GeneralUtility::trimExplode(', ', $this->conf['additionalContentMarkers'], true);
                // get additional marker configuration
                if(count($additionalMarkers) > 0) {
                    foreach($additionalMarkers as $additonalMarker) {
                        $markerArray[strtoupper($additonalMarker)] =
                            $this->cObj->cObjGetSingle($this->conf['additionalMarkerConf.'][$additonalMarker], $this->conf['additionalMarkerConf.'][$additonalMarker . '.']);
                    }
                }
            }

            if (
                $markerArray['CONTENT'] ||
                ($addContent && $this->confArr['showEmptyContent'])
            ) {
                // add content to COLUMNS
                $columns .= $parser->substituteMarkerArray($columnCode, $markerArray, '###|###', 0);
                // add content to TITLE
                $titles .= $parser->substituteMarkerArray($titleCode, $markerArray, '###|###', 0);
            }
        }
        $return_string = $templateCode;
        $return_string = $parser->substituteSubpart($return_string, '###TITLES###', $titles, 0);
        $return_string = $parser->substituteSubpart($return_string, '###COLUMNS###', $columns, 0);

        if (isset($this->conf['additionalMarkers'])) {
            $additonalMarkerArray = [];
            // get additional markers
            $additionalMarkers = GeneralUtility::trimExplode(', ', $this->conf['additionalMarkers'], true);
            // get additional marker configuration
            if(count($additionalMarkers) > 0) {
                foreach($additionalMarkers as $additonalMarker) {
                    $additonalMarkerArray[strtoupper($additonalMarker)] =
                        $this->cObj->cObjGetSingle(
                            $this->conf['additionalMarkerConf.'][$additonalMarker],
                            $this->conf['additionalMarkerConf.'][$additonalMarker . '.'] ?? ''
                        );
                }
            }
            // add addtional marker content to template
            $return_string = $parser->substituteMarkerArray($return_string, $additonalMarkerArray, '###|###', 0);
        }

        return $return_string;
    }

    /**
    * Return a errormessage if needed
    * @param string $msg
    * @param boolean $js
    * @return string
    */
    public function outputError($msg = '', $js = false)
    {
        if (
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->extKey]['FILEWRITER']
        ) {
            $logger = $this->getLogger();
            $logger->error($msg, []);
        }

        if (
            $this->confArr['frontendErrorMsg'] ||
            !isset($this->confArr['frontendErrorMsg'])
        ) {
            return ($js ? 'alert(' . GeneralUtility::quoteJSvalue($msg) . ')' : '<p>' . $msg . '</p>');
        } else {
            return null;
        }
    }

    /**
    * Set the piFlexform data
    *
    * @return void
    */
    protected function setFlexFormData()
    {
        if (empty($this->piFlexForm)) {
            $this->pi_initPIflexForm();
            $this->piFlexForm = $this->cObj->data['pi_flexform'];
        }
    }

    /**
     * Extract the requested information from flexform
     * @param string $sheet
     * @param string $name
     * @param boolean $devlog
     * @return string
     */
    protected function getFlexformData($sheet = '', $name = '', $devlog = true)
    {
        $result = '';

        if (!isset($this->piFlexForm['data'])) {
            if ($devlog === true) {
                $logger = $this->getLogger();
                $logger->debug('Flexform data not set.');
            }
            return $result;
        }

        if (!isset($this->piFlexForm['data'][$sheet])) {
            if ($devlog === true) {
                $logger = $this->getLogger();
                $logger->debug('Flexform sheet ' . $sheet . ' not defined');
            }
            return $result;
        }

        if (!isset($this->piFlexForm['data'][$sheet]['lDEF'][$name])) {
            if ($devlog === true) {
                $logger = $this->getLogger();
                $logger->debug('Flexform data [' . $sheet . '][' . $name . '] does not exist');
            }
            return $result;
        }

        if (isset($this->piFlexForm['data'][$sheet]['lDEF'][$name]['vDEF'])) {
            $result = $this->pi_getFFvalue($this->piFlexForm, $name, $sheet);
        } else {
            $result = $this->piFlexForm['data'][$sheet]['lDEF'][$name];
        }
        return $result;
    }

    /**
     * This function is used to escape any ' -characters when transferring text to JavaScript!
     *
     * @param string $string String to escape
     * @param bool $extended If set, also backslashes are escaped.
     * @param string $char The character to escape, default is ' (single-quote)
     * @return string Processed input string
     */
    public static function slashJS($string)
    {
        $char = '\'';
        return str_replace($char, '\\' . $char, $string);
    }

    /**
     * @return PageRepository
     */
    protected function getPageRepository(): PageRepository
    {
        return $this->getTypoScriptFrontendController()->sys_page ?: GeneralUtility::makeInstance(PageRepository::class);
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return Logger
     */
    protected function getLogger()
    {
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $result = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
        return $result;
    }
}
