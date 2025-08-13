<?php

use MoloniOn\Context;

if (!defined('ABSPATH')) {
    exit;
}

?>

<section id="moloni" class="moloni">
    <?php include MOLONI_ON_DIR . '/assets/icons/plugin.svg' ?>
    <?php include MOLONI_ON_TEMPLATE_DIR . '/Assets/Fonts.php' ?>

    <?php if (!empty($errorData)): ?>
        <pre style="display: none;" id="curl_error_data">
            <?= print_r($errorData, true) ?>
        </pre>
    <?php endif; ?>

    <div class="login login__wrapper">
        <form class="login-form" method='POST' action='<?= Context::getAdminUrl() ?>'>
            <div class="login__card">
                <div class="login__image">
                    <a href="<?= Context::configs()->get('home_page') ?>" target="_blank">
                        <img src="<?= Context::getImagesPath() ?>logo.svg" width="186px" height="32px" alt="Logo">
                    </a>
                </div>

                <div class="login__title">
                    <?= __("Sign in to your account", 'moloni-on') ?> <span><?= Context::configs()->get('name') ?></span>
                </div>

                <div class="login__error">
                    <?php if (isset($error) && $error): ?>
                        <div class="ml-alert ml-alert--danger-light">
                            <svg>
                                <use xlink:href="#ic_notices_important_warning"></use>
                            </svg>

                            <?= $error ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="login__inputs">
                    <div class="ml-input-text <?= isset($error) && $error ? 'ml-input-text--with-error' : '' ?>">
                        <label for='developer_id'>
                            <?= __('Developer Id', 'moloni-on') ?>
                        </label>
                        <input id="developer_id" type='text' name='developer_id'>
                    </div>

                    <div class="ml-input-text <?= isset($error) && $error ? 'ml-input-text--with-error' : '' ?>">
                        <label for='client_secret'>
                            <?= __('Client Secret', 'moloni-on') ?>
                        </label>
                        <input id="client_secret" type='text' name='client_secret'>
                    </div>
                </div>

                <div class="login__help">
                    <a href="<?= Context::configs()->get('landing_page') ?>" target="_blank">
                        <?= __('Click here for more instructions', 'moloni-on') ?>
                    </a>
                </div>

                <div class="login__button">
                    <button class="ml-button ml-button--primary w-full" id="login_button" type="submit" disabled>
                        <?= __("Login", 'moloni-on') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        jQuery(document).ready(function () {
            Moloni.Login.init();
        });
    </script>
</section>
