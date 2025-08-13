<?php

namespace MoloniOn\API;

use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\API\Abstracts\EndpointAbstract;

class Currencies extends EndpointAbstract
{
    /**
     * Get All Currencies from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryCurrencies(?array $variables = []): array
    {
        $action = 'currencies';

        if (empty(self::$responseCache[$action])) {
            $query = self::loadQuery($action);

            self::$responseCache[$action] = Curl::complex($action, $query, $variables);
        }

        return self::$responseCache[$action];
    }

    /**
     * Get All Currencies exchanges from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     * @throws APIExeption
     */
    public static function queryCurrencyExchanges(?array $variables = []): array
    {
        $query = self::loadQuery('currencyExchanges');

        return Curl::complex('currencyExchanges', $query, $variables);
    }
}
