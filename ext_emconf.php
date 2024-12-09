<?php

########################################################################
# Extension Manager/Repository config file for ext "jfmulticontent".
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Multiple Content',
    'description' => 'It arranges multiple contents into one content element with multiple columns, accordions, tabs, slider, slidedeck, easyAccordion or Booklet. This extension will also extend tt_news with two new lists.',
    'category' => 'plugin',
    'version' => '2.15.5',
    'state' => 'stable',
    'author' => 'Franz Holzinger, Jürgen Furrer',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.4.0-8.4.99',
            'typo3' => '12.4.0-13.4.99',
            'div2007' => '2.2.0-0.0.0'
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'lib_jquery' => '2.1.0-0.0.0',
            'patchlayout' => '0.2.0-0.3.99',
        ],
    ],
];
