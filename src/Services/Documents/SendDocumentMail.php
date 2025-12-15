<?php

namespace MoloniOn\Services\Documents;

use MoloniOn\API\Documents\BillsOfLading;
use MoloniOn\API\Documents\Estimate;
use MoloniOn\API\Documents\Invoice;
use MoloniOn\API\Documents\InvoiceReceipt;
use MoloniOn\API\Documents\ProFormaInvoice;
use MoloniOn\API\Documents\PurchaseOrder;
use MoloniOn\API\Documents\Receipt;
use MoloniOn\API\Documents\SimplifiedInvoice;
use MoloniOn\Enums\DocumentTypes;
use MoloniOn\Exceptions\APIExeption;

class SendDocumentMail
{
    private $name;
    private $email;
    private $documentId;
    private $documentType;

    /**
     * Construct
     *
     * @param int $documentId
     * @param string $documentType
     * @param string $name
     * @param string $email
     */
    public function __construct(int $documentId, string $documentType, string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
        $this->documentId = $documentId;
        $this->documentType = $documentType;

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
            'documents' => [
                $this->documentId
            ],
            'mailData' => [
                'to' => [
                    'name' => $this->name,
                    'email' => $this->email
                ],
                'message' => '',
                'attachment' => true
            ]
        ];

        switch ($this->documentType) {
            case DocumentTypes::INVOICE:
                Invoice::mutationInvoiceSendMail($variables);
                break;
            case DocumentTypes::INVOICE_RECEIPT:
                InvoiceReceipt::mutationInvoiceReceiptSendEmail($variables);
                break;
            case DocumentTypes::RECEIPT:
                Receipt::mutationReceiptSendMail($variables);
                break;
            case DocumentTypes::ESTIMATE:
                Estimate::mutationEstimateSendMail($variables);
                break;
            case DocumentTypes::PURCHASE_ORDER:
                PurchaseOrder::mutationPurchaseOrderSendMail($variables);
                break;
            case DocumentTypes::PRO_FORMA_INVOICE:
                ProFormaInvoice::mutationProFormaInvoiceSendMail($variables);
                break;
            case DocumentTypes::SIMPLIFIED_INVOICE:
                SimplifiedInvoice::mutationSimplifiedInvoiceSendMail($variables);
                break;
            case DocumentTypes::BILLS_OF_LADING:
                BillsOfLading::mutationBillsOfLadingSendMail($variables);
                break;
        }
    }
}
