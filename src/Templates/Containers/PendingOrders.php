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
    <h3><?php echo __('Here you can see all the orders you have to generate', 'moloni-on') ?></h3>

    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text"></label><select
                    name="action" id="bulk-action-selector-top">
                <option value="-1"><?php echo __('Bulk actions', 'moloni-on') ?></option>
                <option value="bulkGenInvoice"><?php echo __('Generate documents', 'moloni-on') ?></option>
                <option value="bulkDiscardOrder"><?php echo __('Discard documents', 'moloni-on') ?></option>
            </select>
            <input type="submit" id="doAction" class="button action" value="<?php echo __('Run', 'moloni-on') ?>">
        </div>

        <div class="tablenav-pages">
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
            <th><a><?php echo __('Order', 'moloni-on') ?></a></th>
            <th><a><?php echo __('Client', 'moloni-on') ?></a></th>
            <th><a><?php echo __('VAT', 'moloni-on') ?></a></th>
            <th><a><?php echo __('Total', 'moloni-on') ?></a></th>
            <th><a><?php echo __('Status', 'moloni-on') ?></a></th>
            <th><a><?php echo __('Payment date', 'moloni-on') ?></a></th>
            <th style="width: 350px;"></th>
        </tr>
        </thead>

        <?php if (!empty($orders) && is_array($orders)) : ?>

            <!-- Let's draw a list of all the available orders -->
            <?php foreach ($orders as $order) : ?>
                <tr id="moloni-pending-order-row-<?php echo $order->get_id() ?>">
                    <td class="">
                        <label for="moloni-pending-order-<?php echo $order->get_id() ?>" class="screen-reader-text"></label>
                        <input id="moloni-pending-order-<?php echo $order->get_id() ?>" type="checkbox"
                               value="<?php echo $order->get_id() ?>">
                    </td>
                    <td>
                        <a target="_blank"
                           href=<?php echo $order->get_edit_order_url() ?>>#<?php echo $order->get_order_number() ?></a>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_billing_first_name())) {
                            echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                        } else {
                            echo __('Unknown', 'moloni-on');
                        }
                        ?>
                    <td>
                        <?php
                        $vat = '';

                        if (defined('VAT_FIELD')) {
                            $meta = $order->get_meta(VAT_FIELD);

                            $vat = $meta;
                        }

                        echo empty($vat) ? 'n/a' : $vat;
                        ?>
                    </td>
                    <td><?php echo $order->get_total() . $order->get_currency() ?></td>
                    <td>
                        <?php
                        $availableStatus = wc_get_order_statuses();
                        $needle = 'wc-' . $order->get_status();

                        if (isset($availableStatus[$needle])) {
                            echo $availableStatus[$needle];
                        } else {
                            echo $needle;
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($order->get_date_paid())) {
                            echo gmdate('Y-m-d H:i:s', strtotime($order->get_date_paid()));
                        } else {
                            echo 'n/a';
                        }
                        ?>
                    </td>
                    <td class="order_status column-order_status" style="text-align: right">
                        <form action="<?php echo admin_url('admin.php') ?>">
                            <input type="hidden" name="page" value="molonion">
                            <input type="hidden" name="action" value="genInvoice">
                            <input type="hidden" name="id" value="<?php echo $order->get_id() ?>">

                            <select name="document_type" style="margin-right: 5px; max-width: 45%;">
                                <?php
                                $documentType = '';

                                if (defined('DOCUMENT_TYPE') && !empty(DOCUMENT_TYPE)) {
                                    $documentType = DOCUMENT_TYPE;
                                }
                                ?>

                                <?php foreach (DocumentTypes::getForRender() as $id => $name) : ?>
                                    <option value='<?php echo $id ?>' <?php echo ($documentType === $id ? 'selected' : '') ?>>
                                        <?php echo $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input type="submit"
                                   class="wp-core-ui button-primary"
                                   style="width: 80px; text-align: center; margin-right: 5px"
                                   value="<?php echo __('Create', 'moloni-on') ?>"
                            >


                            <a class="wp-core-ui button-secondary" style="width: 80px; text-align: center"
                               href="<?php echo esc_url(Context::getAdminUrl("action=remInvoice&id={$order->get_id()}")) ?>">
                                <?php echo __('Discard', 'moloni-on') ?>
                            </a>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr>
                <td colspan="8">
                    <?php echo __('No orders to be generated were found!', 'moloni-on') ?>
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

            <th><a><?php echo __('Order', 'moloni-on') ?></a></th>
            <th><a><?php echo __('Client', 'moloni-on') ?></a></th>
            <th><a><?php echo __('VAT', 'moloni-on') ?></a></th>
            <th><a><?php echo __('Total', 'moloni-on') ?></a></th>
            <th><a><?php echo __('Status', 'moloni-on') ?></a></th>
            <th><a><?php echo __('Payment date', 'moloni-on') ?></a></th>
            <th></th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php echo PendingOrders::getPagination() ?>
        </div>
    </div>
</div>

<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/PendingOrders/BulkActionModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.OrdersBulkAction({
            startingProcess: "<?php echo__('Starting process...', 'moloni-on')?>",
            noOrdersSelected: "<?php echo__('No orders selected to process', 'moloni-on')?>",
            creatingDocument: "<?php echo__('Creating document', 'moloni-on')?>",
            discardingOrder: "<?php echo__('Discarding order', 'moloni-on')?>",
            createdDocuments: "<?php echo__('Documents created:', 'moloni-on')?>",
            documentsWithErrors: "<?php echo__('Documents with errors:', 'moloni-on')?>",
            discardedOrders: "<?php echo__('Orders discarded:', 'moloni-on')?>",
            ordersWithErrors: "<?php echo__('Orders with errors:', 'moloni-on')?>",
            close: "<?php echo__('Close', 'moloni-on')?>",
        });
    });
</script>
