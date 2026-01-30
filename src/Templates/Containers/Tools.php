<?php

use MoloniOn\Context;

if (!defined('ABSPATH')) {
    exit;
}
?>

<br>
<table class="wc_status_table wc_status_table--tools widefat">
    <tbody class="tools">

    <?php if (Context::company()->hasWebhooks()) : ?>
        <tr>
            <th class="p-8">
                <strong class="name">
                    <?php esc_html_e('Reinstall Moloni ON Webhooks', 'moloni-on') ?>
                </strong>
                <p class='description'>
                    <?php esc_html_e('Remove this store Webhooks and install them again.', 'moloni-on') ?>
                </p>
            </th>
            <td class="run-tool p-8 text-right">
                <a class="button button-large"
                   href='<?php echo esc_url(Context::getAdminUrl("tab=tools&action=reinstallWebhooks")) ?>'>
                    <?php esc_html_e('Reinstall Moloni ON Webhooks', 'moloni-on') ?>
                </a>
            </td>
        </tr>
    <?php endif; ?>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?php esc_html_e('List Moloni ON products', 'moloni-on') ?>
            </strong>
            <p class='description'>
                <?php esc_html_e('List all products in Moloni ON company and import data into your WooCommerce store.', 'moloni-on') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?php echo esc_url(Context::getAdminUrl('tab=moloniProductsList')) ?>'
               class="button button-large"
            >
                <?php esc_html_e('View Moloni ON products', 'moloni-on') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?php esc_html_e('List WooCommerce products', 'moloni-on') ?>
            </strong>
            <p class='description'>
                <?php esc_html_e('List all products in WooCommerce store and export data to your Moloni ON company.', 'moloni-on') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?php echo esc_url(Context::getAdminUrl("tab=wcProductsList")) ?>'
               class="button button-large"
            >
                <?php esc_html_e('View WooCommerce products', 'moloni-on') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?php esc_html_e('Sign out', 'moloni-on') ?>
            </strong>
            <p class='description'>
                <?php esc_html_e('Log out of your Moloni ON account. Data relating to issued documents will be saved.', 'moloni-on') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a class="button button-large button-primary"
               href='<?php echo esc_url(Context::getAdminUrl("tab=tools&action=logout")) ?>'>
                <?php esc_html_e('Sign out', 'moloni-on') ?>
            </a>
        </td>
    </tr>
    </tbody>
</table>

<div id="molonion-tools-page-anchor"></div>
