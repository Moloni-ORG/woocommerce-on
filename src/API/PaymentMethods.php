<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class PaymentMethods extends EndpointAbstract
{
    /**
     * Get payment methods info
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryPaymentMethod(?array $variables = []): array
    {
        $query = self::loadQuery('paymentMethod');

        return Curl::simple('paymentMethod', $query, $variables);
    }

    /**
     * Get All Payment Methods from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryPaymentMethods(?array $variables = []): array
    {
        $action = 'paymentMethods';

        if (empty(self::$responseCache[$action])) {
            $query = self::loadQuery($action);

            self::$responseCache[$action] = Curl::complex($action, $query, $variables);
        }

        return self::$responseCache[$action];
    }

    /**
     * Creates a payment method
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function mutationPaymentMethodCreate(?array $variables = [])
    {
        $query = self::loadMutation('paymentMethodCreate');

        return Curl::simple('paymentMethodCreate', $query, $variables);
    }
}
