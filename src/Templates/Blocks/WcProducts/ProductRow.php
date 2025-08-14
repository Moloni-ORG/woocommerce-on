<?php

if (!defined('ABSPATH')) {
    exit;
}

$row = $row ?? [];
?>

<tr class="product__row"
    data-wc-id="<?php echo esc_attr($row['wc_product_id'] ?? 0) ?>"
    data-moloni-id="<?php echo esc_attr($row['moloni_product_id'] ?? 0) ?>"
>
    <td class="product__row-name">
        <?php echo $row['wc_product_object']->get_parent_id() ? ' &rdsh; ' : '' ?>
        <?php echo esc_html($row['wc_product_object']->get_name()) ?>
    </td>
    <td class="product__row-reference">
        <?php echo esc_html($row['wc_product_object']->get_sku()) ?>
    </td>
    <td>
        <?php
        switch ($row['wc_product_object']->get_type()) {
            case 'external':
                esc_html_e('External', 'moloni-on');

                break;
            case 'grouped':
                esc_html_e('Grouped', 'moloni-on');

                break;
            case 'simple':
                esc_html_e('Simple', 'moloni-on');

                break;
            case 'variable':
                esc_html_e('Variable', 'moloni-on');

                break;
            case 'variation':
                esc_html_e('Variation', 'moloni-on');

                break;
            default:
                esc_html_e('Others', 'moloni-on');

                break;
        }
        ?>
    </td>
    <td>
        <?php
        if (empty($row['tool_alert_message'])) {
            echo '---';
        } elseif (is_string($row['tool_alert_message'])) {
            echo esc_html($row['tool_alert_message']);
        } elseif (is_array($row['tool_alert_message'])) {
            foreach ($row['tool_alert_message'] as $message) {
                echo "<p>" . esc_html($message) . "</p>";
            }
        }
        ?>
    </td>
    <td>
        <?php if (!empty($row['wc_product_link']) || !empty($row['moloni_product_link'])) : ?>
            <div class="dropdown">
                <button type="button" class="dropdown--manager button button-primary">
                    <?php esc_html_e('Open', 'moloni-on') ?> &#8628;
                </button>
                <div class="dropdown__content">
                    <ul>
                        <?php if (!empty($row['wc_product_link'])) : ?>
                            <li>
                                <a target="_blank" href="<?php echo esc_url($row['wc_product_link']) ?>">
                                    <?php esc_html_e('Open in WooCommerce', 'moloni-on') ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php if (!empty($row['moloni_product_link'])) : ?>
                            <li>
                                <a target="_blank" href="<?php echo esc_url($row['moloni_product_link']) ?>">
                                    <?php esc_html_e('Open in Moloni', 'moloni-on') ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </td>
    <td class="text-center">
        <input type="checkbox" class="checkbox_create_product m-0-important"
            <?php echo empty($row['tool_show_create_button']) ? 'disabled' : '' ?>
        >
    </td>
    <td class="text-center">
        <input type="checkbox" class="checkbox_update_stock_product m-0-important"
            <?php echo empty($row['tool_show_update_stock_button']) ? 'disabled' : '' ?>
        >
    </td>
</tr>
