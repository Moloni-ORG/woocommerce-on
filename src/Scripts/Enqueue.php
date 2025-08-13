<?php

namespace MoloniOn\Scripts;

use MoloniOn\Context;

/**
 * Class Enqueue
 * Add script files to queue
 *
 * @package Moloni\Scripts
 */
class Enqueue
{
    /**
     * Define some table params
     * Load scripts and CSS as needed
     */
    public static function defines()
    {
        if (wp_doing_ajax() || !isset($_REQUEST['page']) || sanitize_text_field($_REQUEST['page']) !== Context::getPageName()) {
            return;
        }

        $ver = '3.0';

        wp_enqueue_style('moloni-styles', plugins_url(Context::getCssPath() . "/css/molonion.min.css", MOLONI_ON_PLUGIN_FILE), [], $ver);
        wp_enqueue_script('moloni-scripts', plugins_url("assets/js/molonion.min.js", MOLONI_ON_PLUGIN_FILE), [], $ver);
    }
}
