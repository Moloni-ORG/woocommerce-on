<?php

namespace MoloniOn\Hooks;

use MoloniOn\Context;
use MoloniOn\Enums\DocumentTypes;
use WP_Post;
use WC_Order;
use Exception;
use MoloniOn\Start;
use MoloniOn\Plugin;
use MoloniOn\Helpers\MoloniOrder;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * Class OrderView
 * Add a Moloni Windows to when user is in the order view
 * There they can create a document for that order or check the document if it was already created
 *
 * @package Moloni\Hooks
 */
class OrderView
{

    public $parent;

    /** @var array */
    private $allowedStatus = ['wc-processing', 'wc-completed'];

    /**
     * @param Plugin $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
        add_action('add_meta_boxes', [$this, 'moloni_add_meta_box']);
    }

    public function moloni_add_meta_box()
    {
        $screen = 'shop_order';

        try {
            if (class_exists(CustomOrdersTableController::class) && wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()) {
                $screen = wc_get_page_screen_id('shop-order');
            }
        } catch (Exception $ex) {}

        add_meta_box('moloni_add_meta_box', 'Moloni', [$this, 'showMoloniView'], $screen, 'side', 'core');
    }

    function showMoloniView($postOrOrderObject)
    {
        /** @var WC_Order $order */
        $order = ($postOrOrderObject instanceof WP_Post) ? wc_get_order($postOrOrderObject->ID) : $postOrOrderObject;

        if (in_array('wc-' . $order->get_status(), $this->allowedStatus)) {
            $documentId = MoloniOrder::getLastCreatedDocument($order);

            Start::login(true);

            echo '<div style="display: none"><pre>' . print_r($order->get_taxes(), true) . '</pre></div>';

            if ($documentId > 0) {
                esc_html_e('The document has already been generated in Moloni' , 'moloni-on');
                echo '<br>';

                $this->seeDocument($documentId);
                $this->getRecreateDocumentButton($order);
            } elseif ($documentId === -1) {
                esc_html_e('Document marked as generated.' , 'moloni-on');
                echo '<br><br>';

                $this->getDocumentTypeSelect();
                echo '<br><br>';

                $this->getDocumentCreateButton($order, __('Generate again' , 'moloni-on'));
            } else {
                $this->getDocumentCreateButton($order, __('Create' , 'moloni-on'));
                $this->getDocumentTypeSelect();
            }

            echo '<div style="clear:both"></div>';
        } else {
            esc_html_e('The order must be paid for in order to be generated.' , 'moloni-on');
        }
    }

    private function getDocumentTypeSelect()
    {
        $documentType = defined('DOCUMENT_TYPE') ? DOCUMENT_TYPE : '';

        ?>
        <select id="moloni_document_type" style="float:right">
            <?php foreach (DocumentTypes::getForRender() as $id => $name) : ?>
                <option value='<?php echo $id ?>' <?php echo ($documentType === $id ? 'selected' : '') ?>>
                    <?php echo $name ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    private function seeDocument($documentId)
    {
        ?>
        <a type="button"
           class="button button-primary"
           target="_BLANK"
           href="<?php echo Context::getAdminUrl("action=getInvoice&id=$documentId") ?>"
           style="margin-top: 10px; margin-left: 10px; float:right;"
        >
            <?php esc_html_e('See document', 'moloni-on') ?>
        </a>

        <?php
    }

    /**
     * Get recreate button
     *
     * @param WC_Order $order
     *
     * @return void
     */
    private function getRecreateDocumentButton($order)
    {
        ?>
        <a type="button"
           class="button"
           target="_BLANK"
           href="<?php echo Context::getAdminUrl("action=genInvoice&id={$order->get_id()}") ?>"
           style="margin-top: 10px; float:right;"
        >
            <?php esc_html_e('Generate again', 'moloni-on') ?>
        </a>
        <?php
    }

    /**
     * Get create button
     *
     * @param WC_Order $order
     * @param string $text
     *
     * @return void
     */
    private function getDocumentCreateButton($order, $text)
    {
        ?>
        <a type="button"
           class="button-primary"
           target="_BLANK"
           onclick="createMoloniDocument()"
           style="margin-left: 5px; float:right;"
        >
            <?php echo $text ?>
        </a>

        <script>
            function createMoloniDocument() {
                var redirectUrl = "<?php echo Context::getAdminUrl("action=genInvoice&id={$order->get_id()}") ?>";

                if (document.getElementById('moloni_document_type')) {
                    redirectUrl += '&document_type=' + document.getElementById('moloni_document_type').value;
                }

                window.open(redirectUrl, '_blank')
            }
        </script>
        <?php
    }
}
