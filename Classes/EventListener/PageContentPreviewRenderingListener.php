<?php

namespace JambageCom\Jfmulticontent\EventListener;


use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterCacheableContentIsGeneratedEvent;

use JambageCom\Div2007\Base\PageContentPreviewRenderingListenerBase;
use JambageCom\Div2007\Utility\HtmlUtility;


class PageContentPreviewRenderingListener extends PageContentPreviewRenderingListenerBase {
    public $extensionKey = 'Jfmulticontent';

}
