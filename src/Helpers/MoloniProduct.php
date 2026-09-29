<?php

namespace MoloniOn\Helpers;

use MoloniOn\API\Products;
use MoloniOn\Context;
use MoloniOn\Enums\DeletionBlocker;
use MoloniOn\Exceptions\APIExeption;

class MoloniProduct
{
    /**
     * Product lists (and searches by reference) don't select deletionBlockers, as it is costly
     * to resolve. Before an update, re-read such a product by id so canUpdateName() can rely on it.
     */
    public static function withDeletionBlockers(array $moloniProduct): array
    {
        if (array_key_exists('deletionBlockers', $moloniProduct) || empty($moloniProduct['productId'])) {
            return $moloniProduct;
        }

        try {
            $query = Products::queryProduct(['productId' => (int)$moloniProduct['productId']]);
        } catch (APIExeption $e) {
            return $moloniProduct;
        }

        return $query['data']['product']['data'] ?? $moloniProduct;
    }

    /**
     * Moloni ON only rejects a name change on a product (or variant) that already has
     * non-draft documents when the company is Portuguese.
     * For every other country the name can always be updated, regardless of deletionBlockers.
     *
     * `deletable` must not be used for the documents check: it is deprecated and is also
     * false when the product only has stock movements, which would still allow a rename.
     */
    public static function canUpdateName(array $moloniProduct): bool
    {
        if (!Context::company()->isPT()) {
            return true;
        }

        return !in_array(DeletionBlocker::PRODUCT_HAS_DOCUMENT, $moloniProduct['deletionBlockers'] ?? [], true);
    }

    public static function parseMoloniStock(array $moloniProduct, int $warehouseId): float
    {
        $stock = 0.0;

        if ($warehouseId === 1) {
            $stock = (float)($moloniProduct['stock'] ?? 0);
        } else {
            foreach ($moloniProduct['warehouses'] as $warehouse) {
                $stock = (float)$warehouse['stock'];

                if ((int)$warehouse['warehouseId'] === $warehouseId) {
                    break;
                }
            }
        }

        return $stock;
    }

    public static function parseVariantAttributes(array $moloniVariant): array
    {
        $attributes = [];

        foreach ($moloniVariant["propertyPairs"] as $value) {
            $propertyName = trim($value['property']["name"]);
            $propertyValue = trim($value['propertyValue']["value"]);

            $attributes[sanitize_title($propertyName)] = $propertyValue;
        }

        return $attributes;
    }

    public static function parseParentVariantsAttributes(array $moloniProduct): array
    {
        $attributes = [];

        foreach ($moloniProduct['variants'] as $variant) {
            foreach ($variant['propertyPairs'] as $property) {
                $propertyName = trim($property['property']['name']);
                $propertyValue = trim($property['propertyValue']['value']);

                if (!isset($attributes[$propertyName])) {
                    $attributes[$propertyName] = [];
                }

                if (!in_array($propertyValue, $attributes[$propertyName], true)) {
                    $attributes[$propertyName][] = $propertyValue;
                }
            }
        }

        return $attributes;
    }
}
