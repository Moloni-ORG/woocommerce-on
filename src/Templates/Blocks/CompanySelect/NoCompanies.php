<?php

use MoloniOn\Context;

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="companies-invalid">
    <img src="<?php echo MOLONI_ON_IMAGES_URL ?>no_companies.svg" width='150px' alt="Moloni">

    <div class="companies-invalid__title">
        <?php echo __('You do not have any valid company to use the plugin', 'moloni-on') ?>
    </div>

    <div class="companies-invalid__message">
        <?php echo __('Please confirm that your account has access to an active company with a plan that allows you to access the plugins.', 'moloni-on') ?>
    </div>

    <div class="companies-invalid__help">
        <?php echo __('Learn more about our plans at: ', 'moloni-on') ?>
        <a href="<?php echo Context::configs()->get('plans_page') ?>" target="_blank">
            <?php echo Context::configs()->get('plans_page') ?>
        </a>
    </div>

    <button class="ml-button ml-button--primary"
            onclick="window.location.href = '<?php echo Context::getAdminUrl("action=logout") ?>'">
        <?php echo __('Back to login', 'moloni-on') ?>
    </button>
</div>
