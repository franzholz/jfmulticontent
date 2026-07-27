<?php

########################################################################
# Extension Manager/Repository config file for ext "jfmulticontent".
########################################################################

$EM_CONF[$_EXTKEY] = [
    'title' => 'Multiple Content',
    'description' => 'It arranges multiple contents into one content element with multiple columns, accordions, tabs, slider, slidedeck, easyAccordion or Booklet.',
    'category' => 'plugin',
    'version' => '2.16.2',
    'state' => 'stable',
    'author' => 'Franz Holzinger, Jürgen Furrer',
    'author_email' => 'franz@ttproducts.de',
    'author_company' => '',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '14.0.0-14.3.99',
            'div2007' => '2.4.0-0.0.0'
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'lib_jquery' => '4.0.0-0.0.0',
            'patchlayout' => '0.4.0-0.5.99',
        ],
    ],
];
