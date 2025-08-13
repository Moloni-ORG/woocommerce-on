<p>
    <?php echo __('Total results', 'moloni-on') ?>: <?php echo $data['totalResults'] ?? 0 ?>
</p>

<?php if (isset($data['hasMore']) && $data['hasMore']) : ?>
    <p>
        <?php echo min($data['currentPercentage'], 100) ?>%
    </p>

    <img src="<?php echo esc_url(includes_url() . 'js/thickbox/loadingAnimation.gif'); ?>"/>

    <p>
        <?php echo __('Please wait, tool in progress', 'moloni-on') ?>
    </p>
<?php else: ?>
    <p>
        <?php echo __('Process complete', 'moloni-on') ?>
    </p>
<?php endif; ?>
