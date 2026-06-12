<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class Documents extends EndpointAbstract
{
    /**
     * Gets document info by id
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryDocument(?array $variables = [])
    {
        $query = self::loadQuery('document');

        return Curl::simple('document', $query, $variables);
    }

    /**
     * Gets documents info
     *
     * @param array|null $variables
     *
     * @return mixed
     *
     * @throws APIExeption
     */
    public static function queryDocuments(?array $variables = [])
    {
        $query = self::loadQuery('documents');

        return Curl::simple('documents', $query, $variables);
    }
}
