<?php

declare(strict_types=1);

namespace MoloniOn\Hooks;

use Exception;
use MoloniOn\API\Documents;
use MoloniOn\API\Documents\BillsOfLading;
use MoloniOn\API\Documents\Estimate;
use MoloniOn\API\Documents\Invoice;
use MoloniOn\API\Documents\InvoiceReceipt;
use MoloniOn\API\Documents\ProFormaInvoice;
use MoloniOn\API\Documents\PurchaseOrder;
use MoloniOn\API\Documents\Receipt;
use MoloniOn\API\Documents\SimplifiedInvoice;
use MoloniOn\Context;
use MoloniOn\Enums\DocumentTypes;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\GenericException;
use MoloniOn\Helpers\MoloniOrder;
use MoloniOn\Helpers\Security;
use MoloniOn\Services\Documents\CreateDocumentPDF;
use MoloniOn\Start;
use WC_Order;

class DownloadOrderDocument
{
    private $orderId;

    private $documentId;

    public function __construct()
    {
        add_action('admin_post_molonion_download_order_document', [$this, 'downloadOrderDocument']);
    }

    public function downloadOrderDocument(): void
    {
        $this->orderId = absint($_GET['order_id'] ?? 0);
        $this->documentId = absint($_GET['document_id'] ?? 0);

        try {
            $this
                ->verify()
                ->download();
        } catch (Exception $e) {
            wp_die('<script>window.close();</script>');
        }
    }

    /**
     * Verify request validity and permissions
     *
     * @throws GenericException
     */
    private function verify(): DownloadOrderDocument
    {
        if (!is_user_logged_in()) {
            throw new GenericException('You must be logged in to download this document.');
        }

        if (!(new Start())->isFullyAuthed()) {
            throw new GenericException('Unexpected error.');
        }

        if ($this->orderId <= 0 || $this->documentId <= 0) {
            throw new GenericException('Invalid document data.');
        }

        Security::verify_request_or_die();

        $order = wc_get_order($this->orderId);

        if (!$order instanceof WC_Order) {
            throw new GenericException('Order not found.');
        }

        // Allow admins/shop managers or the order owner.
        $currentUserId = get_current_user_id();
        $isAllowed = current_user_can('manage_woocommerce') || (int)$order->get_user_id() === (int)$currentUserId;

        if (!$isAllowed) {
            throw new GenericException('You do not have permission to download this document.');
        }

        $orderDocumentIds = [];
        $lastCreatedDocument = MoloniOrder::getLastCreatedDocument($order);

        if (!empty($lastCreatedDocument)) {
            $orderDocumentIds[] = (int)$lastCreatedDocument;
        }

        $allCreatedCreditNotes = MoloniOrder::getAllCreatedCreditNotes($order);

        if (!empty($allCreatedCreditNotes)) {
            $orderDocumentIds = array_merge($orderDocumentIds, $allCreatedCreditNotes);
        }

        if (!in_array($this->documentId, $orderDocumentIds, true)) {
            throw new GenericException('Document does not belong to this order.');
        }

        return $this;
    }

    /**
     * Download the actual document
     * @return void
     *
     * @throws GenericException|APIExeption
     */
    private function download()
    {
        $variables = [
            'documentId' => $this->documentId
        ];

        $invoice = Documents::queryDocument($variables);

        if (isset($invoice['errors']) || !isset($invoice['data']['document']['data']['documentId'])) {
            throw new GenericException(__('Document not found', 'moloni-on'));
        }

        $invoice = $invoice['data']['document']['data'];

        if (empty($invoice['pdfExport']) || $invoice['pdfExport'] === 'null') {
            new CreateDocumentPDF($this->documentId, $invoice['documentType']['apiCode']);
            sleep(2);
        }

        $mutation = [];
        $keyString = '';

        switch ($invoice['documentType']['apiCode']) {
            case DocumentTypes::INVOICE:
                $mutation = Invoice::queryInvoiceGetPDFToken($variables);
                $keyString = 'invoiceGetPDFToken';
                break;
            case DocumentTypes::INVOICE_RECEIPT:
                $mutation = InvoiceReceipt::queryInvoiceReceiptGetPDFToken($variables);
                $keyString = 'invoiceReceiptGetPDFToken';
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
            throw new GenericException(__('Error getting document', 'moloni-on'));
        }

        $url = Context::configs()->get('media_api_url') . $result['path'] . '?jwt=' . $result['token'];

        wp_redirect($url);
        exit;
    }
}
