<?php

if (!defined('ABSPATH')) {
    exit;
}

return [
    'name' => 'Moloni',
    'name_translated' => function_exists('__') ? __('Moloni Spain', 'moloni-on') : 'Moloni Spain',
    'database_infix' => 'es',
    'rest_api' => 'moloni/v1',
    'page_name' => 'molonies',
    'ac_url' => 'https://ac.moloni.es/',
    'api_url' => 'https://api.moloni.es/v1',
    'media_api_url' => 'https://mediaapi.moloni.org',
    'landing_page' => 'https://woocommerce.moloni.es/',
    'plans_page' => 'https://www.moloni.es/plansandprices',
    'help_page' => 'https://www.moloni.es/faqs/subcategory/wordpress-woocommerce',
    'home_page' => 'https://www.moloni.es/',
    'register_page' => 'https://ac.moloni.es/signup',

    // Builder configuration
    'folder_name' => 'molonies',
    'zip_name' => 'moloni-es',
    'main_file_name' => 'moloni_es',
];
