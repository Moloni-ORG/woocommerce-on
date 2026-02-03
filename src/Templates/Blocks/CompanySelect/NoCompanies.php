<?php

use MoloniOn\Context;

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="companies-invalid">
    <img src="<?php echo esc_url(MOLONI_ON_IMAGES_URL) ?>no_companies.svg" width='150px' alt="Moloni">

    <div class="companies-invalid__title">
        <?php esc_html_e('Your account does not have access to any company', 'moloni-on') ?>
    </div>

    <div class="companies-invalid__message">
        <?php esc_html_e("Please ensure your account's company has the API Client add-on active.", 'moloni-on') ?>
    </div>

    <div class="companies-invalid__help">
        <?php esc_html_e('Learn more at:', 'moloni-on') ?>
        <a href="<?php echo esc_url(Context::configs()->get('plans_page')) ?>" target="_blank">
            <?php echo esc_html(Context::configs()->get('plans_page')) ?>
        </a>
    </div>

    <button class="ml-button ml-button--primary"
            onclick="window.location.href = '<?php echo esc_url(Context::getAdminUrl("action=logout")) ?>'">
        <?php esc_html_e('Back to login', 'moloni-on') ?>
    </button>
</div>
