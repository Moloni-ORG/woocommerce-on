<?php
if (!defined('ABSPATH')) {
    exit;
}

use MoloniOn\API\Companies;
use MoloniOn\API\Countries;
use MoloniOn\API\DocumentSets;
use MoloniOn\API\MaturityDates;
use MoloniOn\API\MeasurementUnits;
use MoloniOn\API\PaymentMethods;
use MoloniOn\API\Warehouses;
use MoloniOn\Context;
use MoloniOn\Enums\Boolean;
use MoloniOn\Enums\DocumentStatus;
use MoloniOn\Enums\DocumentTypes;
use MoloniOn\Enums\Languages;
use MoloniOn\Exceptions\APIExeption;
use MoloniOn\Helpers\External;
use MoloniOn\Models\Settings;
use MoloniOn\Tools;

try {
    $company = Companies::queryCompany()['data']['company']['data'] ?? [];
    $documentSets = DocumentSets::queryDocumentSets();
    $paymentMethods = PaymentMethods::queryPaymentMethods();
    $maturityDates = MaturityDates::queryMaturityDates();
    $warehouses = Warehouses::queryWarehouses();
    $measurementUnits = MeasurementUnits::queryMeasurementUnits();

    $countries = Countries::queryCountries([
        'options' => [
            'defaultLanguageId' => Languages::EN
        ]
    ]);
} catch (APIExeption $e) {
    $e->showError();
    return;
}
?>

<form method='POST' action='<?php echo Context::getAdminUrl(("tab=settings")) ?>' id='formOpcoes'>
    <input type='hidden' value='saveSettings' name='action'>
    <div>
        <!-- Documents -->
        <h2 class="title">
            <?php esc_html_e('Document', 'moloni-on') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>
            <!-- Slug -->
            <tr>
                <th>
                    <label for="company_slug">
                        <?php esc_html_e('Company slug', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <input
                            id="company_slug"
                            name="opt[company_slug]"
                            type="text"
                            value="<?php echo $company['slug'] ?>"
                            readonly
                            style="width: 330px;"
                    >
                </td>
            </tr>

            <!-- Document type -->
            <tr>
                <th>
                    <label for="document_type">
                        <?php esc_html_e('Document type', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <select id="document_type" name='opt[document_type]' class='inputOut'>
                        <?php
                        $documentType = '';

                        if (defined('DOCUMENT_TYPE') && !empty(DOCUMENT_TYPE)) {
                            $documentType = DOCUMENT_TYPE;
                        }
                        ?>

                        <?php foreach (DocumentTypes::getForRender() as $id => $name) : ?>
                            <option value='<?php echo $id ?>' <?php echo ($documentType === $id ? 'selected' : '') ?>>
                                <?php echo $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Required', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <!-- Document status -->
            <tr>
                <th>
                    <label for="document_status">
                        <?php esc_html_e('Document status', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <select id="document_status" name='opt[document_status]' class='inputOut'>
                        <?php
                        $documentStatus = 0;

                        if (defined('DOCUMENT_STATUS') && !empty(DOCUMENT_STATUS)) {
                            $documentStatus = (int)DOCUMENT_STATUS;
                        }
                        ?>

                        <option value='0' <?php echo ($documentStatus === DocumentStatus::DRAFT ? 'selected' : '') ?>>
                            <?php esc_html_e('Draft', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo ($documentStatus === DocumentStatus::CLOSED ? 'selected' : '') ?>>
                            <?php esc_html_e('Closed', 'moloni-on') ?>
                        </option>
                    </select>

                    <p class='description'>
                        <?php echo esc_attr__('Required', 'moloni-on') . ' ' . esc_attr__('(Invoice + Receipt cannot be created in draft)', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <!-- Bill of lading -->
            <tr id="create_bill_of_lading_line">
                <th>
                    <label for="create_bill_of_lading">
                        <?php esc_html_e('Create bill of lading', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php
                    $createBillOfLading = 0;

                    if (defined('CREATE_BILL_OF_LADING')) {
                        $createBillOfLading = (int)CREATE_BILL_OF_LADING;
                    }
                    ?>

                    <select id="create_bill_of_lading" name='opt[create_bill_of_lading]' class='inputOut'>
                        <option value='0' <?php echo ($createBillOfLading === 0 ? 'selected' : '') ?>>
                            <?php esc_html_e('No', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo ($createBillOfLading === 1 ? 'selected' : '') ?>>
                            <?php esc_html_e('Yes', 'moloni-on') ?>
                        </option>
                    </select>

                    <p class='description'>
                        <?php esc_html_e('Choose if you want to create a Bill of Lading associated with the main document', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <!-- Document set -->
            <tr>
                <th>
                    <label for="document_set_id">
                        <?php esc_html_e('Document set', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <select id="document_set_id" name='opt[document_set_id]' class='inputOut'>
                        <?php
                        $selectedDocumentSetId = defined('DOCUMENT_SET_ID') ? (int)DOCUMENT_SET_ID : null;

                        foreach ($documentSets as $documentSet) :
                            $isSelected = $selectedDocumentSetId === $documentSet['documentSetId'] ? 'selected' : '';
                            ?>

                            <option value='<?php echo $documentSet['documentSetId'] ?>' <?php echo $isSelected ?>>
                                <?php echo $documentSet['name'] ?>
                            </option>

                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Required', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <!-- Shipping info -->
            <tr>
                <th>
                    <label for="shipping_info">
                        <?php esc_html_e('Shipping info', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <select id="shipping_info" name='opt[shipping_info]' class='inputOut'>
                        <?php $shippingInfo = defined('SHIPPING_INFO') ? (int)SHIPPING_INFO : 0; ?>

                        <option value='0' <?php echo $shippingInfo === Boolean::NO ? 'selected' : '' ?>>
                            <?php esc_html_e('No', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo $shippingInfo === Boolean::YES ? 'selected' : '' ?>>
                            <?php esc_html_e('Yes', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Put shipping info on documents', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <!-- Load address -->
            <tr id="load_address_line" style="display: none;">
                <th>
                    <label for="load_address">
                        <?php esc_html_e('Load address', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <select id="load_address" name='opt[load_address]' class='inputOut'>
                        <?php $activeLoadAddress = defined('LOAD_ADDRESS') ? (int)LOAD_ADDRESS : 0; ?>

                        <option value='0' <?php echo ($activeLoadAddress === 0 ? 'selected' : '') ?>>
                            <?php esc_html_e('Company address', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo ($activeLoadAddress === 1 ? 'selected' : '') ?>>
                            <?php esc_html_e('Custom', 'moloni-on') ?>
                        </option>
                    </select>

                    <div class="custom-address__wrapper" id="load_address_custom_line">
                        <div class="custom-address__line">
                            <?php $customAddress = defined('LOAD_ADDRESS_CUSTOM_ADDRESS') ? LOAD_ADDRESS_CUSTOM_ADDRESS : ''; ?>

                            <input name="opt[load_address_custom_address]"
                                   id="load_address_custom_address"
                                   value="<?php echo $customAddress ?>"
                                   placeholder="Morada"
                                   type="text"
                                   class="inputOut"
                            >
                        </div>

                        <div class="custom-address__line">
                            <?php
                            $customCode = defined('LOAD_ADDRESS_CUSTOM_CODE') ? LOAD_ADDRESS_CUSTOM_CODE : '';
                            $customCity = defined('LOAD_ADDRESS_CUSTOM_CITY') ? LOAD_ADDRESS_CUSTOM_CITY : '';
                            ?>

                            <input name="opt[load_address_custom_code]"
                                   id="load_address_custom_code"
                                   value="<?php echo $customCode ?>"
                                   placeholder="Código Postal"
                                   type="text"
                                   class="inputOut inputOut--sm"
                            >
                            <input name="opt[load_address_custom_city]"
                                   id="load_address_custom_city"
                                   value="<?php echo $customCity ?>"
                                   placeholder="Localidade"
                                   type="text"
                                   class="inputOut inputOut--sm"
                            >
                        </div>
                        <div class="custom-address__line">
                            <select id="load_address_custom_country" name="opt[load_address_custom_country]" class="inputOut inputOut--sm">
                                <?php $activeCountry = defined('LOAD_ADDRESS_CUSTOM_COUNTRY') ? (int)LOAD_ADDRESS_CUSTOM_COUNTRY : 0; ?>

                                <option value='0' <?php echo ($activeCountry === 0 ? 'selected' : '') ?>><?php echo
                                    esc_attr_e('Choose an option', 'moloni-on') ?>
                                </option>

                                <?php foreach ($countries['data']['countries']['data'] as $country) : ?>
                                    <option value='<?php echo $country['countryId'] ?>' <?php echo $activeCountry === (int)$country['countryId'] ? 'selected' : '' ?>>
                                        <?php echo $country['title'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <p class='description'>
                        <?php esc_html_e('Load address used in shipping informations', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <!-- Send e-mail -->
            <tr>
                <th>
                    <label for="email_send">
                        <?php esc_html_e('Send e-mail', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php $emailSend = defined('EMAIL_SEND') ? (int)EMAIL_SEND : 0; ?>

                    <select id="email_send" name="opt[email_send]" class="inputOut">
                        <option value="0" <?php echo $emailSend === Boolean::NO ? 'selected' : '' ?>>
                            <?php esc_html_e('No', 'moloni-on') ?>
                        </option>
                        <option value="1" <?php echo $emailSend === Boolean::YES ? 'selected' : '' ?>>
                            <?php esc_html_e('Yes', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('The document is only sent to the customer if it is inserted as closed', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Documents - Exemptions -->
        <h2 class="title">
            <?php esc_html_e('Document - Exemptions', 'moloni-on') ?>
        </h2>

        <div class="subtitle">
            <?php esc_html_e('National and intra-community sales', 'moloni-on') ?>
            <?php esc_html_e('(within the European Union)', 'moloni-on') ?>

            <a style="cursor: help;" title="<?php esc_html_e('European Union countries', 'moloni-on') . ': ' . implode(", ", Tools::$europeanCountryCodes) ?>">
                (?)
            </a>
        </div>
        <table class="form-table mb-4">
            <tbody>
            <tr>
                <?php
                $reasonName = 'exemption_reason';
                $reasonValue = defined('EXEMPTION_REASON') ? EXEMPTION_REASON : '';
                ?>
                <th>
                    <label for="<?php echo $reasonName ?>">
                        <?php esc_html_e('Exemption reason', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php include MOLONI_ON_TEMPLATE_DIR . 'Blocks/Settings/ExemptionOption.php'; ?>

                    <p class='description'>
                        <?php esc_html_e('Will be used if items do not have tax', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <?php
                $reasonName = 'exemption_reason_shipping';
                $reasonValue = defined('EXEMPTION_REASON_SHIPPING') ? EXEMPTION_REASON_SHIPPING : '';
                ?>

                <th>
                    <label for="<?php echo $reasonName ?>">
                        <?php esc_html_e('Shipping exemption reason', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php include MOLONI_ON_TEMPLATE_DIR . 'Blocks/Settings/ExemptionOption.php'; ?>

                    <p class='description'>
                        <?php esc_html_e('Will be used if shipping does not have tax', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <div class="subtitle">
            <?php esc_html_e('Extra community sales', 'moloni-on') ?>
            <?php esc_html_e('(outside the European Union)', 'moloni-on') ?>

            <a style="cursor: help;" title="<?php echo esc_html__('European Union countries', 'moloni-on') . ': ' . implode(", ", Tools::$europeanCountryCodes) ?>">
                (?)
            </a>
        </div>
        <table class="form-table mb-4">
            <tbody>

            <tr>
                <?php
                $reasonName = 'exemption_reason_extra_community';
                $reasonValue = defined('EXEMPTION_REASON_EXTRA_COMMUNITY') ? EXEMPTION_REASON_EXTRA_COMMUNITY : '';
                ?>

                <th>
                    <label for="<?php echo $reasonName ?>">
                        <?php esc_html_e('Exemption reason', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php include MOLONI_ON_TEMPLATE_DIR . 'Blocks/Settings/ExemptionOption.php'; ?>

                    <p class='description'>
                        <?php esc_html_e('Will be used if items do not have tax', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <?php
                $reasonName = 'exemption_reason_shipping_extra_community';
                $reasonValue = defined('EXEMPTION_REASON_SHIPPING_EXTRA_COMMUNITY') ? EXEMPTION_REASON_SHIPPING_EXTRA_COMMUNITY : '';
                ?>

                <th>
                    <label for="<?php echo $reasonName ?>">
                        <?php esc_html_e('Shipping exemption reason', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php include MOLONI_ON_TEMPLATE_DIR . 'Blocks/Settings/ExemptionOption.php'; ?>

                    <p class='description'>
                        <?php esc_html_e('Will be used if shipping does not have tax', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Products -->
        <h2 class="title">
            <?php esc_html_e('Document', 'moloni-on') ?>
            -
            <?php esc_html_e('Products', 'moloni-on') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <tr>
                <th>
                    <label for="moloni_product_warehouse">
                        <?php esc_html_e('Warehouse', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <select id="moloni_product_warehouse" name='opt[moloni_product_warehouse]' class='inputOut'>
                        <option value='0'>
                            <?php esc_html_e('Default warehouse', 'moloni-on') ?>
                        </option>

                        <?php $moloniProductWarehouse = defined('MOLONI_PRODUCT_WAREHOUSE') ? (int)MOLONI_PRODUCT_WAREHOUSE : 0; ?>

                        <optgroup label="<?php esc_html_e('Warehouses', 'moloni-on') ?>">
                            <?php foreach ($warehouses as $warehouse) : ?>
                                <option value='<?php echo $warehouse['warehouseId'] ?>' <?php echo ($moloniProductWarehouse === $warehouse['warehouseId'] ? 'selected' : '') ?>>
                                    <?php echo $warehouse['name'] ?> (<?php echo $warehouse['number'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Warehouse used in documents', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="measure_unit_id"><?php esc_html_e('Measurement unit', 'moloni-on') ?></label>
                </th>
                <td>
                    <?php $measureUnit = defined('MEASURE_UNIT') ? (int)MEASURE_UNIT : 0; ?>

                    <select id="measure_unit_id" name='opt[measure_unit]' class='inputOut'>
                        <?php foreach ($measurementUnits as $measurementUnit) : ?>
                            <option value='<?php echo $measurementUnit['measurementUnitId'] ?>' <?php echo ($measureUnit === $measurementUnit['measurementUnitId'] ? 'selected' : '') ?>>
                                <?php echo $measurementUnit['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Required', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Customer -->
        <h2 class="title">
            <?php esc_html_e('Document', 'moloni-on') ?>
            -
            <?php esc_html_e('Customer\'s', 'moloni-on') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>
            <tr>
                <th>
                    <label for="customer_language">
                        <?php esc_html_e('Customer language', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php
                    $customerLanguage = 0;

                    if (defined('CUSTOMER_LANGUAGE')) {
                        $customerLanguage = (int)CUSTOMER_LANGUAGE;
                    }
                    ?>

                    <select id="customer_language" name='opt[customer_language]' class='inputOut'>
                        <option value='0' <?php echo ($customerLanguage === 0 ? 'selected' : '') ?>>
                            <?php esc_html_e('Automatic', 'moloni-on') ?>
                        </option>
                        <optgroup label="<?php esc_html_e('Language', 'moloni-on') ?>">
                            <option value='<?php echo Languages::PT ?>' <?php echo ($customerLanguage === Languages::PT ? 'selected' : '') ?>>
                                <?php esc_html_e('Portuguese', 'moloni-on') ?>
                            </option>
                            <option value='<?php echo Languages::ES ?>' <?php echo ($customerLanguage === Languages::ES ? 'selected' : '') ?>>
                                <?php esc_html_e('Spanish', 'moloni-on') ?>
                            </option>
                            <option value='<?php echo Languages::EN ?>' <?php echo ($customerLanguage === Languages::EN ? 'selected' : '') ?>>
                                <?php esc_html_e('English', 'moloni-on') ?>
                            </option>
                        </optgroup>
                    </select>

                    <p class='description'>
                        <?php esc_html_e('Default language for customer\'s', 'moloni-on') ?>
                    </p>

                </td>
            </tr>

            <tr>
                <th>
                    <label for="client_prefix">
                        <?php esc_html_e("Customer's number prefix", 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php $clientPrefix = defined('CLIENT_PREFIX') ? CLIENT_PREFIX : ''; ?>

                    <input id="client_prefix"
                           name="opt[client_prefix]"
                           type="text"
                           value="<?php echo $clientPrefix ?>"
                           class="inputOut"
                    >

                    <div style="max-width: 80vw ;overflow:hidden;">
                        <a id="prefix_preview">
                            (<?php esc_html_e('Example', 'moloni-on') . ': ' . $clientPrefix ?>)
                        </a>
                    </div>

                    <p class='description'>
                        <?php esc_html_e("Prefix used when creating customer's", 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="maturity_date_id">
                        <?php esc_html_e('Maturity date', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php $clientPrefix = defined('MATURITY_DATE') ? (int)MATURITY_DATE : 0; ?>

                    <select id="maturity_date_id" name='opt[maturity_date]' class='inputOut'>
                        <option value='0' <?php echo $clientPrefix === 0 ? 'selected' : '' ?>>
                            <?php esc_html_e('Choose an option', 'moloni-on') ?>
                        </option>

                        <?php foreach ($maturityDates as $maturityDate) : ?>
                            <option value='<?php echo $maturityDate['maturityDateId'] ?>' <?php echo $clientPrefix === $maturityDate['maturityDateId'] ? 'selected' : '' ?>>
                                <?php echo $maturityDate['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class='description'>
                        <?php esc_html_e('Default maturity date for customer\'s', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="payment_method_id">
                        <?php esc_html_e('Payment method', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php $selectedPaymentMethod = defined('PAYMENT_METHOD') ? (int)PAYMENT_METHOD : 0; ?>

                    <select id="payment_method_id" name='opt[payment_method]' class='inputOut'>
                        <option value='0' <?php echo $selectedPaymentMethod === 0 ? 'selected' : '' ?>>
                            <?php esc_html_e('Choose an option', 'moloni-on') ?>
                        </option>

                        <?php foreach ($paymentMethods as $paymentMethod) : ?>
                            <option value='<?php echo $paymentMethod['paymentMethodId'] ?>' <?php echo $selectedPaymentMethod === $paymentMethod['paymentMethodId'] ? 'selected' : '' ?>>
                                <?php echo $paymentMethod['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <p class='description'>
                        <?php esc_html_e('Default payment method for customer\'s', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="vat_validate">
                        <?php esc_html_e('Validate VAT', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php
                    $vatValidate = 0;

                    if (defined('VAT_VALIDATE')) {
                        $vatValidate = (int)VAT_VALIDATE;
                    }
                    ?>

                    <select id="vat_validate" name='opt[vat_validate]' class='inputOut'>
                        <option value='0' <?php echo ($vatValidate === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('No', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo ($vatValidate === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Yes', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('If the VAT number is invalid, the document will be issued to the "final consumer"', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th>
                    <label for="vat_field">
                        <?php esc_html_e('Customer VAT', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <select id="vat_field" name="opt[vat_field]" class="inputOut">
                        <?php
                        $customFields = Settings::getPossibleVatFields();

                        $vatField = '';

                        if (defined('VAT_FIELD') && !empty(VAT_FIELD)) {
                            $vatField = VAT_FIELD;
                        } elseif (External::isMoloniVatPluginActive()) {
                            $vatField = '_billing_vat';

                            if (!in_array($vatField, $customFields, true)) {
                                $customFields[] = $vatField;
                            }
                        }
                        ?>

                        <option value='' <?php echo empty($vatField) ? 'selected' : '' ?>>
                            <?php esc_html_e('Choose an option', 'moloni-on') ?>
                        </option>

                        <?php foreach ($customFields as $customField) : ?>
                            <option value='<?php echo esc_html($customField) ?>' <?php echo $vatField === $customField ? 'selected' : '' ?>>
                                <?php echo esc_html($customField) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class='description'>
                        <?php esc_html_e("Custom field associated with the customer's taxpayer ID. If the field doesn't appear, make sure you have at least one order with this field in use.", 'moloni-on') ?>
                        <br>
                        <?php _e("For the Custom Field to appear, you must have at least one order with the taxpayer ID filled. The field should have a name like <i>_billing_vat</i>.", 'moloni-on') ?>
                        <br>
                        <?php _e("If you don't have a field for the taxpayer ID yet, you can add the plugin available <a target='_blank' href='https://wordpress.org/plugins/contribuinte-checkout'>here.</a>", 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            </tbody>
        </table>

        <!-- Hooks -->
        <h2 class="title">
            <?php esc_html_e('Hooks', 'moloni-on') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <!-- Listagem de encomendas -->
            <tr>
                <th>
                    <label for="moloni_show_download_column">
                        <?php esc_html_e('WooCommerce order list', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <select id="moloni_show_download_column" name='opt[moloni_show_download_column]' class='inputOut'>
                        <?php $moloniShowDownloadColumn = defined('MOLONI_SHOW_DOWNLOAD_COLUMN') ? (int)MOLONI_SHOW_DOWNLOAD_COLUMN : Boolean::NO; ?>

                        <option value='0' <?php echo ($moloniShowDownloadColumn === Boolean::NO ? 'selected' : '') ?>>
                            <?php esc_html_e('No', 'moloni-on') ?>
                        </option>
                        <option value='1' <?php echo ($moloniShowDownloadColumn === Boolean::YES ? 'selected' : '') ?>>
                            <?php esc_html_e('Yes', 'moloni-on') ?>
                        </option>
                    </select>
                    <p class='description'>
                        <?php esc_html_e('Add, in WooCommerce, a column in the order list with fast download of PDF documents', 'moloni-on') ?>
                    </p>
                </td>
            </tr>
            </tbody>
        </table>

        <!-- Advanced -->
        <h2 class="title">
            <?php esc_html_e('Advanced', 'moloni-on') ?>
        </h2>
        <table class="form-table mb-4">
            <tbody>

            <!-- Limit orders by date -->
            <tr>
                <th>
                    <label for="order_created_at_max">
                        <?php esc_html_e('Show orders from the following date', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <?php
                    $orderCreatedAtMax = '';

                    if (defined('ORDER_CREATED_AT_MAX')) {
                        $orderCreatedAtMax = ORDER_CREATED_AT_MAX;
                    }
                    ?>

                    <input value="<?php echo esc_html($orderCreatedAtMax) ?>"
                           id="order_created_at_max"
                           name='opt[order_created_at_max]'
                           type="date"
                           style="width: 330px;"
                           placeholder="">

                    <p class='description'>
                        <?php esc_html_e('Date used to limit the search for pending orders', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <!-- Send alert e-mail -->
            <tr>
                <th>
                    <label for="alert_email">
                        <?php esc_html_e('Alert e-mail', 'moloni-on') ?>
                    </label>
                </th>
                <td>
                    <input id="alert_email"
                           name="opt[alert_email]"
                           type="text"
                           value="<?php echo (defined('ALERT_EMAIL') ? ALERT_EMAIL : '') ?>"
                           class="inputOut"
                    >

                    <p class='description'>
                        <?php esc_html_e('Receive alerts for when an error occurs (document creation/authentication lost).', 'moloni-on') ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th></th>
                <td>
                    <input type="submit"
                           name="submit"
                           id="submit"
                           class="button button-primary"
                           value="<?php esc_html_e('Save changes', 'moloni-on') ?>"
                    >
                </td>
            </tr>

            </tbody>
        </table>
    </div>
</form>

<script>
    jQuery(document).ready(function () {
        Moloni.Settings.init({
            example: "<?php esc_html_e('Example', 'moloni-on') ?>"
        });
    });
</script>
