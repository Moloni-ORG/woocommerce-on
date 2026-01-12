<?php

if (!defined('ABSPATH')) {
    exit;
}

use MoloniOn\API\Warehouses;
use MoloniOn\Context;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\AutomaticDocumentsStatus;

try {
    $canSyncStock = Context::company()->canSyncStock();
    $hasWebhooks = Context::company()->hasWebhooks();

    $warehouses = $canSyncStock ? Warehouses::queryWarehouses() : [];
} catch (APIExeption $e) {
    $e->showError();
    return;
}
?>

<form method='POST' action='<?php echo esc_url(Context::getAdminUrl("tab=automation")) ?>' id='formOpcoes'>
    <?php wp_nonce_field("molonion-form-nonce"); ?>

    <input type='hidden' value='saveAutomations' name='action'>

    <div>
        <h2 class="title">
            <?php esc_html_e('Automatic actions from WooCommerce', 'moloni-on') ?>
        </h2>

        <div class="subtitle">
            (<?php esc_html_e('This actions happen when an action occours in your WooCommerce store.', 'moloni-on') ?>)
        </div>

        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label for="invoice_auto"><?php esc_html_e('Create document automatically', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="invoice_auto" name='opt[invoice_auto]' class='inputOut'>
                        <?php $invoiceAuto = Context::settings()->getInt('invoice_auto'); ?>

                        <option value='0' <?php echo ($invoiceAuto === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('No', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo ($invoiceAuto === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Yes', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'><?php esc_html_e('Automatically create document when an order is paid', 'moloni-on') ?></p>
                </td>
            </tr>

            <tr id="invoice_auto_status_line" <?php echo ($invoiceAuto === Boolean::NO ? 'style="display: none;"' : '') ?>>
                <th>
                    <label for="invoice_auto_status"><?php esc_html_e('Create documents when the order is', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="invoice_auto_status" name='opt[invoice_auto_status]' class='inputOut'>
                        <?php $invoiceAutoStatus = Context::settings()->getString('invoice_auto_status'); ?>

                        <option value='completed' <?php echo ($invoiceAutoStatus === AutomaticDocumentsStatus::COMPLETED ? 'selected' : '') ?>>
                            <?php esc_html_e('Complete', 'moloni-on') ?>
                        </option>
                        <option value='processing' <?php echo ($invoiceAutoStatus === AutomaticDocumentsStatus::PROCESSING ? 'selected' : '') ?>>
                            <?php esc_html_e('Processing', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'><?php esc_html_e('Documents will be created automatically once they are in the selected state', 'moloni-on') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_product_sync"><?php esc_html_e('Sync products', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="moloni_product_sync" name='opt[moloni_product_sync]' class='inputOut'>
                        <?php $moloniProductSync = Context::settings()->getInt('moloni_product_sync'); ?>

                        <option value='0' <?php echo ($moloniProductSync === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('No', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo ($moloniProductSync === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Yes', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'><?php esc_html_e('When saving a product in WooCommerce, the plugin will automatically create the product in Moloni or update if it already exists (only if product has SKU set)', 'moloni-on') ?></p>
                </td>
            </tr>

            <?php if ($canSyncStock) : ?>
                <tr>
                    <th>
                        <label for="moloni_stock_sync"><?php esc_html_e('Sync stocks automatically', 'moloni-on') ?></label>
                    </th>
                    <td>
                        <select id="moloni_stock_sync" name='opt[moloni_stock_sync]' class='inputOut'>
                            <?php $moloniStockSync = Context::settings()->getInt('moloni_stock_sync'); ?>

                            <option value='0' <?php echo($moloniStockSync === Boolean::NO ? 'selected' : '') ?>>
                                <?php esc_html_e('No', 'moloni-on') ?>
                            </option>
                            <option value='1' <?php echo($moloniStockSync === Boolean::YES ? 'selected' : '') ?>>
                                <?php esc_html_e('Yes', 'moloni-on') ?>
                            </option>
                        </select>
                        <p class='description'><?php esc_html_e('Automatic stock synchronization', 'moloni-on') ?></p>
                    </td>
                </tr>
            <?php endif; ?>

            <?php if ($warehouses) : ?>
                <tr>
                    <th>
                        <label for="moloni_stock_sync_warehouse"><?php esc_html_e('Sync stocks warehouse', 'moloni-on') ?></label>
                    </th>
                    <td>
                        <select id="moloni_stock_sync_warehouse" name='opt[moloni_stock_sync_warehouse]' class='inputOut'>
                            <option value='0'>
                                <?php esc_html_e('Default company warehouse', 'moloni-on') ?>
                            </option>

                            <?php $hookStockSyncWarehouse = Context::settings()->getInt('moloni_stock_sync_warehouse'); ?>

                            <optgroup label="<?php esc_html_e('Warehouses', 'moloni-on') ?>">
                                <?php foreach ($warehouses as $warehouse) : ?>
                                    <option value='<?php echo esc_attr($warehouse['warehouseId']) ?>' <?php echo ($hookStockSyncWarehouse === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                        <?php echo esc_html($warehouse['name'] . ' (' . $warehouse['number'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                        <p class='description'>
                            <?php esc_html_e('This warehouse will be used when a product is inserted or updated in WooCommerce', 'moloni-on') ?>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>

        <h2 class="title">
            <?php esc_html_e('Automatic actions from Moloni', 'moloni-on') ?>
        </h2>

        <div class="subtitle">
            (<?php esc_html_e('This actions happen when an action occours in your Moloni account.', 'moloni-on') ?>)
        </div>

        <table class="form-table">
            <tbody>

            <?php if ($hasWebhooks) : ?>
                <tr>
                    <th>
                        <label for="hook_product_sync"><?php esc_html_e('Sync products', 'moloni-on') ?></label>
                    </th>
                    <td>
                        <select id="hook_product_sync" name='opt[hook_product_sync]' class='inputOut'>
                            <?php $hookProductSync = Context::settings()->getInt('hook_product_sync'); ?>

                            <option value='0' <?php echo($hookProductSync === Boolean::NO ? 'selected' : '') ?>>
                                <?php esc_html_e('No', 'moloni-on') ?>
                            </option>
                            <option value='1' <?php echo($hookProductSync === Boolean::YES ? 'selected' : '') ?>>
                                <?php esc_html_e('Yes', 'moloni-on') ?>
                            </option>
                        </select>
                        <p class='description'><?php esc_html_e('When saving a product in Moloni ON, the plugin will automatically create the product in WooCommerce or update if it already exists', 'moloni-on') ?></p>
                    </td>
                </tr>

                <?php if ($canSyncStock) : ?>
                    <tr>
                        <th>
                            <label for="hook_stock_sync"><?php esc_html_e('Sync stocks automatically', 'moloni-on') ?></label>
                        </th>
                        <td>
                            <select id="hook_stock_sync" name='opt[hook_stock_sync]' class='inputOut'>
                                <?php $hookStockSync = Context::settings()->getInt('hook_stock_sync'); ?>

                                <option value='0' <?php echo($hookStockSync === Boolean::NO ? 'selected' : '') ?>>
                                    <?php esc_html_e('No', 'moloni-on') ?>
                                </option>
                                <option value='1' <?php echo($hookStockSync === Boolean::YES ? 'selected' : '') ?>>
                                    <?php esc_html_e('Yes', 'moloni-on') ?>
                                </option>
                            </select>
                            <p class='description'>
                                <?php esc_html_e('When a stock movement is created in Moloni ON, the movement will be recreated in WooCommerce (if product exists)', 'moloni-on') ?>
                            </p>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($warehouses) : ?>
                <tr>
                    <th>
                        <label for="hook_stock_sync_warehouse"><?php esc_html_e('Sync stocks warehouse', 'moloni-on') ?></label>
                    </th>
                    <td>
                        <select id="hook_stock_sync_warehouse" name='opt[hook_stock_sync_warehouse]' class='inputOut'>
                            <option value='1'>
                                <?php esc_html_e('Accumulated stock', 'moloni-on') ?>
                            </option>

                            <?php $hookStockSyncWarehouse = Context::settings()->getInt('hook_stock_sync_warehouse', 1); ?>

                            <optgroup label="<?php esc_html_e('Warehouses', 'moloni-on') ?>">
                                <?php foreach ($warehouses as $warehouse) : ?>
                                    <option value='<?php echo esc_attr($warehouse['warehouseId']) ?>' <?php echo($hookStockSyncWarehouse === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                        <?php echo esc_html($warehouse['name'] . ' (' . $warehouse['number'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>

                        <p class='description'>
                            <?php esc_html_e('This warehouse will be used when a product is inserted or updated in Moloni ON', 'moloni-on') ?>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <h2 class="title">
            <?php esc_html_e('Synchronization extras', 'moloni-on') ?>
        </h2>

        <div class="subtitle">
            (<?php esc_html_e('This settings will be applied to all automatic actions.', 'moloni-on') ?>)
        </div>

        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label><?php esc_html_e('Fields to sync', 'moloni-on') ?></label>
                </th>
                <td>
                    <fieldset>
                        <input type="checkbox" name="opt[sync_fields_name]" id="name"
                               value="1" <?php echo (Context::settings()->getInt('sync_fields_name') ? 'checked' : '') ?>/>
                        <label for="name"><?php esc_html_e('Name', 'moloni-on') ?></label><br/>

                        <input type="checkbox" name="opt[sync_fields_price]" id="price"
                               value="1" <?php echo (Context::settings()->getInt('sync_fields_price') ? 'checked' : '') ?>/>
                        <label for="price"><?php esc_html_e('Price', 'moloni-on') ?></label><br/>

                        <input type="checkbox" name="opt[sync_fields_description]]" id="description"
                               value="1" <?php echo (Context::settings()->getInt('sync_fields_description') ? 'checked' : '') ?>/>
                        <label for="description"><?php esc_html_e('Description', 'moloni-on') ?></label><br/>

                        <input type="checkbox" name="opt[sync_fields_visibility]" id="visibility"
                               value="1" <?php echo (Context::settings()->getInt('sync_fields_visibility') ? 'checked' : '') ?>/>
                        <label for="visibility"><?php esc_html_e('Visibility', 'moloni-on') ?></label><br/>

                        <?php if ($canSyncStock) : ?>
                            <input type="checkbox" name="opt[sync_fields_stock]" id="stock"
                                   value="1" <?php echo(Context::settings()->getInt('sync_fields_stock') ? 'checked' : '') ?>/>
                            <label for="stock"><?php esc_html_e('Stock', 'moloni-on') ?></label><br/>
                        <?php endif; ?>

                        <input type="checkbox" name="opt[sync_fields_categories]" id="categories"
                               value="1" <?php echo (Context::settings()->getInt('sync_fields_categories') ? 'checked' : '') ?>/>
                        <label for="categories"><?php esc_html_e('Categories', 'moloni-on') ?></label><br/>

                        <input type="checkbox" name="opt[sync_fields_ean]" id="ean"
                               value="1" <?php echo (Context::settings()->getInt('sync_fields_ean') ? 'checked' : '') ?>/>
                        <label for="ean"><?php esc_html_e('EAN', 'moloni-on') ?></label><br/>

                        <input type="checkbox" name="opt[sync_fields_image]" id="image"
                               value="1" <?php echo (Context::settings()->getInt('sync_fields_image') ? 'checked' : '') ?>/>
                        <label for="image"><?php esc_html_e('Image', 'moloni-on') ?></label><br/>
                    </fieldset>
                    <p class='description'>
                        <?php esc_html_e('Optional field that will sync when synchronizing products', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?php esc_html_e('Save changes', 'moloni-on') ?>">
                </td>
            </tr>

            </tbody>
        </table>
    </div>
</form>

<div id="molonion-automation-page-anchor"></div>
