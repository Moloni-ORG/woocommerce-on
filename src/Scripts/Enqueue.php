<?php

namespace MoloniOn\Scripts;

use MoloniOn\Context;

/**
 * Class Enqueue
 * Add script files to queue
 */
class Enqueue
{
    public static $inlineScripts = [];

    public static $inlineStyles = [];

    private static $version = '3.2';

    /**
     * Define some table params
     * Load scripts and CSS as needed
     */
    public static function adminCore()
    {
        if (wp_doing_ajax()) {
            return;
        }

        wp_register_style('molonion-styles', MOLONI_ON_PLUGIN_URL . Context::getCssPath() . "/css/molonion.min.css", [], self::$version);
        wp_register_script('molonion-scripts', MOLONI_ON_PLUGIN_URL . "assets/js/molonion.min.js", [], self::$version);

        wp_enqueue_style('molonion-styles');
        wp_enqueue_script('molonion-scripts');

        if (!empty(self::$inlineScripts)) {
            $allJs = implode("\n", self::$inlineScripts);

            wp_add_inline_script('molonion-scripts', $allJs);
        }

        if (!empty(self::$inlineStyles)) {
            $allCss = implode("\n", self::$inlineStyles);

            wp_add_inline_style('molonion-styles', $allCss);
        }
    }

    public static function adminCommon() {
        wp_register_script('molonion-scripts-helper', MOLONI_ON_PLUGIN_URL . "assets/js/helpers.js", [], self::$version);
        wp_enqueue_script('molonion-scripts-helper');
    }

    public static function addInlineScript(string $inlineJs) {
        self::$inlineScripts[] = $inlineJs;
    }

    public static function addInlineStyle(string $inlineCss) {
        self::$inlineStyles[] = $inlineCss;
    }
}
