<div id="export-stocks-modal" class="modal" style="display: none">
    <h2>
        <?= __('Export stocks to Moloni' , 'moloni_on') ?>
    </h2>
    <div>
        <p>
            <?= __('This tool will cycle for all your WooCommerce products and will insert manual stock movements in your Moloni account to make sure the stocks are equal in both platforms.', 'moloni_on') ?>
        </p>
        <p>
            <?= __('When the tool is finish, all your Moloni stock will be updated to match the stock on your WooCommerce store.', 'moloni_on') ?>
        </p>
        <p>
            <?= __('This may take a while, so, please keep this window open until the process finishes.', 'moloni_on') ?>
        </p>
        <p>
            <?= __('Are you sure you want to continue?', 'moloni_on') ?>
        </p>
    </div>
    <div>
        <a class="button button-large button-secondary" href="#" rel="modal:close">
            <?= __('Close', 'moloni_on') ?>
        </a>
        <a class="button button-large button-primary">
            <?= __('Start', 'moloni_on') ?>
        </a>
    </div>
</div>
