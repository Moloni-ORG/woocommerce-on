<?php

namespace MoloniOn\Activators;

use WP_Site;
use MoloniOn\Context;

class Remove
{
    public static function run(): void
    {
        Context::initContext();

        $tableNames = [];

        if (is_multisite() && function_exists('get_sites')) {
            /** @var WP_Site[] $sites */
            $sites = get_sites();

            foreach ($sites as $site) {
                $blogId = $site->blog_id;

                $tableNames[] = Context::getTableName($blogId);
            }
        } else {
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
