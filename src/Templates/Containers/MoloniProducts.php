<?php

use MoloniOn\API\Warehouses;
use MoloniOn\Context;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Helpers\Security;
use MoloniOn\Services\MoloniProduct\Page\FetchAndCheckProducts;

if (!defined('ABSPATH')) {
    exit;
}

$page = isset($_REQUEST['paged']) ? absint(wp_unslash($_REQUEST['paged'])) : 1;
$filters = [
    'filter_name' => sanitize_text_field($_REQUEST['filter_name'] ?? ''),
    'filter_reference' => sanitize_text_field($_REQUEST['filter_reference'] ?? ''),
];

$canSyncStock = Context::company()->canSyncStock();

$service = new FetchAndCheckProducts();
$service->setPage($page);
$service->setFilters($filters);

try {
    $service->run();
} catch (APIExeption $e) {
    $e->showError();
    return;
}

$rows = $service->getRows();
$paginator = $service->getPaginator();

$currentAction = Context::getAdminUrl('tab=moloniProductsList');
$backAction = Context::getAdminUrl('tab=tools');
?>

<h3>
    <?php esc_html_e('Moloni product list', 'moloni-on') ?>
</h3>

<h4>
    <?php esc_html_e('This list will present all Moloni products from the current company and indicate errors/alerts that may exist.', 'moloni-on') ?>
    <?php esc_html_e('All actions on this page will be in the Moloni -> WooCommerce direction.', 'moloni-on') ?>
</h4>

<div class="notice notice-success m-0">
    <p>
        <?php esc_html_e('Do you want to import your entire catalogue?', 'moloni-on') ?>
    </p>

    <p class="">
        <button id="importProductsButton" class="button button-large">
            <?php esc_html_e('Import all products', 'moloni-on') ?>
        </button>

        <?php if ($canSyncStock) : ?>
            <button id="importStocksButton" class="button button-large">
                <?php esc_html_e('Import all stock', 'moloni-on') ?>
            </button>
        <?php endif; ?>
    </p>
</div>

<?php if ($canSyncStock) : ?>
    <div class="notice notice-warning m-0 mt-4">
        <p>
            <?php esc_html_e('Moloni stock values based on:', 'moloni-on') ?>
        </p>
        <p>
            <?php
            $warehouseId = $service->getWarehouseId();

            if ($warehouseId === 1) {
                echo '- <b>' . esc_attr__('Accumulated stock from all warehouses.', 'moloni-on') . '</b>';
            } else {
                try {
                    $warehouse = Warehouses::queryWarehouse([
                            'warehouseId' => $service->getWarehouseId()
                    ])['data']['warehouse']['data'];
                } catch (APIExeption $e) {
                    $e->showError();
                    return;
                }

                echo '- ' . esc_attr__('Warehouse', 'moloni-on');
                echo '<b>';
                echo ': ' . esc_html($warehouse['name']) . ' (' . esc_html($warehouse['number']) . ')';
                echo '</b>';
            }
            ?>
        </p>
    </div>
<?php endif; ?>

<form method='POST' action='<?php echo esc_url($currentAction) ?>' class="list_form">
    <?php wp_nonce_field("molonion-form-nonce"); ?>

    <input type="hidden" name="page" value="molonion">
    <input type="hidden" name="paged" value="<?php echo esc_attr($page) ?>">
    <input type="hidden" name="tab" value="moloniProductsList">

    <div class="tablenav top">
        <a href='<?php echo esc_url($backAction) ?>' class="button button-large">
            <?php esc_html_e('Back', 'moloni-on') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-imports" disabled>
            <?php esc_html_e('Run imports', 'moloni-on') ?>
        </button>

        <div class="tablenav-pages">
            <?php echo wp_kses_post($paginator) ?>
        </div>
    </div>

    <table class="wp-list-table widefat striped posts">
        <thead>
        <tr>
            <th>
                <a><?php esc_html_e('Name', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Reference', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Type', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Alerts', 'moloni-on') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Import produt', 'moloni-on') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Import stock', 'moloni-on') ?></a>
            </th>
        </tr>
        <tr>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_name"
                        value="<?php echo esc_html($filters['filter_name']) ?>"
                >
            </th>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_reference"
                        value="<?php echo esc_html($filters['filter_reference']) ?>"
            </th>
            <th></th>
            <th></th>
            <th class="flex flex-row gap-2">
                <button type="button" class="search_button button button-primary">
                    <?php esc_html_e('Search', 'moloni-on') ?>
                </button>

                <a href='<?php echo esc_url($currentAction) ?>' class="button">
                    <?php esc_html_e('Clear', 'moloni-on') ?>
                </a>
            </th>
            <th>
                <div class="text-center">
                    <input type="checkbox" class="checkbox_create_product_master m-0-important">
                </div>
            </th>
            <th>
                <div class="text-center">
                    <input type="checkbox" class="checkbox_update_stock_product_master m-0-important">
                </div>
            </th>
        </tr>
        </thead>

        <tbody>
        <?php if (!empty($rows) && is_array($rows)) : ?>
            <?php foreach ($rows as $row) : ?>
                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php echo Security::wp_kses_post_with_inputs($row) ?>
            <?php endforeach; ?>
        <?php else : ?>
            <tr class="text-center">
                <td colspan="100%">
                    <?php esc_html_e('No Moloni products were found!', 'moloni-on') ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>

        <tfoot>
        <tr>
            <th>
                <a><?php esc_html_e('Name', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Reference', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Type', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?php esc_html_e('Alerts', 'moloni-on') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Import product', 'moloni-on') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Import stock', 'moloni-on') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?php echo esc_url($backAction) ?>' class="button button-large">
            <?php esc_html_e('Back', 'moloni-on') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-imports" disabled>
            <?php esc_html_e('Run imports', 'moloni-on') ?>
        </button>

        <div class="tablenav-pages">
            <?php echo wp_kses_post($paginator) ?>
        </div>
    </div>
</form>

<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ActionModal.php'; ?>
<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ImportProductsModal.php'; ?>
<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ImportStocksModal.php'; ?>

<div id="molonion-moloni-products-page-anchor"></div>
