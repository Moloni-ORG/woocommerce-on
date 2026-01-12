<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="export-products-modal" class="modal" style="display: none">
    <h2>
        <?php esc_html_e('Export products to Moloni ON', 'moloni-on') ?>
    </h2>
    <div>
        <p>
            <?php esc_html_e('This will fetch all the products in WooCommerce store and create them in your Moloni ON account.', 'moloni-on') ?>
        </p>
        <p>
            <?php esc_html_e('This may take a while, so, please keep this window open until the process finishes.', 'moloni-on') ?>
        </p>
        <p>
            <?php esc_html_e('Are you sure you want to continue?', 'moloni-on') ?>
        </p>
    </div>
    <div>
        <a class="button button-large button-secondary" href="#" rel="modal:close">
            <?php esc_html_e('Close', 'moloni-on') ?>
        </a>
        <a class="button button-large button-primary">
            <?php esc_html_e('Start', 'moloni-on') ?>
        </a>
    </div>
</div>
