<?php

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name' => 'Moloni On',
    'name_translated' => function_exists('__') ? __('Moloni On', 'moloni-on') : 'Moloni On',
    'database_infix' => 'on',
    'rest_api' => 'molonion/v1',
    'page_name' => 'molonion',
    'ac_url' => 'https://ac.molonion.pt/',
    'api_url' => 'https://api.molonion.pt/v1',
    'media_api_url' => 'https://mediaapi.moloni.org',
    'landing_page' => 'https://woocommerce.molonion.pt/',
    'plans_page' => 'https://www.molonion.pt/plansandprices',
    'help_page' => 'https://www.molonion.pt/faqs/subcategory/woocommerce',
    'home_page' => 'https://www.molonion.pt/',
    'register_page' => 'https://account.molonion.pt/signup',

    // Builder configuration
    'folder_name' => 'molonion',
    'zip_name' => 'moloni-on',
    'main_file_name' => 'moloni-on',
];
