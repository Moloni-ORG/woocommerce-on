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
                <?php echo $company["name"] ?>
            </div>
        </div>

        <div class="companies__card-divider"></div>

        <div class="companies__card-section">
            <div class="companies__card-label">
                <?php echo __("Address", 'moloni-on') ?>
            </div>
            <div class="companies__card-text">
                <?php echo $company["address"] ?>
            </div>
            <div class="companies__card-text">
                <?php echo $company["zipCode"] ?>
            </div>
        </div>

        <div class="companies__card-section">
            <div class="companies__card-label">
                <?php echo __("Vat number", 'moloni-on') ?>
            </div>
            <div class="companies__card-text">
                <?php echo $company["vat"] ?>
            </div>
        </div>
    </div>

    <button class="ml-button ml-button--primary w-full"
            onclick="window.location.href = '<?php echo Context::getAdminUrl("companyId={$company["companyId"]}") ?>'">
        <?php echo __('Choose company', 'moloni-on') ?>
    </button>
</div>
