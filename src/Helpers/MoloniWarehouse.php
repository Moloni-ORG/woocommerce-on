<?php

namespace MoloniOn\Helpers;

use MoloniOn\API\Warehouses;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\HelperException;

class MoloniWarehouse
{
    /**
     * Get company default warehouse ID
     *
     * @return int
     *
     * @throws HelperException
     */
    public static function getDefaultWarehouseId(): int
    {
        try {
            $results = Warehouses::queryWarehouses();
        } catch (APIExeption $e) {
            throw new HelperException(
                __('Error fetching warehouses', 'moloni-on'),
                ['message' => $e->getMessage(), 'data' => $e->getData()]
            );
        }

        foreach ($results as $result) {
            if ((bool)$result['isDefault'] === true) {
                return (int)$result['warehouseId'];
            }
        }

        throw new HelperException(
            __('No default warehouse found', 'moloni-on'),
            ['results' => $results]
        );
    }
}
