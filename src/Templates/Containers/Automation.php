<?php

if (!defined('ABSPATH')) {
    exit;
}

use MoloniOn\API\Companies;
use MoloniOn\API\Warehouses;
use MoloniOn\Context;
use MoloniOn\Enums\MoloniPlans;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\AutomaticDocumentsStatus;

try {
    $warehouses = Warehouses::queryWarehouses();
    $company = Companies::queryCompany()['data']['company']['data'] ?? [];
} catch (APIExeption $e) {
    $e->showError();
    return;
}
?>

<form method='POST' action='<?php echo Context::getAdminUrl("tab=automation") ?>' id='formOpcoes'>
    <input type='hidden' value='saveAutomations' name='action'>
    <div>
        <h2 class="title">
            <?php echo __('Automatic actions from WooCommerce', 'moloni-on') ?>
        </h2>

        <div class="subtitle">
            (<?php echo __('This actions happen when an action occours in your WooCommerce store.', 'moloni-on') ?>)
        </div>

        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label for="invoice_auto"><?php echo __('Create document automatically', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="invoice_auto" name='opt[invoice_auto]' class='inputOut'>
                        <?php $invoiceAuto = defined('INVOICE_AUTO') ? (int)INVOICE_AUTO : Boolean::NO; ?>

                        <option value='0' <?php echo ($invoiceAuto === Boolean::NO ? 'selected' : '') ?>>
                            <?php echo __('No', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo ($invoiceAuto === Boolean::YES ? 'selected' : '') ?>>
                            <?php echo __('Yes', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'><?php echo __('Automatically create document when an order is paid', 'moloni-on') ?></p>
                </td>
            </tr>

            <tr id="invoice_auto_status_line" <?php echo ($invoiceAuto === Boolean::NO ? 'style="display: none;"' : '') ?>>
                <th>
                    <label for="invoice_auto_status"><?php echo __('Create documents when the order is', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="invoice_auto_status" name='opt[invoice_auto_status]' class='inputOut'>
                        <?php $invoiceAutoStatus = defined('INVOICE_AUTO_STATUS') ? INVOICE_AUTO_STATUS : ''; ?>

                        <option value='completed' <?php echo ($invoiceAutoStatus === AutomaticDocumentsStatus::COMPLETED ? 'selected' : '') ?>>
                            <?php echo __('Complete', 'moloni-on') ?>
                        </option>
                        <option value='processing' <?php echo ($invoiceAutoStatus === AutomaticDocumentsStatus::PROCESSING ? 'selected' : '') ?>>
                            <?php echo __('Processing', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'><?php echo __('Documents will be created automatically once they are in the selected state', 'moloni-on') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_product_sync"><?php echo __('Sync products', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="moloni_product_sync" name='opt[moloni_product_sync]' class='inputOut'>
                        <option value='0' <?php echo (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC === '0' ? 'selected' : '') ?>><?php echo __('No', 'moloni-on') ?></option>
                        <option value='1' <?php echo (defined('MOLONI_PRODUCT_SYNC') && MOLONI_PRODUCT_SYNC === '1' ? 'selected' : '') ?>><?php echo __('Yes', 'moloni-on') ?></option>
                    </select>
                    <p class='description'><?php echo __('When saving a product in WooCommerce, the plugin will automatically create the product in Moloni or update if it already exists (only if product has SKU set)', 'moloni-on') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_sync"><?php echo __('Sync stocks automatically', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="moloni_stock_sync" name='opt[moloni_stock_sync]' class='inputOut'>
                        <option value='0' <?php echo (defined('MOLONI_STOCK_SYNC') && MOLONI_STOCK_SYNC === '0' ? 'selected' : '') ?>><?php echo __('No', 'moloni-on') ?></option>
                        <option value='1' <?php echo (defined('MOLONI_STOCK_SYNC') && MOLONI_STOCK_SYNC === '1' ? 'selected' : '') ?>><?php echo __('Yes', 'moloni-on') ?></option>
                    </select>
                    <p class='description'><?php echo __('Automatic stock synchronization', 'moloni-on') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="moloni_stock_sync_warehouse"><?php echo __('Sync stocks warehouse', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="moloni_stock_sync_warehouse" name='opt[moloni_stock_sync_warehouse]' class='inputOut'>
                        <option value='0'>
                            <?php echo __('Default company warehouse', 'moloni-on') ?>
                        </option>

                        <?php $hookStockSyncWarehouse = defined('MOLONI_STOCK_SYNC_WAREHOUSE') ? (int)MOLONI_STOCK_SYNC_WAREHOUSE : 0; ?>

                        <optgroup label="<?php echo __('Warehouses', 'moloni-on') ?>">
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option
                                        value='<?php echo $warehouse['warehouseId'] ?>' <?php echo ($hookStockSyncWarehouse === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                    <?php echo $warehouse['name'] ?> (<?php echo $warehouse['number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <p class='description'>
                        <?php echo __('This warehouse will be used when a product is inserted or updated in WooCommerce', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <h2 class="title">
            <?php echo __('Automatic actions from Moloni', 'moloni-on') ?>
        </h2>

        <div class="subtitle">
            (<?php echo __('This actions happen when an action occours in your Moloni account.', 'moloni-on') ?>)
        </div>

        <table class="form-table">
            <tbody>

            <tr>
                <th>
                    <label for="hook_product_sync"><?php echo __('Sync products', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="hook_product_sync" name='opt[hook_product_sync]' class='inputOut'>
                        <option value='0' <?php echo (defined('HOOK_PRODUCT_SYNC') && HOOK_PRODUCT_SYNC === '0' ? 'selected' : '') ?>><?php echo __('No', 'moloni-on') ?></option>
                        <option value='1' <?php echo (defined('HOOK_PRODUCT_SYNC') && HOOK_PRODUCT_SYNC === '1' ? 'selected' : '') ?>><?php echo __('Yes', 'moloni-on') ?></option>
                    </select>
                    <p class='description'><?php echo __('When saving a product in Moloni, the plugin will automatically create the product in WooCommerce or update if it already exists', 'moloni-on') ?></p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="hook_stock_sync"><?php echo __('Sync stocks automatically', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="hook_stock_sync" name='opt[hook_stock_sync]' class='inputOut'>
                        <option value='0' <?php echo (defined('HOOK_STOCK_SYNC') && HOOK_STOCK_SYNC === '0' ? 'selected' : '') ?>>
                            <?php echo __('No', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo (defined('HOOK_STOCK_SYNC') && HOOK_STOCK_SYNC === '1' ? 'selected' : '') ?>>
                            <?php echo __('Yes', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'>
                        <?php echo __('When a stock movement is created in moloni, the movement will be recreated in WooCommerce (if product exists)', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="hook_stock_sync_warehouse"><?php echo __('Sync stocks warehouse', 'moloni-on') ?></label>
                </th>
                <td>
                    <select id="hook_stock_sync_warehouse" name='opt[hook_stock_sync_warehouse]' class='inputOut'>
                        <option value='1'>
                            <?php echo __('Accumulated stock', 'moloni-on') ?>
                        </option>

                        <?php $hookStockSyncWarehouse = defined('HOOK_STOCK_SYNC_WAREHOUSE') ? (int)HOOK_STOCK_SYNC_WAREHOUSE : 1 ?>

                        <optgroup label="<?php echo __('Warehouses', 'moloni-on') ?>">
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option
                                        value='<?php echo $warehouse['warehouseId'] ?>' <?php echo ($hookStockSyncWarehouse === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                    <?php echo $warehouse['name'] ?> (<?php echo $warehouse['number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>

                    <p class='description'>
                        <?php echo __('This warehouse will be used when a product is inserted or updated in Moloni', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <h2 class="title">
            <?php echo __('Synchronization extras', 'moloni-on') ?>
        </h2>

        <div class="subtitle">
            (<?php echo __('This settings will be applied to all automatic actions.', 'moloni-on') ?>)
        </div>

        <table class="form-table">
            <tbody>


            <?php if (MoloniPlans::hasVariants((int)($company['subscription'][0]['plan']['planId'] ?? 0))) : ?>
                <tr>
                    <th>
                        <label for="sync_products_with_variants">
                            <?php echo __('Sync products with variants/variations', 'moloni-on') ?>
                        </label>
                    </th>
                    <td>
                        <?php $syncProductsWithVariants = defined('SYNC_PRODUCTS_WITH_VARIANTS') ? (int)SYNC_PRODUCTS_WITH_VARIANTS : 0; ?>

                        <select id="sync_products_with_variants" name='opt[sync_products_with_variants]' class='inputOut'>
                            <option value='0' <?php echo ($syncProductsWithVariants === Boolean::NO ? 'selected' : '') ?>>
                                <?php echo __('No', 'moloni-on') ?>
                            </option>
                            <option value='1' <?php echo ($syncProductsWithVariants === Boolean::YES ? 'selected' : '') ?>>
                                <?php echo __('Yes', 'moloni-on') ?>
                            </option>
                        </select>
                        <p class='description'>
                            <?php echo __('WooCommerce product with variations will be created in Moloni as products with variants. If disabled, each WooCommerce variation will be created as a simple product.', 'moloni-on') ?>
                            <br/>
                            <?php echo __('Moloni product with variants will be created in WooCommerce as products with variations. If disabled, Moloni products with variants will not be synchronized.', 'moloni-on') ?>
                        </p>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td>
                        <input type='hidden' id='sync_products_with_variants' name='opt[sync_products_with_variants]' value="0">
                    </td>
                </tr>
            <?php endif; ?>

            <tr>
                <th>
                    <label><?php echo __('Fields to sync', 'moloni-on') ?></label>
                </th>
                <td>
                    <fieldset>
                        <input type="checkbox" name="opt[sync_fields_name]" id="name"
                               value="1" <?php echo (defined('SYNC_FIELDS_NAME') && SYNC_FIELDS_NAME === '1' ? 'checked' : '') ?>/><label
                                for="name"><?php echo __('Name', 'moloni-on') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_price]" id="price"
                               value="1" <?php echo (defined('SYNC_FIELDS_PRICE') && SYNC_FIELDS_PRICE === '1' ? 'checked' : '') ?>/><label
                                for="price"><?php echo __('Price', 'moloni-on') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_description]]" id="description"
                               value="1" <?php echo (defined('SYNC_FIELDS_DESCRIPTION') && SYNC_FIELDS_DESCRIPTION === '1' ? 'checked' : '') ?>/><label
                                for="description"><?php echo __('Description', 'moloni-on') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_visibility]" id="visibility"
                               value="1" <?php echo (defined('SYNC_FIELDS_VISIBILITY') && SYNC_FIELDS_VISIBILITY === '1' ? 'checked' : '') ?>/><label
                                for="visibility"><?php echo __('Visibility', 'moloni-on') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_stock]" id="stock"
                               value="1" <?php echo (defined('SYNC_FIELDS_STOCK') && SYNC_FIELDS_STOCK === '1' ? 'checked' : '') ?>/><label
                                for="stock"><?php echo __('Stock', 'moloni-on') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_categories]" id="categories"
                               value="1" <?php echo (defined('SYNC_FIELDS_CATEGORIES') && SYNC_FIELDS_CATEGORIES === '1' ? 'checked' : '') ?>/><label
                                for="categories"><?php echo __('Categories', 'moloni-on') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_ean]" id="ean"
                               value="1" <?php echo (defined('SYNC_FIELDS_EAN') && SYNC_FIELDS_EAN === '1' ? 'checked' : '') ?>/><label
                                for="ean"><?php echo __('EAN', 'moloni-on') ?></label><br/>
                        <input type="checkbox" name="opt[sync_fields_image]" id="image"
                               value="1" <?php echo (defined('SYNC_FIELDS_IMAGE') && SYNC_FIELDS_IMAGE === '1' ? 'checked' : '') ?>/><label
                                for="image"><?php echo __('Image', 'moloni-on') ?></label><br/>
                    </fieldset>
                    <p class='description'>
                        <?php echo __('Optional field that will sync when synchronizing products', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?php echo __('Save changes', 'moloni-on') ?>">
                </td>
            </tr>

            </tbody>
        </table>
    </div>
</form>

<script>
    jQuery(document).ready(function () {
        Moloni.Automations.init();
    });
</script>
