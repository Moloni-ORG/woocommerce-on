<?php

namespace MoloniOn\Services\Documents;

use MoloniOn\API\Documents;
use MoloniOn\Context;
use MoloniOn\Exceptions\APIExeption;

class OpenDocument
{
    private $documentId;

    /**
     * Construct
     *
     * @param $documentId
     */
    public function __construct($documentId)
    {
        $this->documentId = $documentId;

        try {
            $this->run();
        } catch (APIExeption $e) {}
    }

    /**
     * Service runner
     *
     * @throws APIExeption
     */
    private function run(): void
    {
        $variables = [
            'documentId' => $this->documentId
        ];

        $invoice = Documents::queryDocument($variables);

        if (isset($invoice['errors']) || !isset($invoice['data']['document']['data']['documentId'])) {
            return;
        }

        $invoice = $invoice['data']['document']['data'];

        $url = Context::configs()->get('ac_url');
        $url .= $invoice['company']['slug'] . '/';
        $url .= $invoice['documentType']['apiCodePlural'] . '/view/' . $invoice['documentId'];

        header("Location: $url");

        exit;
    }
}
