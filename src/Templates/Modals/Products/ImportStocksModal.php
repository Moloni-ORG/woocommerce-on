<div id="import-stocks-modal" class="modal" style="display: none">
    <h2>
        <?= __('Import stocks from Moloni' , 'moloni_on') ?>
    </h2>
    <div>
        <p>
            <?= __('This tool will fetch all your Moloni products and synchronize your WooCommerce stocks with the ones on your Moloni account.', 'moloni_on') ?>
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
