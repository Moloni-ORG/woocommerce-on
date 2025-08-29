<?php

namespace MoloniOn\Activators;

use WP_Site;
use MoloniOn\Context;
use MoloniOn\Helpers\Development;

class Remove
{
    public static function run(): void
    {
        Context::initContext();

        $tableNames = [];

        $isDev = Context::configs()->get('is_dev');

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                $blogId = $site->blog_id;

                $isDev ?
                    $tableNames = array_merge($tableNames, Development::getPlatformsTableNames($blogId)) :
                    $tableNames[] = Context::getTableName($blogId);
            }
        } else {
            $isDev ?
                $tableNames = Development::getPlatformsTableNames() :
                $tableNames[] = Context::getTableName();
        }

        foreach ($tableNames as $tableName) {
            self::dropTables($tableName);
        }
    }

    public static function uninitializeSite(WP_Site $site): void
    {
        $tableNames = [];

        $blogId = $site->blog_id;

        Context::configs()->get('is_dev') ?
            $tableNames = Development::getPlatformsTableNames($blogId) :
            $tableNames[] = Context::getTableName($blogId);

        foreach ($tableNames as $tableName) {
            self::dropTables($tableName);
        }
    }

    private static function dropTables(string $tableName): void
    {
        global $wpdb;

        $tableName = esc_sql($tableName);

        $wpdb->query("DROP TABLE IF EXISTS `{$tableName}_api`");
        $wpdb->query("DROP TABLE IF EXISTS `{$tableName}_config`");
        $wpdb->query("DROP TABLE IF EXISTS `{$tableName}_logs`");
        $wpdb->query("DROP TABLE IF EXISTS `{$tableName}_sync_logs`");
    }
}
