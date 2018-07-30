<?php declare(strict_types = 1);
namespace Portrino\PxShopware\Xclass\Solr\Domain\Index\Queue;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2017 dkd Internet Service GmbH <solr-support@dkd.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

use ApacheSolrForTypo3\Solr\IndexQueue\Item;
use ApacheSolrForTypo3\Solr\Site;
use ApacheSolrForTypo3\Solr\System\Logging\SolrLogManager;
use ApacheSolrForTypo3\Solr\System\Records\AbstractRepository;
use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class QueueItemRepository
 * Handles all CRUD operations to tx_solr_indexqueue_item table
 *
 */
class QueueItemRepository extends \ApacheSolrForTypo3\Solr\Domain\Index\Queue\QueueItemRepository
{
    /**
     * Returns the records for suitable item type.
     *
     * @param array $indexQueueItemRecords
     * @return array
     */
    protected function getAllQueueItemRecordsByUidsGroupedByTable(array $indexQueueItemRecords) : array
    {
        $tableUids = [];
        $tableRecords = [];
        // grouping records by table
        foreach ($indexQueueItemRecords as $indexQueueItemRecord) {
            $tableUids[$indexQueueItemRecord['item_type']][] = $indexQueueItemRecord['item_uid'];
        }

        // fetching records by table, saves us a lot of single queries
        foreach ($tableUids as $table => $uids) {

            if (isset($GLOBALS['TCA'][$table])) {
                $uidList = implode(',', $uids);

                $queryBuilderForRecordTable = $this->getQueryBuilder();
                $queryBuilderForRecordTable->getRestrictions()->removeAll();
                $resultsFromRecordTable = $queryBuilderForRecordTable
                    ->select('*')
                    ->from($table)
                    ->where($queryBuilderForRecordTable->expr()->in('uid', $uidList))
                    ->execute();
                $records = [];
                while ($record = $resultsFromRecordTable->fetch()) {
                    $records[$record['uid']] = $record;
                }

                $tableRecords[$table] = $records;
            }
            $this->hookPostProcessFetchRecordsForIndexQueueItem($table, $uids, $tableRecords);
        }

        return $tableRecords;
    }
}
