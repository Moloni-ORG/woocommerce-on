<?php

use MoloniOn\Context;

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="companies-invalid">
    <img src="<?= MOLONI_ON_IMAGES_URL ?>no_companies.svg" width='150px' alt="Moloni">

    <div class="companies-invalid__title">
        <?= __('You do not have any valid company to use the plugin', 'moloni-on') ?>
    </div>

    <div class="companies-invalid__message">
        <?= __('Please confirm that your account has access to an active company with a plan that allows you to access the plugins.', 'moloni-on') ?>
    </div>

    <div class="companies-invalid__help">
        <?= __('Learn more about our plans at: ', 'moloni-on') ?>
        <a href="<?= Context::configs()->get('plans_page') ?>" target="_blank">
            <?= Context::configs()->get('plans_page') ?>
        </a>
    </div>

    <button class="ml-button ml-button--primary"
            onclick="window.location.href = '<?= Context::getAdminUrl("action=logout") ?>'">
        <?= __('Back to login', 'moloni-on') ?>
    </button>
</div>
