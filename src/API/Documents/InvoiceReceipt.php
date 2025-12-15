<?php

namespace MoloniOn\API\Documents;

use MoloniOn\API\Abstracts\EndpointAbstract;
use MoloniOn\Curl;
use MoloniOn\Exceptions\APIExeption;

class InvoiceReceipt extends EndpointAbstract
{
    /**
     * Gets invoice-receipt information
     *
     * @param array|null $variables array variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryInvoiceReceipt(?array $variables = []): array
    {
        $query = self::loadQuery('invoiceReceipt');

        return Curl::simple('invoiceReceipt', $query, $variables);
    }

    /**
     * Gets all invoice-receipts
     *
     * @param array|null $variables array variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function queryInvoiceReceipts(?array $variables = []): array
    {
        $query = self::loadQuery('invoiceReceipts');

        return Curl::complex('invoiceReceipts', $query, $variables);
    }

    /**
     * Get document token and path for invoice-receipt
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function queryInvoiceReceiptGetPDFToken(?array $variables = []): array
    {
        $query = self::loadQuery('invoiceReceiptGetPDFToken');

        return Curl::simple('invoiceReceiptGetPDFToken', $query, $variables);
    }

    /**
     * Creates an invoice-receipt
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationInvoiceReceiptCreate(?array $variables = []): array
    {
        $query = self::loadMutation('invoiceReceiptCreate');

        return Curl::simple('invoiceReceiptCreate', $query, $variables);
    }

    /**
     * Update an invoice-receipt
     *
     * @param array|null $variables variables of the request
     *
     * @return array Api data
     *
     * @throws APIExeption
     */
    public static function mutationInvoiceReceiptUpdate(?array $variables = []): array
    {
        $query = self::loadMutation('invoiceReceiptUpdate');

        return Curl::simple('invoiceReceiptUpdate', $query, $variables);
    }

    /**
     * Creates invoice-receipt pdf
     *
     * @param array|null $variables
     *
     * @return array returns the Graphql response array or an error array
     *
     * @throws APIExeption
     */
    public static function mutationInvoiceReceiptGetPDF(?array $variables = []): array
    {
        $query = self::loadMutation('invoiceReceiptGetPDF');

        return Curl::simple('invoiceReceiptGetPDF', $query, $variables);
    }

    /**
     * Send invoice-receipt by mail
     *
     * @param array|null $variables
     *
     * @return array
     *
     * @throws APIExeption
     */
    public static function mutationInvoiceReceiptSendEmail(?array $variables = []): array
    {
        $query = self::loadMutation('invoiceReceiptSendMail');

        return Curl::simple('invoiceReceiptSendMail', $query, $variables);
    }
}
