<?php

namespace MoloniOn\Services\Documents;

use MoloniOn\API\Documents;
use MoloniOn\API\Documents\BillsOfLading;
use MoloniOn\API\Documents\Estimate;
use MoloniOn\API\Documents\Invoice;
use MoloniOn\API\Documents\ProFormaInvoice;
use MoloniOn\API\Documents\PurchaseOrder;
use MoloniOn\API\Documents\Receipt;
use MoloniOn\API\Documents\SimplifiedInvoice;
use MoloniOn\Context;
use MoloniOn\Enums\DocumentTypes;
use MoloniOn\Exceptions\APIExeption;

class DownloadDocumentPDF
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
        } catch (APIExeption $e) {
            $this->showError(__('Unexpected error', 'moloni-on'));
        }
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
            $this->showError(__('Document not found', 'moloni-on'));

            return;
        }

        $invoice = $invoice['data']['document']['data'];

        if (empty($invoice['pdfExport']) || $invoice['pdfExport'] === 'null') {
            new CreateDocumentPDF($this->documentId, $invoice['documentType']['apiCode']);
        }

        $mutation = [];
        $keyString = '';

        switch ($invoice['documentType']['apiCode']) {
            case DocumentTypes::INVOICE:
                $mutation = Invoice::queryInvoiceGetPDFToken($variables);
                $keyString = 'invoiceGetPDFToken';
                break;
            case DocumentTypes::RECEIPT:
                $mutation = Receipt::queryReceiptGetPDFToken($variables);
                $keyString = 'receiptGetPDFToken';
                break;
            case DocumentTypes::ESTIMATE:
                $mutation = Estimate::queryEstimateGetPDFToken($variables);
                $keyString = 'estimateGetPDFToken';
                break;
            case DocumentTypes::PURCHASE_ORDER:
                $mutation = PurchaseOrder::queryPurchaseOrderGetPDFToken($variables);
                $keyString = 'purchaseOrderGetPDFToken';
                break;
            case DocumentTypes::PRO_FORMA_INVOICE:
                $mutation = ProFormaInvoice::queryProFormaInvoiceGetPDFToken($variables);
                $keyString = 'proFormaInvoiceGetPDFToken';
                break;
            case DocumentTypes::SIMPLIFIED_INVOICE:
                $mutation = SimplifiedInvoice::querySimplifiedInvoiceGetPDFToken($variables);
                $keyString = 'simplifiedInvoiceGetPDFToken';
                break;
            case DocumentTypes::BILLS_OF_LADING:
                $mutation = BillsOfLading::queryBillsOfLadingGetPDFToken($variables);
                $keyString = 'billsOfLadingGetPDFToken';
                break;
        }

        $result = $mutation['data'][$keyString]['data'] ?? [];

        if (empty($result)) {
            $this->showError(__('Error getting document', 'moloni-on'));

            return;
        }

        $url = Context::configs()->get('media_api_url') . $result['path'] . '?jwt=' . $result['token'];

        header("Location: $url");
    }

    private function showError($message): void
    {
        echo "<script>";
        echo "  alert('" . esc_html($message) . "');";
        echo "  window.close();";
        echo "</script>";
    }
}
