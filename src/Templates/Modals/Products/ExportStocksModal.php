<div id="export-stocks-modal" class="modal" style="display: none">
    <h2>
        <?php echo __('Export stocks to Moloni' , 'moloni-on') ?>
    </h2>
    <div>
        <p>
            <?php echo __('This tool will cycle for all your WooCommerce products and will insert manual stock movements in your Moloni account to make sure the stocks are equal in both platforms.', 'moloni-on') ?>
        </p>
        <p>
            <?php echo __('When the tool is finish, all your Moloni stock will be updated to match the stock on your WooCommerce store.', 'moloni-on') ?>
        </p>
        <p>
            <?php echo __('This may take a while, so, please keep this window open until the process finishes.', 'moloni-on') ?>
        </p>
        <p>
            <?php echo __('Are you sure you want to continue?', 'moloni-on') ?>
        </p>
    </div>
    <div>
        <a class="button button-large button-secondary" href="#" rel="modal:close">
            <?php echo __('Close', 'moloni-on') ?>
        </a>
        <a class="button button-large button-primary">
            <?php echo __('Start', 'moloni-on') ?>
        </a>
    </div>
</div>
