<?php

namespace MoloniOn\Activators;

use MoloniOn\Context;
use WP_Site;

class Install
{
    public static function run(): void
    {
        if (!function_exists('curl_version')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('cURL library is required for using Moloni Plugin.', 'moloni-on'));
        }

        if (!class_exists('WooCommerce')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('Requires WooCommerce 3.0.0 or above.', 'moloni-on'));
        }

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
            self::createTables($tableName);
        }
    }

    /**
     * Create tables for new site
     *
     * @param WP_Site $site
     *
     * @return void
     */
    public static function initializeSite(WP_Site $site): void
    {
        $blogId = $site->blog_id;

        $tableName = Context::getTableName($blogId);

        self::createTables($tableName);
    }

    /**
     * Create API connection table
     */
    private static function createTables(string $tableName): void
    {
        global $wpdb;

        $tableName = esc_sql($tableName);
        $charsetCollate = $wpdb->get_charset_collate();

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$tableName}_api`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                main_token VARCHAR(100), 
                access_expire VARCHAR(250),
                refresh_token VARCHAR(100), 
                refresh_expire VARCHAR(250),
                client_id VARCHAR(100), 
                client_secret VARCHAR(100), 
                company_id INT,
                dated TIMESTAMP default CURRENT_TIMESTAMP
            ) ENGINE=InnoDB {$charsetCollate} AUTO_INCREMENT=2 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$tableName}_api_config`( 
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, 
                config VARCHAR(100), 
                description VARCHAR(100), 
                selected VARCHAR(100), 
                changed TIMESTAMP default CURRENT_TIMESTAMP
			) ENGINE=InnoDB {$charsetCollate} AUTO_INCREMENT=2 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$tableName}_sync_logs` (
			    log_id INT NOT NULL AUTO_INCREMENT,
                type_id INT NOT NULL,
                entity_id INT NOT NULL,
                sync_date VARCHAR(250) CHARACTER SET utf8 NOT NULL,
			    PRIMARY KEY (`log_id`)
			) ENGINE=InnoDB {$charsetCollate} AUTO_INCREMENT=1 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$tableName}_logs` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                log_level VARCHAR(100) NULL,
                company_id INT,
                message TEXT,
                context TEXT,
                created_at TIMESTAMP default CURRENT_TIMESTAMP
            ) ENGINE=InnoDB {$charsetCollate} AUTO_INCREMENT=2 ;"
        );

        $wpdb->query(
            "CREATE TABLE IF NOT EXISTS `{$tableName}_product_associations` (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                wc_product_id INT(11) NOT NULL,
                wc_parent_id INT(11) DEFAULT 0,
                ml_product_id INT(11) NOT NULL,
                ml_parent_id INT(11) DEFAULT 0,
                active INT(11) DEFAULT 1
            ) ENGINE=InnoDB {$charsetCollate} AUTO_INCREMENT=2 ;"
        );
    }
}
