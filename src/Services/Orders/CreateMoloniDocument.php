<?php

namespace MoloniOn\Services\Orders;

use MoloniOn\Context;
use WC_Order;
use MoloniOn\API\Companies;
use MoloniOn\Controllers\Documents;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\DocumentStatus;
use MoloniOn\Enums\DocumentTypes;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\DocumentError;
use MoloniOn\Exceptions\DocumentWarning;

class CreateMoloniDocument
{
    /**
     * Order object
     *
     * @var WC_Order
     */
    private $order;

    /**
     * Created document id
     *
     * @var int
     */
    private $documentId = 0;

    /**
     * Document type
     *
     * @var string|null
     */
    private $documentType;

    public function __construct($orderId)
    {
        $this->order = new WC_Order((int)$orderId);
        $this->documentType = isset($_GET['document_type']) ? sanitize_text_field($_GET['document_type']) : null;

        if (empty($this->documentType) && defined('DOCUMENT_TYPE')) {
            $this->documentType = DOCUMENT_TYPE;
        }
    }

    /**
     * Run service
     *
     * @throws DocumentError
     * @throws DocumentWarning
     */
    public function run(): void
    {
        $this->checkForWarnings();

        try {
            $company = (Companies::queryCompany())['data']['company']['data'] ?? [];
        } catch (APIExeption $e) {
            throw new DocumentError(
                __('Error fetching company', 'moloni-on'),
                [
                    'message' => $e->getMessage(),
                    'data' => $e->getData(),
                ]
            );
        }

        if ($this->shouldCreateBillOfLading()) {
            $billOfLading = new Documents($this->order, $company);
            $billOfLading
                ->setDocumentType(DocumentTypes::BILLS_OF_LADING)
                ->setDocumentStatus(DocumentStatus::CLOSED)
                ->setSendEmail(Boolean::NO)
                ->setShippingInformation(Boolean::YES)
                ->createDocument();
        }

        if (isset($billOfLading)) {
            $builder = clone $billOfLading;

            $builder
                ->setDocumentStatus()
                ->setSendEmail()
                ->setShippingInformation()
                ->addRelatedDocument(
                    $billOfLading->getDocumentId(),
                    $billOfLading->getDocumentTotal(),
                    $billOfLading->getDocumentProducts()
                );

            unset($billOfLading);
        } else {
            $builder = new Documents($this->order, $company);
        }

        if ($this->documentType === DocumentTypes::INVOICE_AND_RECEIPT) {
            $builder
                ->setDocumentType(DocumentTypes::INVOICE)
                ->setDocumentStatus(DocumentStatus::CLOSED)
                ->createDocument();

            $receipt = clone $builder;

            $receipt
                ->addRelatedDocument(
                    $builder->getDocumentId(),
                    $builder->getDocumentTotal(),
                    $builder->getDocumentProducts()
                )
                ->setDocumentType(DocumentTypes::RECEIPT)
                ->setDocumentStatus(DocumentStatus::CLOSED)
                ->createDocument();
        } else {
            $builder
                ->setDocumentType($this->documentType)
                ->createDocument();
        }

        $this->documentId = $builder->getDocumentId();
    }

    //          GETS          //

    public function getDocumentId(): int
    {
        return $this->documentId ?? 0;
    }

    public function getOrderID(): int
    {
        return (int)$this->order->get_id();
    }

    public function getOrderNumber(): string
    {
        return $this->order->get_order_number() ?? '';
    }


    //          PRIVATES          //

    private function shouldCreateBillOfLading(): bool
    {
        if (!defined('DOCUMENT_STATUS') || (int)DOCUMENT_STATUS === DocumentStatus::DRAFT) {
            return false;
        }

        if ($this->documentType === DocumentTypes::BILLS_OF_LADING) {
            return false;
        }

        if (!DocumentTypes::canRelateToBillOfLading($this->documentType)) {
            return false;
        }

        if (defined('CREATE_BILL_OF_LADING')) {
            return (bool)CREATE_BILL_OF_LADING;
        }

        return false;
    }

    private function isReferencedInDatabase(): bool
    {
        return (bool)$this->order->get_meta('_molonion_sent');
    }

    /**
     * Checks if order already has a document associated
     *
     * @throws DocumentError
     */
    private function checkForWarnings(): void
    {
        if ((!isset($_GET['force']) || sanitize_text_field($_GET['force']) !== 'true') && $this->isReferencedInDatabase()) {
            $forceUrl = Context::getAdminUrl("action=genInvoice&id={$this->order->get_id()}&force=true");

            if (!empty($this->documentType)) {
                $forceUrl .= '&document_type=' . sanitize_text_field($this->documentType);
            }

            // Translators: %1$s is the order name.
            $errorMsg = sprintf(__('The order %s document was previously generated!', 'moloni-on'), $this->order->get_order_number());
            $errorMsg .= " <a href='" . esc_url($forceUrl) . "'>" . __('Generate again', 'moloni-on') . '</a>';

            throw new DocumentError($errorMsg);
        }
    }
}
