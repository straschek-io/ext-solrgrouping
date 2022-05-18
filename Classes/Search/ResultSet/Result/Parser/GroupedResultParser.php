<?php

namespace ApacheSolrForTypo3\Solrgrouping\Search\ResultSet\Result\Parser;

use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\Group;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Grouping\GroupItem;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\Parser\AbstractResultParser;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\Result\SearchResultCollection;
use ApacheSolrForTypo3\Solr\Domain\Search\ResultSet\SearchResultSet;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use ApacheSolrForTypo3\Solr\System\Solr\Document\Document;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GroupedResultParser extends AbstractResultParser
{
    public function parse(SearchResultSet $resultSet, bool $useRawDocuments = true): SearchResultSet
    {
        /** @var SearchResultCollection $searchResults */
        $searchResults = GeneralUtility::makeInstance(SearchResultCollection::class);
        $parsedData = $resultSet->getResponse()->getParsedData();

        $solrGroupCollection = $searchResults->getGroups();
        $collectedMaximumScore = 0.0;
        $collectedNumFound = 0;

        $typoScriptConfiguration = $resultSet->getUsedSearchRequest()->getContextTypoScriptConfiguration();
        foreach ($typoScriptConfiguration->getSearchGroupingGroupsConfiguration() as $key => $configuration) {
            if ($configuration['field'] && $groupedResult = $parsedData->grouped->{$configuration['field']}) {
                if ($groupedResult->matches === 0) {
                    continue;
                }

                $group = new Group($key);
                foreach ($groupedResult->groups as $rawGroupItem) {
                    if ($rawGroupItem->doclist->numFound === 0) {
                        continue;
                    }

                    $documents = $rawGroupItem->doclist->docs;
                    $documents = $this->getObjectsFromRawDocuments($documents);
                    if (!$useRawDocuments) {
                        $documents = $this->documentEscapeService->applyHtmlSpecialCharsOnAllFields($documents);
                    }

                    $groupItem = new GroupItem(
                        $group,
                        $rawGroupItem->groupValue,
                        $rawGroupItem->doclist->numFound,
                        $rawGroupItem->doclist->start,
                        $rawGroupItem->doclist->maxScore
                    );

                    $collectedMaximumScore = ($groupItem->getMaximumScore() > $collectedMaximumScore) ? $groupItem->getMaximumScore() : $collectedMaximumScore;

                    foreach ($documents as $searchResult) {
                        $searchResultObject = $this->searchResultBuilder->fromApacheSolrDocument($searchResult);
                        $groupItem->addSearchResult($searchResultObject);
                    }

                    $group->addGroupItem($groupItem);
                }

                $solrGroupCollection->add($group);
                $collectedNumFound += $groupedResult->ngroups;
            }

            $searchResults->setGroups($solrGroupCollection);
        }
        $resultSet->setMaximumScore($collectedMaximumScore);
        $resultSet->setAllResultCount($collectedNumFound);
        $resultSet->setSearchResults($searchResults);
        return $resultSet;
    }

    public function canParse(SearchResultSet $resultSet): bool
    {
        $configuration = $resultSet->getUsedSearchRequest()->getContextTypoScriptConfiguration();
        if ($configuration instanceof TypoScriptConfiguration && $configuration->getIsSearchGroupingEnabled()) {
            return true;
        }

        return false;
    }

    private function getObjectsFromRawDocuments(array $rawDocuments)
    {
        $documents = [];

        foreach ($rawDocuments as $rawDocument) {
            $fields = get_object_vars($rawDocument);
            $document = new Document($fields);
            $documents[] = $document;
        }

        return $documents;
    }
}
