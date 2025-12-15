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

class CreateDocumentPDF
{
    private $documentId;
    private $documentType;

    /**
     * Construct
     *
     * @param int $documentId
     * @param string $documentType
     */
    public function __construct(int $documentId, string $documentType)
    {
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
            'documentId' => $this->documentId,
        ];

        switch ($this->documentType) {
            case DocumentTypes::INVOICE:
                Invoice::mutationInvoiceGetPDF($variables);
                break;
            case DocumentTypes::INVOICE_RECEIPT:
                InvoiceReceipt::mutationInvoiceReceiptGetPDF($variables);
                break;
            case  DocumentTypes::RECEIPT:
                Receipt::mutationReceiptGetPDF($variables);
                break;
            case  DocumentTypes::ESTIMATE:
                Estimate::mutationEstimateGetPDF($variables);
                break;
            case  DocumentTypes::PURCHASE_ORDER:
                PurchaseOrder::mutationPurchaseOrderGetPDF($variables);
                break;
            case  DocumentTypes::PRO_FORMA_INVOICE:
                ProFormaInvoice::mutationProFormaInvoiceGetPDF($variables);
                break;
            case  DocumentTypes::SIMPLIFIED_INVOICE:
                SimplifiedInvoice::mutationSimplifiedInvoiceGetPDF($variables);
                break;
            case  DocumentTypes::BILLS_OF_LADING:
                BillsOfLading::mutationBillsOfLadingGetPDF($variables);
                break;
        }
    }
}
