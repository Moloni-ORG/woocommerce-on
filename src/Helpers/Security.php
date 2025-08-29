<?php

namespace MoloniOn\Helpers;

class Security
{
    public static function wp_kses_post_with_inputs($content): string
    {
        $allowed_html = wp_kses_allowed_html('post');

        $allowed_html['input'] = array(
            'type' => true,
            'name' => true,
            'value' => true,
            'class' => true,
            'id' => true,
            'placeholder' => true,
            'checked' => true,
            'disabled' => true,
            'readonly' => true,
        );

        return wp_kses($content, $allowed_html);
    }

    public static function verify_ajax_request_or_die(): void
    {
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce(wp_unslash($_REQUEST['nonce']), 'molonion-ajax-nonce')) {
            wp_send_json_error('Invalid security token');
            wp_die();
        }
    }

    public static function verify_post_request_or_die(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'molonion-form-nonce')) {
            wp_die('Security check failed');
        }
    }
}
