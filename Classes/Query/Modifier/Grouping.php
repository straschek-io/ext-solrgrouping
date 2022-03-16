<?php
namespace ApacheSolrForTypo3\Solrgrouping\Query\Modifier;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2015 Ingo Renner <ingo@typo3.org>
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

use ApacheSolrForTypo3\Solr\Domain\Search\Query\ParameterBuilder\Grouping as GroupingBuilder;
use ApacheSolrForTypo3\Solr\Domain\Search\Query\Query;
use ApacheSolrForTypo3\Solr\Domain\Search\Query\QueryBuilder;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequest;
use ApacheSolrForTypo3\Solr\Domain\Search\SearchRequestAware;
use ApacheSolrForTypo3\Solr\Query\Modifier\Modifier;
use ApacheSolrForTypo3\Solr\Search;
use ApacheSolrForTypo3\Solr\Util;

/**
 * Modifies a query to add grouping parameters
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class Grouping implements Modifier, SearchRequestAware
{
    /**
     * @var Search
     */
    public $search;

    /**
     * @var SearchRequest
     */
    public $searchRequest;

    /**
     * Solr configuration
     *
     * @var array
     */
    protected $configuration;

    /**
     * Grouping related configuration
     *
     * plugin.tx.solr.search.grouping
     *
     * @var array
     */
    protected $groupingConfiguration;

    protected $groupingParameters = [];

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->configuration = Util::getSolrConfiguration();
        $this->groupingConfiguration = $this->configuration->getObjectByPath('plugin.tx_solr.search.grouping.');
    }

    /**
     * Modifies the given query and adds the parameters necessary
     * for result grouping.
     *
     * @param Query $query The query to modify
     * @return Query The modified query with grouping parameters
     */
    public function modifyQuery(Query $query)
    {
        $typoScriptConfiguration = $this->searchRequest->getContextTypoScriptConfiguration();
        $grouping = GroupingBuilder::fromTypoScriptConfiguration($typoScriptConfiguration);
        $queryBuilder = new QueryBuilder($typoScriptConfiguration);
        $queryBuilder->startFrom($query)->useGrouping($grouping);
        return $query;
    }
    public function setSearch(Search $search)
    {
        $this->search = $search;
    }
    public function setSearchRequest(SearchRequest $searchRequest)
    {
        $this->searchRequest = $searchRequest;
    }

    /**
     * Finds the highest number of results per group.
     *
     * Checks the global setting, as well as each group configuration's
     * individual results limit.
     *
     * The lowest limit returned will be 1, as this is the default for Solr's
     * group.limit parameter. See http://wiki.apache.org/solr/FieldCollapsing
     *
     * @return integer Highest number of results per group configured.
     */
    protected function findHighestGroupResultsLimit()
    {
        $highestLimit = 1;

        if (!empty($this->groupingConfiguration['numberOfResultsPerGroup'])) {
            $highestLimit = $this->groupingConfiguration['numberOfResultsPerGroup'];
        }

        $configuredGroups = $this->groupingConfiguration['groups.'];
        foreach ($configuredGroups as $groupName => $groupConfiguration) {
            if (!empty($groupConfiguration['numberOfResultsPerGroup'])
                && $groupConfiguration['numberOfResultsPerGroup'] > $highestLimit
            ) {
                $highestLimit = $groupConfiguration['numberOfResultsPerGroup'];
            }
        }

        return $highestLimit;
    }
}
