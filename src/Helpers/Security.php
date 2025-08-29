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
}
