<?php

namespace MoloniOn\Models;

use MoloniOn\Context;

class SyncLogs
{
    /**
     * Validity of each log in seconds
     *
     * @var int
     */
    private static $logValidity = 20;

    //          Publics          //

    /**
     * Adds a new log
     *
     * @param int $typeId
     * @param int $entityId
     *
     * @return void
     */
    public static function addTimeout(int $typeId, int $entityId)
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $wpdb->insert(
            "{$tableName}_sync_logs",
            [
                'type_id' => $typeId,
                'entity_id' => $entityId,
                'sync_date' => time() + self::$logValidity,
            ]
        );
    }

    /**
     * Procedure to check if an entity has been synced recently
     *
     * @param int|int[] $typeId
     * @param int $entityId
     *
     * @return bool
     */
    public static function hasTimeout($typeId, int $entityId): bool
    {
        /** Delete old logs before checking entry */
        self::removeExpiredTimeouts();

        return self::checkIfExists($typeId, $entityId);
    }

    /**
     * Remove expired timeouts
     *
     * @return void
     */
    public static function removeTimeouts(): void
    {
        self::removeExpiredTimeouts();
    }

    //          Privates          //

    /**
     * Checks for a log entry
     *
     * @param int|int[] $typeId
     * @param int $entityId
     *
     * @return bool
     */
    private static function checkIfExists($typeId, int $entityId): bool
    {
        global $wpdb;

        $tableName = Context::getTableName() . '_sync_logs';

        if (is_array($typeId)) {
            $placeholders = implode(',', array_fill(0, count($typeId), '%d'));

            $query = $wpdb->prepare(
                "SELECT COUNT(*) as cnt 
             FROM {$tableName} 
             WHERE entity_id = %d 
             AND type_id IN ($placeholders)",
                array_merge([$entityId], array_map('intval', $typeId))
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) as cnt 
             FROM {$tableName} 
             WHERE entity_id = %d 
             AND type_id = %d",
                $entityId,
                (int)$typeId
            );
        }

        $queryResult = $wpdb->get_var($query);

        return (int)$queryResult > 0;
    }

    /**
     * Deletes logs that have more than defined seconds (default 20)
     *
     * @return void
     */
    private static function removeExpiredTimeouts(): void
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $query = $wpdb->prepare("DELETE FROM `{$tableName}_sync_logs` WHERE sync_date < %d", time());

        $wpdb->query($query);
    }
}
