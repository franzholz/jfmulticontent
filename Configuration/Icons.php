<?php

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'jfmulticontent-plugin' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:jfmulticontent/Resources/Public/Icons/Extension.giff'
    ],
    'extensions-jfmulticontent-wizard' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:jfmulticontent/Resources/Public/Icons/ce_wiz.gif'
    ],
];
