<?php

use MoloniOn\Context;

if (!defined('ABSPATH')) {
    exit;
}

$company = $company ?? [];
?>

<div class="companies__card">
    <div class="companies__card-content">
        <div class="companies__card-header">
            <div class="companies__card-accent"></div>
            <div>
                <?php echo esc_html($company["name"]) ?>
            </div>
        </div>

        <div class="companies__card-divider"></div>

        <div class="companies__card-section">
            <div class="companies__card-label">
                <?php esc_html_e("Address", 'moloni-on') ?>
            </div>
            <div class="companies__card-text">
                <?php echo esc_html($company["address"]) ?>
            </div>
            <div class="companies__card-text">
                <?php echo esc_html($company["zipCode"]) ?>
            </div>
        </div>

        <div class="companies__card-section">
            <div class="companies__card-label">
                <?php esc_html_e("Vat number", 'moloni-on') ?>
            </div>
            <div class="companies__card-text">
                <?php echo esc_html($company["vat"]) ?>
            </div>
        </div>
    </div>

    <button class="ml-button ml-button--primary w-full"
            onclick="window.location.href = '<?php echo esc_url(Context::getAdminUrl("companyId={$company["companyId"]}")) ?>'">
        <?php esc_html_e('Choose company', 'moloni-on') ?>
    </button>
</div>
