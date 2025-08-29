<?php

use MoloniOn\Context;
use MoloniOn\API\Warehouses;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\HelperException;
use MoloniOn\Helpers\Security;
use MoloniOn\Services\WcProduct\Page\FetchAndCheckProducts;

if (!defined('ABSPATH')) {
    exit;
}

$page = (int)($_REQUEST['paged'] ?? 1);
$filters = [
    'filter_name' => sanitize_text_field($_REQUEST['filter_name'] ?? ''),
    'filter_reference' => sanitize_text_field($_REQUEST['filter_reference'] ?? ''),
];

$service = new FetchAndCheckProducts();
$service->setPage($page);
$service->setFilters($filters);

try {
    $service->run();
} catch (HelperException|APIExeption $e) {
    $e->showError();
    return;
}

$rows = $service->getRows();
$paginator = $service->getPaginator();

$currentAction = Context::getAdminUrl('tab=wcProductsList');
$backAction = Context::getAdminUrl('tab=tools');
?>

<h3>
    <?php esc_html_e('WooCommerce product listing', 'moloni-on') ?>
</h3>

<h4>
    <?php esc_html_e('This list will display all WooCommerce products from the current store and indicate errors/alerts that may exist.', 'moloni-on') ?>
    <?php esc_html_e('All actions on this page will be in the WooCommerce -> Moloni direction.', 'moloni-on') ?>
</h4>

<div class="notice notice-success m-0">
    <p>
        <?php esc_html_e('Do you want to export your entire catalogue?', 'moloni-on') ?>
    </p>

    <p class="">
        <button id="exportProductsButton" class="button button-large">
            <?php esc_html_e('Export all products', 'moloni-on') ?>
        </button>

        <button id="exportStocksButton" class="button button-large">
            <?php esc_html_e('Export all stock', 'moloni-on') ?>
        </button>
    </p>
</div>

<div class="notice notice-warning m-0 mt-4">
    <p>
        <?php esc_html_e('Moloni stock values based on:', 'moloni-on') ?>
    </p>
    <p>
        <?php
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
        ?>
    </p>
</div>

<form method="get" action='<?php echo esc_html($currentAction) ?>' class="list_form">
    <input type="hidden" name="page" value="molonion">
    <input type="hidden" name="paged" value="<?php echo esc_attr($page) ?>">
    <input type="hidden" name="tab" value="wcProductsList">

    <div class="tablenav top">
        <a href='<?php echo esc_url($backAction) ?>' class="button button-large">
            <?php esc_html_e('Back', 'moloni-on') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?php esc_html_e('Run exports', 'moloni-on') ?>
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
                <a><?php esc_html_e('Export product', 'moloni-on') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Export stock', 'moloni-on') ?></a>
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
                <?php echo Security::wp_kses_post_with_inputs($row) ?>
            <?php endforeach; ?>
        <?php else : ?>
            <tr class="text-center">
                <td colspan="100%">
                    <?php esc_html_e('No WooCommerce products were found!', 'moloni-on') ?>
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
                <a><?php esc_html_e('Export product', 'moloni-on') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?php esc_html_e('Export stock', 'moloni-on') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?php echo esc_url($backAction) ?>' class="button button-large">
            <?php esc_html_e('Back', 'moloni-on') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?php esc_html_e('Run exports', 'moloni-on') ?>
        </button>

        <div class="tablenav-pages">
            <?php echo wp_kses_post($paginator) ?>
        </div>
    </div>
</form>

<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ActionModal.php'; ?>
<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ExportProductsModal.php'; ?>
<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ExportStocksModal.php'; ?>

<div id="molonion-wc-products-page-anchor"></div>
