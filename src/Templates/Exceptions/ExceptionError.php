<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div>
    <div id="message" class="updated error is-dismissible">
        <p>
            <?php echo wp_kses_post($message ?? ''); ?>
        </p>

        <a onclick="molonion_show_errors();" style="cursor: pointer;">
            <p><?php esc_html_e("Click here for more information",'moloni-on') ?></p>
        </a>

        <div class="MoloniConsoleLogError" style="display: none;">
            <b><?php esc_html_e("Data",'moloni-on') ?>: </b>

            <br>

            <pre>
                <?php
                /** @var array $data */
                echo wp_json_encode($data ?? [], JSON_PRETTY_PRINT)
                ?>
            </pre>
        </div>
    </div>
</div>
