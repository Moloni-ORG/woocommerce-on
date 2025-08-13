<div id="action-modal" class="modal" style="display: none">
    <h2 id="action-modal-title-start" style="display: none;">
        <?= __('Action in progress', 'moloni_on') ?>
    </h2>

    <h2 id="action-modal-title-end" style="display: none;">
        <?= __('Process concluded', 'moloni_on') ?>
    </h2>

    <div id="action-modal-content" style="display: none;"></div>

    <div id="action-modal-spinner" style="display: none;">
        <p>
            <?= __('We are processing your request.', 'moloni_on') ?>
        </p>

        <img src="<?php echo esc_url( includes_url() . 'js/thickbox/loadingAnimation.gif' ); ?>" />

        <p>
            <?= __('Please wait until the process finishes!', 'moloni_on') ?>
        </p>
    </div>

    <div id="action-modal-error" style="display: none;">
        <p>
            <?= __('Something went wrong!', 'moloni_on') ?>
        </p>
        <p>
            <?= __('Please check logs for more information.', 'moloni_on') ?>
        </p>
    </div>

    <div class="mt-4">
        <a class="button button-large button-secondary" href="#" rel="modal:close" style="display: none;">
            <?= __('Close', 'moloni_on') ?>
        </a>
    </div>
</div>
