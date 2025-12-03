<?php

namespace MoloniOn\Models;

use MoloniOn\Context;
use MoloniOn\Enums\Boolean;

class ProductAssociations
{
    //          RETRIEVES          //

    public static function findByWcId($wcId)
    {
        return self::fetch('wc_product_id', (int)$wcId);
    }

    public static function findByWcParentId($wcParentId)
    {
        return self::fetch('wc_parent_id', (int)$wcParentId);
    }

    public static function findByMoloniId($mlId)
    {
        return self::fetch('ml_product_id', (int)$mlId);
    }

    public static function findByMoloniParentId($mlParentId)
    {
        return self::fetch('ml_parent_id', (int)$mlParentId);
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
                'company_id' => (int)Context::$MOLONI_ON_COMPANY_ID,
                'active' => Boolean::YES,
            ]
        );
    }

    public static function deleteById($id): void
    {
        self::delete('id', (int)$id);
    }

    public static function deleteByWcId($wcId): void
    {
        self::delete('wc_product_id', (int)$wcId);
    }

    public static function deleteByWcParentId($wcParentId): void
    {
        self::delete('wc_parent_id', (int)$wcParentId);
    }

    public static function deleteByMoloniId($mlId): void
    {
        self::delete('ml_product_id', (int)$mlId);
    }

    public static function deleteByMoloniParentId($mlParentId): void
    {
        self::delete('ml_parent_id', (int)$mlParentId);
    }

    //          Privates          //

    private static function fetch($field, $value)
    {
        global $wpdb;

        $tableName = Context::getTableName();
        $table = $tableName . '_product_associations';

        $query = $wpdb->prepare(
            "SELECT * FROM `$table` WHERE $field = %d AND company_id = %d",
            $value,
            Context::$MOLONI_ON_COMPANY_ID
        );

        return $wpdb->get_row($query, ARRAY_A);
    }

    private static function delete($field, $value)
    {
        global $wpdb;

        $tableName = Context::getTableName();
        $table = $tableName . '_product_associations';

        $query = $wpdb->prepare(
            "DELETE FROM `$table` WHERE $field = %d AND company_id = %d",
            $value,
            Context::$MOLONI_ON_COMPANY_ID
        );

        $wpdb->query($query);
    }
}
