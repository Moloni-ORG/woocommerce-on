<?php

use MoloniOn\API\Warehouses;
use MoloniOn\Context;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Services\MoloniProduct\Page\FetchAndCheckProducts;

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
    <?= __('Moloni product list', 'moloni-on') ?>
</h3>

<h4>
    <?= __('This list will present all Moloni products from the current company and indicate errors/alerts that may exist.', 'moloni-on') ?>
    <?= __('All actions on this page will be in the Moloni -> WooCommerce direction.', 'moloni-on') ?>
</h4>

<div class="notice notice-success m-0">
    <p>
        <?= __('Do you want to import your entire catalogue?', 'moloni-on') ?>
    </p>

    <p class="">
        <button id="importProductsButton" class="button button-large">
            <?= __('Import all products', 'moloni-on') ?>
        </button>

        <button id="importStocksButton" class="button button-large">
            <?= __('Import all stock', 'moloni-on') ?>
        </button>
    </p>
</div>

<div class="notice notice-warning m-0 mt-4">
    <p>
        <?= __('Moloni stock values based on:', 'moloni-on') ?>
    </p>
    <p>
        <?php
        $warehouseId = $service->getWarehouseId();

        if ($warehouseId === 1) {
            echo '- <b>' . __('Accumulated stock from all warehouses.', 'moloni-on') . '</b>';
        } else {
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
        }
        ?>
    </p>
</div>

<form method="get" action='<?= $currentAction ?>' class="list_form">
    <input type="hidden" name="page" value="molonion">
    <input type="hidden" name="paged" value="<?= $page ?>">
    <input type="hidden" name="tab" value="moloniProductsList">

    <div class="tablenav top">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Back', 'moloni-on') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-imports" disabled>
            <?= __('Run imports', 'moloni-on') ?>
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
                <a><?= __('Import produt', 'moloni-on') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Import stock', 'moloni-on') ?></a>
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
                    <?= __('No Moloni products were found!', 'moloni-on') ?>
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
                <a><?= __('Import product', 'moloni-on') ?></a>
            </th>
            <th class="w-12 text-center">
                <a><?= __('Import stock', 'moloni-on') ?></a>
            </th>
        </tr>
        </tfoot>
    </table>

    <div class="tablenav bottom">
        <a href='<?= $backAction ?>' class="button button-large">
            <?= __('Back', 'moloni-on') ?>
        </a>

        <button type="button" class="button button-large button-primary button-start-imports" disabled>
            <?= __('Run imports', 'moloni-on') ?>
        </button>

        <div class="tablenav-pages">
            <?= $paginator ?>
        </div>
    </div>
</form>

<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ActionModal.php'; ?>
<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ImportProductsModal.php'; ?>
<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Products/ImportStocksModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.MoloniProducts.init({
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
