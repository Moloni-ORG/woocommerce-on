<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class DeliveryMethods extends EndpointAbstract
{
    /**
     * Create a new delivery methods
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationDeliveryMethodCreate(?array $variables = []) {
        $query = self::loadMutation('deliveryMethodCreate');

        return Curl::simple('deliveryMethodCreate', $query, $variables);
    }

    /**
     * Get All DeliveryMethods from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryDeliveryMethods(?array $variables = []): array
    {
        $query = self::loadQuery('deliveryMethods');

        return Curl::complex('deliveryMethods', $query, $variables);
    }
}
