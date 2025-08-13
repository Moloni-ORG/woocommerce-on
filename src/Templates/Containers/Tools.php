<?php

use MoloniOn\Context;

if (!defined('ABSPATH')) {
    exit;
}
?>

<br>
<table class="wc_status_table wc_status_table--tools widefat">
    <tbody class="tools">

    <tr>
        <th class="p-8">
            <strong class="name">
                <?= __('Reinstall Moloni Webhooks', 'moloni-on') ?>
            </strong>
            <p class='description'>
                <?= __('Remove this store Webhooks and install them again', 'moloni-on') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a class="button button-large"
               href='<?= esc_url(Context::getAdminUrl("tab=tools&action=reinstallWebhooks")) ?>'>
                <?= __('Reinstall Moloni Webhooks', 'moloni-on') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?= __('List Moloni products', 'moloni-on') ?>
            </strong>
            <p class='description'>
                <?= __('List all products in Moloni company and import data into your WooCommerce store', 'moloni-on') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= Context::getAdminUrl('tab=moloniProductsList') ?>'
               class="button button-large"
            >
                <?= __('View Moloni products', 'moloni-on') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?= __('List WooCommerce Products', 'moloni-on') ?>
            </strong>
            <p class='description'>
                <?= __('List all products in WooCommerce store and export data to your Moloni company', 'moloni-on') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a href='<?= Context::getAdminUrl("tab=wcProductsList") ?>'
               class="button button-large"
            >
                <?= __('View WooCommerce Products', 'moloni-on') ?>
            </a>
        </td>
    </tr>

    <tr>
        <th class="p-8">
            <strong class="name">
                <?= __('Logout', 'moloni-on') ?>
            </strong>
            <p class='description'>
                <?= __('We will keep the data regarding the documents already issued', 'moloni-on') ?>
            </p>
        </th>
        <td class="run-tool p-8 text-right">
            <a class="button button-large button-primary"
               href='<?= esc_url(Context::getAdminUrl("tab=tools&action=logout")) ?>'>
                <?= __('Logout', 'moloni-on') ?>
            </a>
        </td>
    </tr>
    </tbody>
</table>

<script>
    jQuery(document).ready(function () {
        Moloni.Tools.init();
    });
</script>
