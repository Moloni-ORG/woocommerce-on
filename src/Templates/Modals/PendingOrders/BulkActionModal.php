<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="bulk-action-progress-modal" class="modal" style="display: none">
    <h2 style="display: none" id="bulk-action-progress-title-start">
        <?php esc_html_e('Processing', 'moloni-on') ?>
        &nbsp;
        <span id="bulk-action-progress-current">0</span>
        &nbsp;
        <?php esc_html_e('of', 'moloni-on') ?>
        &nbsp;
        <span id="bulk-action-progress-total">0</span>
        &nbsp;
        <?php esc_html_e('orders', 'moloni-on') ?>.
    </h2>
    <h2 style="display: none" id="bulk-action-progress-title-finish">
        <?php esc_html_e('Progress completed', 'moloni-on') ?>
    </h2>
    <div id="bulk-action-progress-message">
    </div>
</div>
