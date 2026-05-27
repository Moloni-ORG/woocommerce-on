<?php

namespace MoloniOn\Hooks;

use MoloniOn\Helpers\Security;
use WC_Order;
use MoloniOn\API\Documents;
use MoloniOn\Enums\DocumentStatus;
use MoloniOn\Enums\DocumentTypes;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Helpers\MoloniOrder;
use MoloniOn\Context;
use MoloniOn\Enums\Boolean;
use MoloniOn\Start;

class OrderDetails
{
    public $order;

    public $documents = [];

    public $htmlToRender = '';

    public function __construct()
    {
        add_action('woocommerce_order_details_after_customer_details', [$this, 'orderDetailsAfterCustomerDetails']);
    }

    public function orderDetailsAfterCustomerDetails(WC_Order $order)
    {
        $this->order = $order;

        if (!(new Start())->isFullyAuthed()) {
            return;
        }

        if (Context::settings()->getInt('moloni_show_download_my_account_order_view') === Boolean::NO) {
            return;
        }

        $this->loadDocuments();

        if (empty($this->documents)) {
            return;
        }

        $this->getHtmlToRender();

        apply_filters('moloni_on_before_order_details_render', $this);

        if (!empty($this->htmlToRender)) {
            echo $this->htmlToRender;
        }
    }

    private function loadDocuments(): void
    {
        $documentIds = [];

        $lastCreatedDocument = MoloniOrder::getLastCreatedDocument($this->order);

        if (!empty($lastCreatedDocument)) {
            $documentIds[] = $lastCreatedDocument;
        }

        $allCreatedCreditNotes = MoloniOrder::getAllCreatedCreditNotes($this->order);

        if (!empty($allCreatedCreditNotes)) {
            $documentIds = array_merge($documentIds, $allCreatedCreditNotes);
        }

        if (empty($documentIds)) {
            return;
        }

        $documents = $this->getDocumentsData($documentIds);

        foreach ($documents as $documentData) {
            if ($documentData['status'] !== DocumentStatus::CLOSED) {
                continue;
            }

            if (empty($documentData['pdfExport'])) {
                continue;
            }

            $documentTypeName = DocumentTypes::getDocumentTypeName($documentData['documentType']['apiCode']);

            if (empty($documentTypeName)) {
                continue;
            }

            $this->documents[] = [
                'orderId' => $this->order->get_id(),
                'documentId' => $documentData['documentId'],
                'label' => $documentTypeName,
                'data' => $documentData
            ];
        }
    }

    private function getDocumentsData(array $documentIds): array
    {
        $documentIdsString = implode(',', $documentIds);

        try {
            $variables = [
                'options' => [
                    'filter' => [
                        'field' => 'documentId',
                        'comparison' => 'in',
                        'value' => "[$documentIdsString]"
                    ]
                ]
            ];
            $documents = Documents::queryDocuments($variables);

            return $documents['data']['documents']['data'] ?? [];
        } catch (APIExeption $e) {
        }

        return [];
    }

    private function getHtmlToRender(): void
    {
        ob_start();

        ?>
        <section id="invoice_document">
            <h2>
                <?= __('Billing document', 'moloni-on') ?>
            </h2>
            <ul>
                <?php foreach ($this->documents as $document) : ?>
                    <li>
                        <a href="<?= esc_url($this->getDownloadUrl($document)) ?>" target="_blank" rel="noopener noreferrer">
                            <?= esc_html($document['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php

        $this->htmlToRender = ob_get_clean();
    }

    private function getDownloadUrl(array $document): string
    {
        $orderId = (int)$document['orderId'];
        $documentId = (int)$document['documentId'];

        $url = add_query_arg([
            'action' => 'molonion_download_order_document',
            'order_id' => $orderId,
            'document_id' => $documentId,
        ], admin_url('admin-post.php'));

        return Security::get_nonce_url($url);
    }
}
