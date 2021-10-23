<?php

########################################################################
# Extension Manager/Repository config file for ext "jfmulticontent".
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Multiple Content',
    'description' => 'Arranges multiple contents into one content element with multiple columns, accordions, tabs, slider, slidedeck, easyAccordion or Booklet. This extension will also extend tt_news with two new lists.',
    'category' => 'plugin',
    'version' => '2.11.5',
    'state' => 'stable',
    'clearcacheonload' => 1,
    'author' => 'Franz Holzinger, Jürgen Furrer',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.4.99',
            'typo3' => '7.6.0-10.4.99'
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'lib_jquery' => '2.1.0-0.0.0',
            'patchlayout' => '0.0.1-0.1.9',
            'typo3db_legacy' => '1.0.0-1.1.99',
        ],
    ],
];
