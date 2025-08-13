<div id="import-stocks-modal" class="modal" style="display: none">
    <h2>
        <?= __('Import stocks from Moloni' , 'moloni-on') ?>
    </h2>
    <div>
        <p>
            <?= __('This tool will fetch all your Moloni products and synchronize your WooCommerce stocks with the ones on your Moloni account.', 'moloni-on') ?>
        </p>
        <p>
            <?= __('This may take a while, so, please keep this window open until the process finishes.', 'moloni-on') ?>
        </p>
        <p>
            <?= __('Are you sure you want to continue?', 'moloni-on') ?>
        </p>
    </div>
    <div>
        <a class="button button-large button-secondary" href="#" rel="modal:close">
            <?= __('Close', 'moloni-on') ?>
        </a>
        <a class="button button-large button-primary">
            <?= __('Start', 'moloni-on') ?>
        </a>
    </div>
</div>
