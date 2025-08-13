<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class Warehouses extends EndpointAbstract
{
    /**
     * Get All Warehouses from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryWarehouse(?array $variables = []): array
    {
        $query = self::loadQuery('warehouse');

        return Curl::simple('warehouse', $query, $variables);
    }

    /**
     * Get All Warehouses from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryWarehouses(?array $variables = []): array
    {
        $action = 'warehouses';

        if (empty(self::$responseCache[$action])) {
            $query = self::loadQuery($action);

            self::$responseCache[$action] = Curl::complex($action, $query, $variables);
        }

        return self::$responseCache[$action];
    }
}
