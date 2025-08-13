<?php

namespace MoloniOn\API;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class DocumentSets extends EndpointAbstract
{
    /**
     * Get All Documents Set from MoloniOn
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryDocumentSets(?array $variables = []): array
    {
        $query = self::loadQuery('documentSets');

        return Curl::complex('documentSets', $query, $variables);
    }
}
