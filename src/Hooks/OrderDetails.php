<?php

namespace MoloniOn\Hooks;

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

        apply_filters('moloni_es_before_order_details_render', $this);

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
                'label' => $documentTypeName,
                'href' => "", // todo: create action for this
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
                        'value' => "[${documentIdsString}]"
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
                <?= __('Billing document', 'moloni_es') ?>
            </h2>
            <ul>
                <?php foreach ($this->documents as $document) : ?>
                    <li>
                        <a href="<?= $document['href'] ?>" target="_blank">
                            <?= $document['label'] ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php

        $this->htmlToRender = ob_get_clean();
    }
}
