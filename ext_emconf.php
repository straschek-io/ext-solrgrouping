<?php
$EM_CONF['solrgrouping'] = [
    'title' => 'Apache Solr for TYPO3 - Result Grouping',
    'description' => 'Solr Result Grouping allows grouping of documents sharing a common field. For each group the most relevant documents are returned.',
    'version' => '1.4.0-dev',
    'state' => 'stable',
    'category' => 'plugin',
    'author' => 'Ingo Renner',
    'author_email' => 'ingo@typo3.org',
    'author_company' => 'dkd Internet Service GmbH',
    'module' => '',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-10.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'ApacheSolrForTypo3\\Solrgrouping\\' => 'Classes/'
        ]
    ],
    '_md5_values_when_last_written' => ''
];
