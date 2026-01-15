<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="export-stocks-modal" class="modal" style="display: none">
    <h2>
        <?php esc_html_e('Export stocks to Moloni ON' , 'moloni-on') ?>
    </h2>
    <div>
        <p>
            <?php esc_html_e('This tool will cycle for all your WooCommerce products and will insert manual stock movements in your Moloni ON account to make sure the stocks are equal in both platforms.', 'moloni-on') ?>
        </p>
        <p>
            <?php esc_html_e('When the tool is finish, all your Moloni ON stock will be updated to match the stock on your WooCommerce store.', 'moloni-on') ?>
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
