<?php

namespace MoloniOn\Models;

use MoloniOn\Context;
use MoloniOn\Enums\Boolean;

class ProductAssociations
{
    //          RETRIEVES          //

    public static function findByWcId($wcId)
    {
        $condition = 'wc_product_id = ' . (int)$wcId;

        return self::fetch($condition);
    }

    public static function findByWcParentId($wcParentId)
    {
        $condition = 'wc_parent_id = ' . (int)$wcParentId;

        return self::fetch($condition);
    }

    public static function findByMoloniId($mlId)
    {
        $condition = 'ml_product_id = ' . (int)$mlId;

        return self::fetch($condition);
    }

    public static function findByMoloniParentId($mlParentId)
    {
        $condition = 'ml_parent_id = ' . (int)$mlParentId;

        return self::fetch($condition);
    }

    //          CRUD          //

    public static function add($wcId = 0, $wcParentId = 0, $mlProductId = 0, $mlParentId = 0)
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $wpdb->insert(
            "{$tableName}_product_associations",
            [
                'wc_product_id' => (int)$wcId,
                'wc_parent_id' => (int)$wcParentId,
                'ml_product_id' => (int)$mlProductId,
                'ml_parent_id' => (int)$mlParentId,
                'active' => Boolean::YES,
            ]
        );
    }

    public static function deleteById($id): void
    {
        $condition = 'id = ' . (int)$id;

        self::delete($condition);
    }

    public static function deleteByWcId($wcId): void
    {
        $condition = 'wc_product_id = ' . (int)$wcId;

        self::delete($condition);
    }

    public static function deleteByWcParentId($wcParentId): void
    {
        $condition = 'wc_parent_id = ' . (int)$wcParentId;

        self::delete($condition);
    }

    public static function deleteByMoloniId($mlId): void
    {
        $condition = 'ml_product_id = ' . (int)$mlId;

        self::delete($condition);
    }

    public static function deleteByMoloniParentId($mlParentId): void
    {
        $condition = 'ml_parent_id = ' . (int)$mlParentId;

        self::delete($condition);
    }

    //          Privates          //

    private static function fetch($condition = '')
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $query = "SELECT * FROM {$tableName}_product_associations WHERE {$condition}";

        return $wpdb->get_row($query, ARRAY_A);
    }

    private static function delete($condition = '')
    {
        global $wpdb;

        $tableName = Context::getTableName();

        $query = "DELETE FROM {$tableName}_product_associations WHERE {$condition}";

        $wpdb->query($query);
    }
}
