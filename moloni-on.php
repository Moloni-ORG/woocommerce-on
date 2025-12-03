<?php
/**
 *   Plugin Name:  Moloni On
 *   Description:  Simple invoicing integration with Moloni On.
 *   Version:      #VERSION#
 *   Tested up to: 6.9
 *   WC tested up to: 10.3.6
 *
 *   Author:       Moloni
 *   Author URI:   https://moloni.pt
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *   Text Domain:  moloni-on
 *   Domain Path:  /languages
 */

namespace MoloniOn;

use MoloniOn\Scripts\Enqueue;
use MoloniOn\Activators\Remove;
use MoloniOn\Activators\Install;

if (!defined('ABSPATH')) {
    exit;
}

$composer_autoloader = __DIR__ . '/vendor/autoload.php';

if (is_readable($composer_autoloader)) {
    require $composer_autoloader;
}

if (!defined('MOLONI_ON_PLUGIN_FILE')) {
    define('MOLONI_ON_PLUGIN_FILE', __FILE__);
}

if (!defined('MOLONI_ON_DIR')) {
    define('MOLONI_ON_DIR', __DIR__);
}

if (!defined('MOLONI_ON_TEMPLATE_DIR')) {
    define('MOLONI_ON_TEMPLATE_DIR', __DIR__ . '/src/Templates/');
}

if (!defined('MOLONI_ON_LANGUAGES_DIR')) {
    define('MOLONI_ON_LANGUAGES_DIR', basename(dirname(__FILE__)) . '/languages/');
}

if (!defined('MOLONI_ON_PLUGIN_URL')) {
    define('MOLONI_ON_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('MOLONI_ON_IMAGES_URL')) {
    define('MOLONI_ON_IMAGES_URL', plugin_dir_url(__FILE__) . 'images/');
}

register_activation_hook(__FILE__, [Install::class, 'run']);
register_deactivation_hook(__FILE__, [Remove::class, 'run']);

add_action('wp_initialize_site', [Install::class, 'initializeSite'], 200);
add_action('wp_uninitialize_site', [Remove::class, 'uninitializeSite']);
add_action('plugins_loaded', Start::class);

add_action('admin_enqueue_scripts', [Enqueue::class, 'adminCommon']);
add_action('admin_print_footer_scripts-toplevel_page_molonion', [Enqueue::class, 'adminCore']);

function Start(): Plugin
{
    //start the plugin
    return new Plugin();
}
