const { __ } = wp.i18n;

jQuery(document).ready(function () {
    if (jQuery('#molonion-login-page-anchor').length) {
        Moloni.Login.init();
    }

    if (jQuery('#molonion-automations-page-anchor').length) {
        Moloni.Automations.init();
    }

    if (jQuery('#molonion-logs-page-anchor').length) {
        Moloni.Logs.init();
    }

    if (jQuery('#molonion-moloni-products-page-anchor').length) {
        Moloni.MoloniProducts.init({
            "create_action": __('product creation processes.', 'moloni-on'),
            "update_action": __('product update processes.', 'moloni-on'),
            "stock_action": __('stock update processes.', 'moloni-on'),
            "processing_product": __('Processing product', 'moloni-on'),
            "successfully_processed": __('Successfully processed', 'moloni-on'),
            "error_in_the_process": __('Error in the process', 'moloni-on'),
            "click_to_see": __('Click to see', 'moloni-on'),
            "completed": __('Completed', 'moloni-on'),
        });
    }

    if (jQuery('#molonion-pending-orders-page-anchor').length) {
        Moloni.OrdersBulkAction({
            startingProcess: __('Starting process...', 'moloni-on'),
            noOrdersSelected: __('No orders selected to process', 'moloni-on'),
            creatingDocument: __('Creating document', 'moloni-on'),
            discardingOrder: __('Discarding order', 'moloni-on'),
            createdDocuments:  __('Documents created:', 'moloni-on'),
            documentsWithErrors:  __('Documents with errors:', 'moloni-on'),
            discardedOrders: __('Orders discarded:', 'moloni-on'),
            ordersWithErrors:  __('Orders with errors:', 'moloni-on'),
            close:  __('Close', 'moloni-on'),
        });
    }

    if (jQuery('#molonion-settings-page-anchor').length) {
        Moloni.Settings.init({
            example: __('Example', 'moloni-on'),
        });
    }

    if (jQuery('#molonion-tools-page-anchor').length) {
        Moloni.Tools.init();
    }

    if (jQuery('#molonion-wc-products-page-anchor').length) {
        Moloni.WcProducts.init({
            "create_action": __('product creation processes.', 'moloni-on'),
            "update_action": __('product update processes.', 'moloni-on'),
            "stock_action":__('stock update processes.', 'moloni-on'),
            "processing_product": __('Processing product', 'moloni-on'),
            "successfully_processed": __('Successfully processed', 'moloni-on'),
            "error_in_the_process": __('Error in the process', 'moloni-on'),
            "click_to_see": __('Click to see', 'moloni-on'),
            "completed": __('Completed', 'moloni-on'),
        });
    }
});
