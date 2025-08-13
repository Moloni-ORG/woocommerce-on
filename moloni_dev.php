<?php
/**
 *
 *   Plugin Name:  Moloni DEV
 *   Description:  Development plugin for Moloni Spain and MoloniOn.
 *   Version:      0.0.01
 *   Tested up to: 6.8
 *   WC tested up to: 9.8.5
 *
 *   Author:       Moloni
 *   Author URI:   https://moloni.pt
 *   License:      GPL2
 *   License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 *   Text Domain:  moloni-on
 *   Domain Path:  /languages
 */

namespace MoloniOn;

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

register_activation_hook(__FILE__, '\MoloniOn\Activators\Install::run');
register_deactivation_hook(__FILE__, '\MoloniOn\Activators\Remove::run');

add_action('wp_initialize_site', '\MoloniOn\Activators\Install::initializeSite', 200);
add_action('wp_uninitialize_site', '\MoloniOn\Activators\Remove::uninitializeSite');
add_action('plugins_loaded', Start::class);
add_action('admin_enqueue_scripts', '\MoloniOn\Scripts\Enqueue::defines');

function Start(): Plugin
{
    //start the plugin
    return new Plugin();
}
