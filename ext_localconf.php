<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

(static function () {
    \ApacheSolrForTypo3\Solr\Search\SearchComponentManager::registerSearchComponent(
        'grouping',
        \ApacheSolrForTypo3\Solrgrouping\Search\GroupingComponent::class
    );
})();
