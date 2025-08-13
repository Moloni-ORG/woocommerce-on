<div id="action-modal" class="modal" style="display: none">
    <h2 id="action-modal-title-start" style="display: none;">
        <?php echo __('Action in progress', 'moloni-on') ?>
    </h2>

    <h2 id="action-modal-title-end" style="display: none;">
        <?php echo __('Process concluded', 'moloni-on') ?>
    </h2>

    <div id="action-modal-content" style="display: none;"></div>

    <div id="action-modal-spinner" style="display: none;">
        <p>
            <?php echo __('We are processing your request.', 'moloni-on') ?>
        </p>

        <img src="<?php echo esc_url( includes_url() . 'js/thickbox/loadingAnimation.gif' ); ?>" />

        <p>
            <?php echo __('Please wait until the process finishes!', 'moloni-on') ?>
        </p>
    </div>

    <div id="action-modal-error" style="display: none;">
        <p>
            <?php echo __('Something went wrong!', 'moloni-on') ?>
        </p>
        <p>
            <?php echo __('Please check logs for more information.', 'moloni-on') ?>
        </p>
    </div>

    <div class="mt-4">
        <a class="button button-large button-secondary" href="#" rel="modal:close" style="display: none;">
            <?php echo __('Close', 'moloni-on') ?>
        </a>
    </div>
</div>
