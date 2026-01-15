<?php

namespace MoloniOn\Models;

use MoloniOn\Context;
use MoloniOn\Curl;
use MoloniOn\Services\Mails\AuthenticationExpired;

class Settings
{
    /**
     * Check if a setting exists on a database and update it or create it
     *
     * @param string $option
     * @param string|null $value
     *
     * @return int
     */
    public static function setOption(string $option, ?string $value = ''): int
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $query = $wpdb->prepare("SELECT * FROM {$tableName}_api_config WHERE config = %s", $option);
        $setting = $wpdb->get_row($query, ARRAY_A);

        if (!empty($setting)) {
            $mutation = $wpdb->prepare(
                "UPDATE {$tableName}_api_config SET selected = %s WHERE config = %s",
                $value,
                $option
            );
        } else {
            $mutation = $wpdb->prepare(
                "INSERT INTO {$tableName}_api_config (selected, config) VALUES (%s, %s)",
                $value,
                $option
            );
        }

        $wpdb->query($mutation);

        return $wpdb->insert_id;
    }

    /**
     * Define company selected settings
     */
    public static function loadToContext()
    {
        global $wpdb;

        $tableName = Context::getTableName();
        $results = $wpdb->get_results("SELECT * FROM {$tableName}_api_config ORDER BY id DESC", ARRAY_A);

        $settings = [];

        foreach ($results as $result) {
            $settings[$result['config']] = $result['selected'];
        }

        Context::setSettings($settings);
    }

    /**
     * Get all available custom fields
     *
     * @return array
     */
    public static function getPossibleVatFields(): array
    {
        $customFields = [];
        $args = [
            'posts_per_page' => 50,
            'orderby' => 'date',
            'paginate' => false,
            'order' => 'DESC',
            'post_type' => 'shop_order'
        ];

        $orders = wc_get_orders($args);

        if (empty($orders)) {
            return $customFields;
        }

        foreach ($orders as $order) {
            $metas = $order->get_meta_data();

            if (empty($metas)) {
                continue;
            }

            foreach ($metas as $meta) {
                if (in_array($meta->key, $customFields)) {
                    continue;
                }

                $customFields[] = $meta->key;
            }
        }

        return $customFields;
    }
}
