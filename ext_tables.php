<?php
defined('TYPO3') || die('Access denied.');

call_user_func(function () {

    if (
        TYPO3_MODE == 'BE'
    ) {
        $GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses']['JambageCom\\Jfmulticontent\\Controller\\Plugin\\WizardIcon'] = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(JFMULTICONTENT_EXT) . 'Classes/Controller/Plugin/WizardIcon.php';
    }

});
