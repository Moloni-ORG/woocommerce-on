<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<?php

use MoloniOn\Context;
use MoloniOn\Enums\DocumentTypes;
use MoloniOn\Models\PendingOrders;

?>

<?php
/** @var WC_Order[] $orders */
$orders = PendingOrders::getAllAvailable();
?>

<div class="wrap">
    <h3><?php esc_html_e('Here you can see all the orders you have to generate', 'moloni-on') ?></h3>

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text"></label><select
                    name="action" id="bulk-action-selector-top">
                <option value="-1"><?php esc_html_e('Bulk actions', 'moloni-on') ?></option>
                <option value="bulkGenInvoice"><?php esc_html_e('Generate documents', 'moloni-on') ?></option>
                <option value="bulkDiscardOrder"><?php esc_html_e('Discard documents', 'moloni-on') ?></option>
            </select>
            <input type="submit" id="doAction" class="button action" value="<?php esc_html_e('Run', 'moloni-on') ?>">
        </div>

        <div class="tablenav-pages">
            <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo PendingOrders::getPagination() ?>
        </div>
    </div>

    <table class='wp-list-table widefat striped posts'>
        <thead>
        <tr>
            <td class="manage-column column-cb check-column">
                <label for="moloni-pending-orders-select-all" class="screen-reader-text"></label>
                <input id="moloni-pending-orders-select-all" class="moloni-pending-orders-select-all" type="checkbox">
            </td>
            <th><a><?php esc_html_e('Order', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('Client', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('VAT', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('Total', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('Status', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('Payment date', 'moloni-on') ?></a></th>
            <th style="width: 350px;"></th>
        </tr>
        </thead>

        <?php if (!empty($orders) && is_array($orders)) : ?>

            <!-- Let's draw a list of all the available orders -->
            <?php foreach ($orders as $order) : ?>
                <tr id="moloni-pending-order-row-<?php echo esc_attr($order->get_id()) ?>">
                    <td class="">
                        <label for="moloni-pending-order-<?php echo esc_attr($order->get_id()) ?>" class="screen-reader-text"></label>
                        <input id="moloni-pending-order-<?php echo esc_attr($order->get_id()) ?>" type="checkbox"
                               value="<?php echo esc_attr($order->get_id()) ?>">
                    </td>
                    <td>
                        <a target="_blank" href=<?php echo esc_url($order->get_edit_order_url()) ?>>
                            #<?php echo esc_html($order->get_order_number()) ?>
                        </a>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_billing_first_name())) {
                            echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                        } else {
                            esc_html_e('Unknown', 'moloni-on');
                        }
                        ?>
                    <td>
                        <?php
                        $vat = '';

                        if (defined('VAT_FIELD')) {
                            $meta = $order->get_meta(VAT_FIELD);

                            $vat = $meta;
                        }

                        echo esc_html(empty($vat) ? 'n/a' : $vat);
                        ?>
                    </td>
                    <td>
                        <?php echo esc_html($order->get_total() . $order->get_currency()) ?>
                    </td>
                    <td>
                        <?php
                        $availableStatus = wc_get_order_statuses();
                        $needle = 'wc-' . $order->get_status();

                        if (isset($availableStatus[$needle])) {
                            echo esc_html($availableStatus[$needle]);
                        } else {
                            echo esc_html($needle);
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_date_paid())) {
                            echo esc_html(gmdate('Y-m-d H:i:s', strtotime($order->get_date_paid())));
                        } else {
                            echo 'n/a';
                        }
                        ?>
                    </td>
                    <td class="order_status column-order_status" style="text-align: right">
                        <form action="<?php echo esc_url(admin_url('admin.php')) ?>">
                            <input type="hidden" name="page" value="molonion">
                            <input type="hidden" name="action" value="genInvoice">
                            <input type="hidden" name="id" value="<?php echo esc_attr($order->get_id()) ?>">

                            <select name="document_type" style="margin-right: 5px; max-width: 45%;">
                                <?php
                                $documentType = '';

                                if (defined('DOCUMENT_TYPE') && !empty(DOCUMENT_TYPE)) {
                                    $documentType = DOCUMENT_TYPE;
                                }
                                ?>

                                <?php foreach (DocumentTypes::getForRender() as $id => $name) : ?>
                                    <option value='<?php echo esc_attr($id) ?>' <?php echo ($documentType === $id ? 'selected' : '') ?>>
                                        <?php echo esc_html($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input type="submit"
                                   class="wp-core-ui button-primary"
                                   style="width: 80px; text-align: center; margin-right: 5px"
                                   value="<?php esc_html_e('Create', 'moloni-on') ?>"
                            >


                            <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                               href="<?php echo esc_url(Context::getAdminUrl("action=remInvoice&id={$order->get_id()}")) ?>">
                                <?php esc_html_e('Discard', 'moloni-on') ?>
                            </a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="8">
                    <?php esc_html_e('No orders to be generated were found!', 'moloni-on') ?>
                </td>
            </tr>

        <?php endif; ?>

        <tfoot>
        <tr>
            <td class="manage-column column-cb check-column">
                <label for="moloni-pending-orders-select-all-bottom" class="screen-reader-text"></label>
                <input id="moloni-pending-orders-select-all-bottom" class="moloni-pending-orders-select-all"
                       type="checkbox">
            </td>

            <th><a><?php esc_html_e('Order', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('Client', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('VAT', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('Total', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('Status', 'moloni-on') ?></a></th>
            <th><a><?php esc_html_e('Payment date', 'moloni-on') ?></a></th>
            <th></th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo PendingOrders::getPagination() ?>
        </div>
    </div>
</div>

<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/PendingOrders/BulkActionModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.OrdersBulkAction({
            startingProcess: "<?php esc_attr_e('Starting process...', 'moloni-on')?>",
            noOrdersSelected: "<?php esc_attr_e('No orders selected to process', 'moloni-on')?>",
            creatingDocument: "<?php esc_attr_e('Creating document', 'moloni-on')?>",
            discardingOrder: "<?php esc_attr_e('Discarding order', 'moloni-on')?>",
            createdDocuments: "<?php esc_attr_e('Documents created:', 'moloni-on')?>",
            documentsWithErrors: "<?php esc_attr_e('Documents with errors:', 'moloni-on')?>",
            discardedOrders: "<?php esc_attr_e('Orders discarded:', 'moloni-on')?>",
            ordersWithErrors: "<?php esc_attr_e('Orders with errors:', 'moloni-on')?>",
            close: "<?php esc_attr_e('Close', 'moloni-on')?>",
        });
    });
</script>
