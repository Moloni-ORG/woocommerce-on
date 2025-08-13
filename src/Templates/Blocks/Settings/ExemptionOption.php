<?php

if (!defined('ABSPATH')) {
    exit;
}

$company = $company ?? [];
$reasonName = $reasonName ?? '';
$reasonValue = $reasonValue ?? '';
?>

<?php if (isset($company['fiscalZone']['exemption']['reasons'])) : ?>
    <select id="<?php echo $reasonName ?>" name='opt[<?php echo $reasonName ?>]' class='inputOut'>
        <option value='' <?php echo empty($reasonValue) ? 'selected' : '' ?>>
            <?php esc_html_e('Choose an option', 'moloni-on') ?>
        </option>

        <?php foreach ($company['fiscalZone']['exemption']['reasons'] as $reason) : ?>
            <option
                value="<?php echo esc_html($reason['code']) ?>"
                title="<?php echo esc_html($reason['name']) ?>"
                <?php echo $reasonValue === $reason['code'] ? ' selected' : '' ?>
            >
                <?php echo esc_html("{$reason['code']} - {$reason['name']}"); ?>
            </option>
        <?php endforeach; ?>
    </select>
<?php else : ?>
    <input id="<?php echo $reasonName ?>"
           name="opt[<?php echo $reasonName ?>]"
           type="text"
           value="<?php echo $reasonValue ?>"
           class="inputOut"
    >
<?php endif; ?>
