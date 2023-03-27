<?php
global $cpbp_options;
global $CP_Sage;

$home_url = home_url();
$menu_page_url = menu_page_url(CPLINK_SETTINGS_LINK, false);

// Prepare data for options values --
$woo_active_payments = array();
$gateways = WC()->payment_gateways->get_available_payment_gateways();
if( $gateways ) {
    foreach( $gateways as $gateway ) {
        if( $gateway->enabled == 'yes' && in_array($gateway->id, CPBP_AVAILABLE_PAYMENTS) ) {
            $woo_active_payments[$gateway->id] = $gateway->title;
        }
    }
}
// -- Prepare data for options values
$tabs = apply_filters('cpbp_option_tabs_filter',
    array(
        'general' => array(
            'title' => __('General', CPLINK_NAME),
            'id' => 'general'
        )
    )
);
$sections = apply_filters('cpbp_option_sections_filter',
    array(
        'general' => array(
            array(
                'title' => __('Settings', CPBP_NAME),
                'id' => 'general_settings'
            ),
            array(
                'title' => __('Import/Export', CPBP_NAME),
                'id' => 'import_export_settings'
            ),
            array(
                'title' => __('Email', CPBP_NAME),
                'id' => 'email_settings'
            )
        )
    )
);

$options = apply_filters('cpbp_options_filter',
    array(
        'general' => array(
            'general_settings' => array(
                array(
                    'title' => __('Allow Make Payments', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'allow_make_payments',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('...', CPBP_NAME)
                ),
                array(
                    'title' => __('Allow Deposits', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'allow_deposits',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('...', CPBP_NAME),
                    'dependencies' => array(
                        'allow_make_payments' => '1'
                    ),
                ),
                array(
                    'title' => __('Active Payment Methods for Cache Receipt', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'active_payment_methods',
                    'dependencies' => array(
                        'allow_make_payments' => '1'
                    ),
                    'multiple' => true,
                    'options' => $woo_active_payments,
                    'value' => '',
                    'default' => '',
                    'description' => __('This settings specifies to set Active Payment Methods to accept Cache Receipt', CPLINK_NAME)
                ),
                array(
                    'title' => __('Invoice Past Days', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'text',
                    'id' => 'invoice_past_days',
                    'value' => '',
                    'default' => '',
                    'description' => __('Only invoices with date not older than specified days will be accessible.', CPBP_NAME),
                    'dependencies' => array(
                        'allow_make_payments' => '1'
                    ),
                ),
                array(
                    'title' => __('Allow Using ACH', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'allow_using_ach',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('...', CPBP_NAME),
                    'dependencies' => array(
                        'allow_make_payments' => '1'
                    ),
                ),
                /*array(
                    'title' => __('Require Nickname', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'require_nickname',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('Show nickname field on cash receipt create form.', CPBP_NAME),
                    'dependencies' => array(
                        'allow_make_payments' => '1'
                    ),
                ),
                array(
                    'title' => __('Require Card Verification Number', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'require_card_verification_number',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('Show cvv/cvn field on cash receipt create form.', CPBP_NAME),
                    'dependencies' => array(
                        'allow_make_payments' => '1'
                    ),
                ),*/
            ),
            'import_export_settings' => array(
                array(
                    'title' => __('Cash Receipts Import', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'cash_receipts_import',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('...', CPBP_NAME),
                ),
                array(
                    'title' => __('Cash Receipts History Import', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'cash_receipts_history_import',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('...', CPBP_NAME),
                    'dependencies' => array(
                        'cash_receipts_import' => '1'
                    ),
                ),
                array(
                    'title' => __('Cash Receipts Cut of Months', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'text',
                    'id' => 'cash_receipts_cut_of_months',
                    'value' => '',
                    'default' => '',
                    'description' => __('This value can be used to filter cash receipts by modified date on import.', CPBP_NAME),
                    'dependencies' => array(
                        'cash_receipts_import' => '1'
                    ),
                ),
                array(
                    'title' => __('Cash Export', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'cash_export',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('...', CPBP_NAME),
                ),
                /*array(
                    'title' => __('Truncate Addresses', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'truncate_addresses',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '1',
                    'description' => __('Set Yes if you want to truncate addresses while exporting', CPBP_NAME),
                    'dependencies' => array(
                        'cash_export' => '1'
                    ),
                ),*/
                array(
                    'title' => __('Exportable Status', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'exportable_status',
                    'options' => array(
                        '0' => 'New',
                        '1' => 'Sent',
                        '2' => 'Error on Send',
                    ),
                    'value' => '',
                    'default' => '0',
                    'multiple' => true,
                    'description' => __('Cash receipts with queue status selected above will be exported', CPLINK_NAME),
                    'dependencies' => array(
                        'cash_export' => '1'
                    ),
                ),
                array(
                    'title' => __('Max Exporting Attempts', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'text',
                    'id' => 'max_exporting_attempts',
                    'value' => '',
                    'default' => '',
                    'description' => __('Cash receipts with exporting attempts count less than this value will be exported. 0 or empty to disable', CPBP_NAME),
                    'dependencies' => array(
                        'cash_export' => '1'
                    ),
                ),
            ),
            'email_settings' => array(
                array(
                    'title' => __('Enabled', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'selectbox',
                    'id' => 'email_enabled',
                    'options' => array(
                        '1' => 'Yes',
                        '0' => 'No',
                    ),
                    'value' => '',
                    'default' => '0',
                    'description' => __('...', CPBP_NAME),
                ),
                array(
                    'title' => __('New Cash Receipts Email Sender', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'new_cash_receipts_email_sender',
                    'options' => array(
                        'general' => 'General Contact',
                        'sales' => 'Sales Representative',
                        'support' => 'Customer Support',
                        'custom1' => 'Custom Email 1',
                        'custom2' => 'Custom Email 2',
                    ),
                    'value' => '',
                    'default' => 'general',
                    'description' => __('...', CPLINK_NAME),
                    'dependencies' => array(
                        'email_enabled' => '1'
                    ),
                ),
                /*array(
                    'title' => __('New Cash Receipts Email Template', CPLINK_NAME),
                    'subtitle' => __('[store view]', CPLINK_NAME),
                    'type' => 'selectbox',
                    'id' => 'new_cash_receipts_email_template',
                    'options' => array(
                        '' => '',
                        '1' => 'New Pickup Order',
                        '2' => 'New Pickup Order For Guest',
                    ),
                    'value' => '',
                    'default' => '',
                    'description' => __('...', CPLINK_NAME),
                    'dependencies' => array(
                        'email_enabled' => '1'
                    ),
                ),
                array(
                    'title' => __('Send Email Copy To', CPBP_NAME),
                    'subtitle' => __('[store view]', CPBP_NAME),
                    'type' => 'text',
                    'id' => 'send_email_copy_to',
                    'value' => '',
                    'default' => '',
                    'description' => __('Comma-separated.', CPBP_NAME),
                    'dependencies' => array(
                        'email_enabled' => '1'
                    ),
                ),*/
            )
        )
    )
);

//Building Option Area
$current_tab = (isset($_GET['tab']) && isset($tabs[ $_GET['tab'] ]))?$_GET['tab']:'general';

echo '<div id="cplink_option_area"><form id="cplink_settings_form" method="post" action="options.php">'; // option area div -
settings_fields('cpbp_option_settings');
do_settings_sections('cpbp_option_settings');
echo '<input type="hidden" name="tab" value="'.$current_tab.'">';

echo '<h1>' . $tabs[$current_tab]['title'].' - '.__('CertiPro Bill Pay (Cash receipt) Options', CPBP_NAME) . '</h1>';
echo '<div class="clink_settings_wrapper">';
echo '<div class="clink_settings_notices">';
settings_errors();
echo '</div>';


echo '<div class="tabbed_settings">'; //tabs wrapper
echo '<div class="tabs_wrapper">';
foreach ($tabs as $tab) {
    $tab_class =" ";
    if( $current_tab == $tab['id'] )
        $tab_class.=" active";
    echo '<div class="tab"><a href="'.$menu_page_url.'&tab='.$tab['id'].'" id="' . $tab['id'] . '" class="'.$tab_class.'">' . $tab['title'] . '</a></div>';
}
echo '</div>';

echo '<div class="tabs_content_wrapper">';
if ( $current_tab ) {
    echo '<div class="tab_content" id="' . $current_tab . '_tab">';
    echo '<table id="cplink_option_content"> <tr> '; // #cplink_option_content -
    //	Building Option Menu Sections
    echo '<td class="cplink_option_sections col-md-3"> <ul class="">';
    $tab_sections = $sections[$current_tab];
    foreach ($tab_sections as $section) {
        echo '<li class="i_cplink_section_tab"><a href="#" id="i_' . $section['id'] . '" >' . $section['title'] . '</a></li>';
    }
    echo '</ul></td>';

    //	Building Options Content Sections
    echo '<td class="cplink_option_fields_div col-md-12">';
    $option_div_class = '';
    if( $current_tab == 'shipping_methods' )
        $option_div_class = 'col-md-4';

    $tab_section_options = $options[$current_tab];
    $dependencies_array = [];
    foreach ($tab_section_options as $option => $fileds) {
        echo '<div id="i_' . $option . '_option" class="i_cplink_section_content">';

        foreach ($fileds as $field) {
            if(CPLINK::isset_return($field, 'dependencies')){
                $dependencies_array['field_'.$field['id']] = $field['dependencies'];
            }
            //'.( isset($field["depends_from"]) ? 'data-depends_from="field_'.$field["depends_from"].'_"' : '' ).' '.( isset($field["dependance_value"]) ? 'data-dependance_value="'.$field["dependance_value"].'"' : '' ).'
            echo '<div class="cplink_option_field_div field_' . $field['id'] . '_div '.$option_div_class.'" >';

            if (isset($field['global_option']) && $field['global_option']) {
                $f_value = get_option($field['id']);
            } else {
                $f_value = (isset($cpbp_options[$field['id']])) ? $cpbp_options[$field['id']] : '';
                if (isset($field['id_key'])) {
                    $f_value = ($f_value) ? $f_value[$field['id_key']] : '';
                } else {
                    $field['id_key'] = false;
                }
            }

            switch ($field['type']) {
                case "text";
                    echo CPBP_Admin::create_section_for_text($field, $f_value);
                    break;

                case "textarea":
                    echo CPBP_Admin::create_section_for_textarea($field, $f_value);
                    break;

                case "textarea_editor":
                    CPBP_Admin::create_section_for_textarea_editor($field, $f_value);
                    break;

                case "checkbox":
                    echo CPBP_Admin::create_section_for_checkbox($field, $f_value);
                    break;

                case "radio":
                    echo CPBP_Admin::create_section_for_radio($field, $f_value);
                    break;

                case "selectbox":
                    echo CPBP_Admin::create_section_for_selectbox($field, $f_value);
                    break;

                case "email";
                    echo CPBP_Admin::create_section_for_email($field, $f_value);
                    break;

                case "number":
                    echo CPBP_Admin::create_section_for_number($field, $f_value);
                    break;

                case "post_selector":
                    echo CPBP_Admin::create_section_for_post_selector($field, $f_value);
                    break;

                case "post_selectbox":
                    echo CPBP_Admin::create_section_for_post_selectbox($field, $f_value);
                    break;

                case "image_url":
                    echo CPBP_Admin::create_section_for_image_url($field, $f_value);
                    break;

                case "date_picker":
                    echo CPBP_Admin::create_section_for_date_picker($field, $f_value);
                    break;

                case "color_picker":
                    echo CPBP_Admin::create_section_for_color_picker($field, $f_value);
                    break;

                case "intro_view":
                    echo CPBP_Admin::create_section_for_intro_view($field, $f_value);
                    break;

                case "html_view":
                    echo CPBP_Admin::create_section_for_html_view($field, $f_value);
                    break;
            }

            echo '</div>';
        }

        echo '</div>';
    }
    echo '</td>';
    ?>
    <script>
        var dependencies_array = <?php echo json_encode($dependencies_array); ?>;
    </script>
    <?php
    echo '</tr></table>'; // - #cplink_option_content
    echo '</div>';//tab end
}
echo '</div>';//tabs content wrapper end
echo '</div>';//tabs wrapper end
echo get_submit_button();
echo '</div>';//clink settings wrapper
echo '</form></div>'; // - option area div