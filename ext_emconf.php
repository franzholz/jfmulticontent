<?php

########################################################################
# Extension Manager/Repository config file for ext "jfmulticontent".
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Multiple Content',
    'description' => 'It arranges multiple contents into one content element with multiple columns, accordions, tabs, slider, slidedeck, easyAccordion or Booklet. This extension will also extend tt_news with two new lists.',
    'category' => 'plugin',
    'version' => '2.13.0',
    'state' => 'stable',
    'clearcacheonload' => 1,
    'author' => 'Franz Holzinger, Jürgen Furrer',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.1.99',
            'typo3' => '9.5.0-11.5.99'
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'lib_jquery' => '2.1.0-0.0.0',
            'patchlayout' => '0.0.1-0.1.9',
        ],
    ],
];
