<p>
    <?= __('Total results', 'moloni_on') ?>: <?= $data['totalResults'] ?? 0 ?>
</p>

<?php if (isset($data['hasMore']) && $data['hasMore']) : ?>
    <p>
        <?= min($data['currentPercentage'], 100) ?>%
    </p>

    <img src="<?php echo esc_url(includes_url() . 'js/thickbox/loadingAnimation.gif'); ?>"/>

    <p>
        <?= __('Please wait, tool in progress', 'moloni_on') ?>
    </p>
<?php else: ?>
    <p>
        <?= __('Process complete', 'moloni_on') ?>
    </p>
<?php endif; ?>
