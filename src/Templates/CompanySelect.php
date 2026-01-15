<?php

use MoloniOn\API\Companies;
use MoloniOn\Context\Company;
use MoloniOn\Exceptions\APIExeption;

if (!defined('ABSPATH')) {
    exit;
}

$companies = [];

try {
    $query = Companies::queryCompanies();

    foreach ($query['data']['companies']['data'] as $companyObject) {
        $companyObject = new Company($companyObject);

        if (!$companyObject->getCompanyId()) {
            continue;
        }

        if (!$companyObject->get('isConfirmed')) {
            continue;
        }

        if (!$companyObject->hasApiClient()) {
            continue;
        }

        $companies[] = $companyObject->getAll();
    }
} catch (APIExeption $e) {
    $e->showError();
}
?>

<section id="moloni" class="moloni">
    <div class="companies">
        <?php if (!empty($companies) && is_array($companies)) : ?>
            <div class="companies__title">
                <?php esc_html_e("Select the company you want to connect with WooCommerce", 'moloni-on') ?>
            </div>

            <div class="companies__list">
                <?php
                foreach ($companies as $company) {
                    include MOLONI_ON_TEMPLATE_DIR . 'Blocks/CompanySelect/CompanyCard.php';
                }
                ?>
            </div>
        <?php else : ?>
            <?php include MOLONI_ON_TEMPLATE_DIR . 'Blocks/CompanySelect/NoCompanies.php'; ?>
        <?php endif; ?>
    </div>
</section>
