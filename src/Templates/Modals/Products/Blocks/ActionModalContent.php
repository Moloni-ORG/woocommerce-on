<p>
    <?php esc_html_e('Total results', 'moloni-on') ?>: <?php echo esc_html($data['totalResults'] ?? 0) ?>
</p>

<?php if (isset($data['hasMore']) && $data['hasMore']) : ?>
    <p>
        <?php echo esc_html(min($data['currentPercentage'], 100)) ?>%
    </p>

    <img src="<?php echo esc_url(includes_url() . 'js/thickbox/loadingAnimation.gif'); ?>"/>

    <p>
        <?php esc_html_e('Please wait, tool in progress', 'moloni-on') ?>
    </p>
<?php else: ?>
    <p>
        <?php esc_html_e('Process complete', 'moloni-on') ?>
    </p>
<?php endif; ?>
