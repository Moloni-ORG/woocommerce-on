<?php

if (!defined('ABSPATH')) {
    exit;
}

$company = $company ?? [];
$reasonName = $reasonName ?? '';
$reasonValue = $reasonValue ?? '';
?>

<?php if (isset($company['fiscalZone']['exemption']['reasons'])) : ?>
    <select id="<?php echo esc_attr($reasonName) ?>" name='opt[<?php echo esc_attr($reasonName) ?>]' class='inputOut'>
        <option value='' <?php echo empty($reasonValue) ? 'selected' : '' ?>>
            <?php esc_html_e('Choose an option', 'moloni-on') ?>
        </option>

        <?php foreach ($company['fiscalZone']['exemption']['reasons'] as $reason) : ?>
            <option
                value="<?php echo esc_attr($reason['code']) ?>"
                title="<?php echo esc_attr($reason['name']) ?>"
                <?php echo $reasonValue === $reason['code'] ? ' selected' : '' ?>
            >
                <?php echo esc_html("{$reason['code']} - {$reason['name']}"); ?>
            </option>
        <?php endforeach; ?>
    </select>
<?php else : ?>
    <input id="<?php echo esc_attr($reasonName) ?>"
           name="opt[<?php echo esc_attr($reasonName) ?>]"
           type="text"
           value="<?php echo esc_attr($reasonValue) ?>"
           class="inputOut"
    >
<?php endif; ?>
