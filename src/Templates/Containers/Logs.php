<?php

if (!defined('ABSPATH')) {
    exit;
}

use MoloniOn\Context;
use MoloniOn\Enums\LogLevel;
use MoloniOn\Models\Logs;

$logs = Logs::getAllAvailable();

$logsContext = [];
?>

<div class="wrap">
    <h3><?php echo __('Here you can check all plugin logs', 'moloni-on') ?></h3>

    <div class="tablenav top">
        <div class="tablenav-pages">
            <?php echo Logs::getPagination() ?>
        </div>
    </div>

    <form method="post" action='<?php echo Context::getAdminUrl("tab=logs") ?>'>
        <table class='wp-list-table widefat striped posts'>
            <thead>
            <tr>
                <th><a><?php echo __('Date', 'moloni-on') ?></a></th>
                <th><a><?php echo __('Level', 'moloni-on') ?></a></th>
                <th><a><?php echo __('Message', 'moloni-on') ?></a></th>
                <th><a><?php echo __('Context', 'moloni-on') ?></a></th>
            </tr>
            <tr>
                <th>
                    <input
                        type="date"
                        class="inputOut ml-0"
                        name="filter_date"
                        value="<?php echo esc_html($_GET['filter_date'] ?? $_POST['filter_date'] ?? '') ?>"
                    >
                </th>
                <th>
                    <?php $options = LogLevel::getForRender() ?>

                    <select name="filter_level">
                        <?php $filterLevel = esc_html($_GET['filter_level'] ?? $_POST['filter_level'] ?? '') ?>

                        <option value='' selected><?php echo
                            __('Choose an option', 'moloni-on') ?>
                        </option>

                        <?php foreach ($options as $option) : ?>
                            <option
                                value='<?php echo $option['value'] ?>'
                                <?php echo $filterLevel === $option['value'] ? 'selected' : '' ?>
                            >
                            <?php echo $option['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </th>
                <th>
                    <input
                        type="text"
                        class="inputOut ml-0"
                        name="filter_message"
                        value="<?php echo esc_html($_GET['filter_message'] ?? $_POST['filter_message'] ?? '') ?>"
                    >
                </th>
                <th>
                    <button type="submit" name="submit" id="submit" class="button button-primary">
                        <?php echo __('Search', 'moloni-on') ?>
                    </button>
                </th>
            </tr>
            </thead>

            <?php if (!empty($logs) && is_array($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td>
                            <?php echo gmdate("d-m-Y H:i:s", strtotime($log['created_at'])) ?>
                        </td>
                        <td>
                            <?php
                            $logLevel = $log['log_level'] ?? '';
                            ?>

                            <div class="chip <?php echo LogLevel::getClass($logLevel) ?>">
                                <?php echo LogLevel::getTranslation($logLevel) ?>
                            </div>
                        </td>
                        <td>
                            <?php echo $log['message'] ?>
                        </td>
                        <td>
                            <?php $logContext = htmlspecialchars($log['context']) ?>

                            <button type="button" class="button action"
                                    onclick="Moloni.Logs.openContextDialog(<?php echo $logContext ?>)">
                                <?php echo __("See", 'moloni-on') ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">
                        <?php echo __('No records found!', 'moloni-on') ?>
                    </td>
                </tr>
            <?php endif; ?>

            <tfoot>
            <tr>
                <th><a><?php echo __('Date', 'moloni-on') ?></a></th>
                <th><a><?php echo __('Level', 'moloni-on') ?></a></th>
                <th><a><?php echo __('Message', 'moloni-on') ?></a></th>
                <th><a><?php echo __('Context', 'moloni-on') ?></a></th>
            </tr>
            </tfoot>
        </table>
    </form>

    <div class="tablenav bottom">
        <div class="alignleft actions">
            <a class="button button-primary" href='<?php echo Context::getAdminUrl('tab=logs&action=remLogs') ?>'>
                <?php echo __('Delete records older than 1 week', 'moloni-on') ?>
            </a>
        </div>

        <div class="tablenav-pages">
            <?php echo Logs::getPagination() ?>
        </div>
    </div>
</div>

<?php include MOLONI_ON_TEMPLATE_DIR . 'Modals/Logs/LogsContextModal.php'; ?>

<script>
    jQuery(document).ready(function () {
        Moloni.Logs.init();
    });
</script>
