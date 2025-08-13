<?php

use MoloniOn\Context;
use MoloniOn\API\Warehouses;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Exceptions\HelperException;
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
    <?= __('WooCommerce product listing', 'moloni-on') ?>
</h3>

<h4>
    <?= __('This list will display all WooCommerce products from the current store and indicate errors/alerts that may exist.', 'moloni-on') ?>
    <?= __('All actions on this page will be in the WooCommerce -> Moloni direction.', 'moloni-on') ?>
</h4>

<div class="notice notice-success m-0">
    <p>
        <?= __('Do you want to export your entire catalogue?', 'moloni-on') ?>
    </p>

    <p class="">
        <button id="exportProductsButton" class="button button-large">
            <?= __('Export all products', 'moloni-on') ?>
        </button>

        <button id="exportStocksButton" class="button button-large">
            <?= __('Export all stock', 'moloni-on') ?>
        </button>
    </p>
</div>

<div class="notice notice-warning m-0 mt-4">
    <p>
        <?= __('Moloni stock values based on:', 'moloni-on') ?>
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

        echo '- ' . __('Warehouse', 'moloni-on');
        echo '<b>';
        echo ': ' . $warehouse['name'] . ' (' . $warehouse['number'] . ')';
        echo '</b>';
        ?>
    </p>
</div>

<form method="get" action='<?= $currentAction ?>' class="list_form">
    <input type="hidden" name="page" value="molonion">
    <input type="hidden" name="paged" value="<?= $page ?>">
    <input type="hidden" name="tab" value="wcProductsList">

    <div class="tablenav top">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Back', 'moloni-on') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?= __('Run exports', 'moloni-on') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>

    <table class="wp-list-table widefat striped posts">
        <thead>
        <tr>
            <th>
                <a><?= __('Name', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?= __('Reference', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?= __('Type', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?= __('Alerts', 'moloni-on') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?= __('Export product', 'moloni-on') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Export stock', 'moloni-on') ?></a>
            </th>
        </tr>
        <tr>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_name"
                        value="<?= esc_html($filters['filter_name']) ?>"
                >
            </th>
            <th>
                <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_reference"
                        value="<?= esc_html($filters['filter_reference']) ?>"
            </th>
            <th></th>
            <th></th>
            <th class="flex flex-row gap-2">
                <button type="button" class="search_button button button-primary">
                    <?= __('Search', 'moloni-on') ?>
                </button>

                <a href='<?= $currentAction ?>' class="button">
                    <?= __('Clear', 'moloni-on') ?>
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
                <?= $row ?>
            <?php endforeach; ?>
        <?php else : ?>
            <tr class="text-center">
                <td colspan="100%">
                    <?= __('No WooCommerce products were found!', 'moloni-on') ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>

        <tfoot>
        <tr>
            <th>
                <a><?= __('Name', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?= __('Reference', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?= __('Type', 'moloni-on') ?></a>
            </th>
            <th>
                <a><?= __('Alerts', 'moloni-on') ?></a>
            </th>
            <th></th>
            <th class="w-12 text-center">
                <a><?= __('Export product', 'moloni-on') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Export stock', 'moloni-on') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Back', 'moloni-on') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-exports" disabled>
            <?= __('Run exports', 'moloni-on') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>
</form>

<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ActionModal.php'; ?>
<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ExportProductsModal.php'; ?>
<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ExportStocksModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.WcProducts.init({
            'create_action': "<?= __('product creation processes.', 'moloni-on') ?>",
            'update_action': "<?= __('product update processes.', 'moloni-on') ?>",
            'stock_action': "<?= __('stock update processes.', 'moloni-on') ?>",
            'processing_product': "<?= __('Processing product', 'moloni-on') ?>",
            'successfully_processed': "<?= __('Successfully processed', 'moloni-on') ?>",
            'error_in_the_process': "<?= __('Error in the process', 'moloni-on') ?>",
            'click_to_see': "<?= __('Click to see', 'moloni-on') ?>",
            'completed': "<?= __('Completed', 'moloni-on') ?>",
        });
    });
</script>
