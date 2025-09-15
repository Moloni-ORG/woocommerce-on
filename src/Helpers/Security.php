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

    public static function get_nonce_url(string $url): string
    {
        return wp_nonce_url($url, 'molonion-form-nonce');
    }

    public static function verify_user_can_access(): bool
    {
        return current_user_can('manage_woocommerce');
    }

    public static function verify_ajax_request_or_die(): void
    {
        if (!check_admin_referer('molonion-ajax-nonce', '_wpnonce')) {
            wp_send_json_error('Invalid referer');
            wp_die();
        }

        $nonce = sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'] ?? ''));

        if (empty($nonce)) {
            wp_send_json_error('Invalid nonce');
            wp_die();
        }

        if (!wp_verify_nonce($nonce, 'molonion-ajax-nonce')) {
            wp_send_json_error('Invalid security token');
            wp_die();
        }
    }

    public static function verify_request_or_die()
    {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            self::verify_post_request_or_die();
        } elseif ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
            self::verify_get_request_or_die();
        }
    }

    public static function verify_post_request_or_die(): void
    {
        // All post request must have nonce

        $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce'] ?? ''));

        if (!wp_verify_nonce($nonce, 'molonion-form-nonce')) {
            wp_die('Security check failed');
        }
    }

    public static function verify_get_request_or_die(): void
    {
        $action = sanitize_text_field($_REQUEST['action'] ?? '');

        // No data is changed with GET requests, so we only check if there's an action
        if (empty($action)) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce'] ?? ''));

        if (!wp_verify_nonce($nonce, 'molonion-form-nonce')) {
            wp_die('Security check failed');
        }
    }
}
