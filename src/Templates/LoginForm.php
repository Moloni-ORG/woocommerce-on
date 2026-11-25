<?php

use MoloniOn\Context;

if (!defined('ABSPATH')) {
    exit;
}
?>

<section id="moloni" class="moloni">
    <?php include MOLONI_ON_DIR . '/assets/icons/plugin.svg' ?>

    <div class="login login__wrapper">
        <form method='POST' action='<?php echo esc_url(Context::getAdminUrl()) ?>' class="login-form">
            <?php wp_nonce_field("molonion-form-nonce"); ?>
            <input type="hidden" name="molonion_connect" value="1">

            <div class="login__card">
                <div class="login__image">
                    <a href="<?php echo esc_url(Context::configs()->get('home_page')) ?>" target="_blank">
                        <img src="<?php echo esc_url(Context::getImagesPath()) ?>logo.svg" width="186px" height="32px" alt="Logo">
                    </a>
                </div>

                <div class="login__title">
                    <?php esc_html_e("Sign in to your account", 'moloni-on') ?> <span><?php echo esc_html(Context::configs()->get('name')) ?></span>
                </div>

                <div class="login__error">
                    <?php if (isset($error) && $error): ?>
                        <div class="ml-alert ml-alert--danger-light">
                            <svg>
                                <use xlink:href="#ic_notices_important_warning"></use>
                            </svg>

                            <?php echo wp_kses_post($error); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="login__button">
                    <button class="ml-button ml-button--primary w-full" id="login_button" type="submit">
                        <?php esc_html_e("Connect to Moloni ON", 'moloni-on') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div id="molonion-login-page-anchor"></div>
</section>
