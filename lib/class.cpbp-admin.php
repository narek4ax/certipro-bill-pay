<?php

class CPBP_Admin
{

    private static $initiated = false;
    private static $settings_name = '';

    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
    }



    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        self::$initiated = true;

        if (!is_network_admin())
            self::register_cpbp_settings();

        add_action('admin_menu', array('CPBP_Admin', 'init_menus'), 10, 2);
        add_action('admin_enqueue_scripts', array('CPBP_Admin', 'enqueue_admin_scripts'));

        add_action('wp_ajax_cpbp_import', array('CPBP_Admin', 'cpbp_import'));

        add_action('wp_ajax_i_cpbp_delete_queue_orders', array('CPBP_Admin', 'delete_queue_orders'));
        add_action('wp_ajax_i_cpbp_export_orders_to_sage_request', array('CPBP_Admin', 'export_orders_to_sage_request'));
        /*
        add_action('admin_notices', array('CPBP_Admin', 'general_admin_notice'));
        add_action('wp_ajax_i_import_order_to_queue', array('CPBP_Admin', 'import_order_to_queue'));
        */
    }

    public static function general_admin_notice()
    {
        if (!CPBP::is_woo_active()) {
            echo '<div class="notice notice-warning is-dismissible"><h3>' . CPBP_PLUGIN_NAME . '</h3>
             <p>' . __('Woocommerce plugin is missing, Please install Woocommerce & try again', CPBP_NAME) . '</p>
         </div>';
        }
    }

    public static function init_menus()
    {
        $parent_slug = CPBP_SETTINGS_LINK;
        $icon_link = CPLINK_PLUGIN_URL . 'images/icon/CertiProIcon-white.png';
        add_menu_page('Cash Receipts', 'Cash Receipts', 'manage_options', $parent_slug, array('CPBP_Admin', 'i_settings'), $icon_link, '80.08');
        add_submenu_page($parent_slug, 'Import', 'Import', 'manage_options', 'cpbp_import', array('CPBP_Admin', 'cpbp_import_page'), 1);
        add_submenu_page($parent_slug, 'Queue', 'Queue', 'manage_options', 'cpbp_queue', array('CPBP_Admin', 'cpbp_queue_page'), 2);
    }

    public static function enqueue_admin_scripts()
    {
        wp_enqueue_style('cpbp_admin_global_style', CPBP_PLUGIN_URL . 'resources/style/admin_global.css', array(), CPBPVersion, 'all');
    }

    public static function cpbp_css_and_js($enqueue_uploader = false)
    {
        CPLINK_ADMIN::cplink_css_and_js();
        wp_enqueue_style('cpbp_admin_style', CPBP_PLUGIN_URL . 'resources/style/admin_style.css', array(), CPBPVersion, 'all');
        wp_enqueue_script('cpbp-admin-js', CPBP_PLUGIN_URL . 'resources/js/admin_js.js', array('jquery'), CPBPVersion, true);
        wp_localize_script('cpbp-admin-js', 'cpbp_infos',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'loadingMessage' => __('Loading...', CPBP_NAME),
                'importMessage' => __('Import in progress...', CPBP_NAME),
                'exportMessage' => __('Export in progress...', CPBP_NAME),
                'exportSuccessMessage' => __('Export is successfully done!', CPBP_NAME),
                'exportErrorMessage' => __('Something went wrong:', CPBP_NAME),
                'deleteMessage' => __('Delete in progress...', CPBP_NAME),
                'deleteSuccessMessage' => __('Delete is successfully done!', CPBP_NAME),
                'deleteErrorMessage' => __('Something went wrong!', CPBP_NAME),
            )
        );
    }

    public static function i_settings()
    {
        global $cpbp_options;

        $settings_name = self::get_settings_name();

        $cpbp_options = get_option($settings_name, true);

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();

        wp_enqueue_script('cplink_options_js', CPLINK_PLUGIN_URL . 'resources/plugin-options/js.js', array('wp-color-picker', 'jquery-ui-core', 'jquery-ui-datepicker'), false, true);
        wp_enqueue_style('cplink_options_style', CPLINK_PLUGIN_URL . 'resources/plugin-options/style.css', array(), CPLINKVersion, 'all');
        wp_enqueue_style('cpbp_options_style', CPBP_PLUGIN_URL . 'resources/plugin-options/style.css', array(), CPBPVersion, 'all');

        self::cpbp_css_and_js();

        require_once(CPBP_PLUGIN_DIR . 'view/admin/cpbp_settings.php');
    }

    public static function cpbp_import_page()
    {

        self::cpbp_css_and_js(true);
        wp_enqueue_script('cpbp-import-js', CPLINK_PLUGIN_URL . 'resources/js/cp_import.js', array('jquery'), CPBPVersion, true);

        require_once(CPBP_PLUGIN_DIR . 'view/admin/cpbp_import.php');
    }

    public static function cpbp_queue_page()
    {

        self::cpbp_css_and_js(true);

        require_once(CPBP_PLUGIN_DIR . 'view/admin/cpbp_queue.php');
    }


    public static function cpbp_option_name($field, $key = false)
    {
        $op_name = self::$settings_name;

        $name = $field['id'];

        if (isset($field['global_option']) && $field['global_option'])
            return $name;

        if ($key)
            return $op_name . '[' . $name . '][' . $key . ']';

        return $op_name . '[' . $name . ']';
    }

    public static function cpbp_option_name_1($field, $key = false)
    {
        $name = $field['id'];

        if (isset($field['global_option']) && $field['global_option'])
            return $name;

        if ($key)
            return CPBP_SETTINGS_NAME . '[' . $name . '][' . $key . ']';

        return CPBP_SETTINGS_NAME . '[' . $name . ']';
    }

    /*
     * Register CPLink Settings
     * */

    public static function set_settings_name($current_tab = 'general')
    {
        $settings_name = CPBP_SETTINGS_NAME;

        $current_tab = (isset($_REQUEST['tab'])) ? $_REQUEST['tab'] : $current_tab;

        if ($current_tab == 'general')
            $current_tab = '';

        if ($current_tab)
            $settings_name .= '-' . $current_tab;

        self::$settings_name = $settings_name;
    }

    public static function get_settings_name()
    {
        return self::$settings_name;
    }

    private static function register_cpbp_settings()
    {
        self::set_settings_name();
        $settings_name = self::get_settings_name();

        register_setting('cpbp_option_settings', $settings_name, array('CPBP_Admin', 'settings_validate'));
    }

    public static function settings_validate($input)
    {
        global $CP_Sage;
        // add_settings_error( $setting, $code, $message, $type )
        $message = 'Settings saved.';
        $type = 'updated';
        add_settings_error('cpbp_settings', 'cpbp_settings_updated', $message, $type); //exit;
        if (isset($CP_Sage)) {
            $CP_Sage->testConnection();
        }
        return $input;
    }

    /*
     *	Meta boxes
     */

    /*
     *	Field generators
     */
    public static function create_section_for_text($field, $value = '')
    {
        if (!$value && CPLINK::isset_return($field, 'default')) $value = $field['default'];
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cpbp_option_name($field, $field['id_key']) . '" value="' . htmlspecialchars($value) . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input" >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_email($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (CPLINK::isset_return($field, 'validate'))
            $attrs .= ' validate ';
        if (!$value && CPLINK::isset_return($field, 'default')) $value = $field['default'];
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cpbp_option_name($field, $field['id_key']) . '" value="' . htmlspecialchars($value) . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input email_field" >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_number($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (!$value && isset($field['default'])) $value = $field['default'];
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="number" name="' . self::cpbp_option_name($field, $field['id_key']) . '" value="' . $value . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input" >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_textarea($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (!$value && CPLINK::isset_return($field, 'default')) $value = $field['default'];
        $rows = ($field['rows']) ? $field['rows'] : 3;
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<textarea type="text" rows="' . $rows . '" name="' . self::cpbp_option_name($field) . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input" >' . $value . '</textarea>';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_textarea_editor($field, $value = '')
    {
        $html = '';
        echo '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        echo '<p class="subtitle">' . $field['subtitle'] . '</p>';
        wp_editor($value, 'field_' . $field['id'] . '_' . $field['id_key'],
            array(
                'textarea_rows' => 12,
                'textarea_name' => self::cpbp_option_name($field),
                //'media_buttons' => 1,
            )
        );
        /*echo '<textarea type="text" name="'.self::cpbp_option_name( $field ).'" ' .
            ' id="field_'.$field['id'].'_'.$field['id_key'].'" placeholder="' . CPLINK::isset_return($field, 'placeholder').'" class="i_input i_texteditor" >'. $value . '</textarea>';*/
        echo '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_checkbox($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $checked = '';
        if ($value) $checked = 'checked';
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="checkbox" name="' . self::cpbp_option_name($field) . '" value="1" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" class="i_checkbox" ' . $checked . ' >';
        $html .= '<span class="description">' . $field['description'] . '</span>';

        return $html;
    }

    public static function create_section_for_radio($field, $options)
    {
        $html = '';
        return $html;
    }

    public static function create_section_for_selectbox($field, $value = '')
    {
        $options = ($field['options']) ? $field['options'] : array();
        $is_multiple = (isset($field['multiple'])) ? $field['multiple'] : false;
        $html = '';
        $html .= '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';

        if ($value == '' && CPLINK::isset_return($field, 'default')) $value = $field['default'];

        $attrs = '';
        $field_name = self::cpbp_option_name($field);

        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if ($is_multiple) {
            $attrs .= 'multiple';
            $field_name .= '[]';
        }
        $html .= '<select name="' . $field_name . '" id="field_' . $field['id'] . '_' . $field['id_key'] . '" ' . $attrs . ' ' . $attrs . ' >';
        //$html.= '<option value="null" > --- </option>';
        if (count($options)) {
            foreach ($options as $option => $option_name) {
                $i_selected = '';
                if ($is_multiple && is_array($value)) {
                    if (in_array($option, $value))
                        $i_selected = 'selected';
                } elseif ($option == $value) {
                    $i_selected = 'selected';
                }
                $html .= '<option value="' . $option . '" ' . $i_selected . '  >' . $option_name . '</option>';
            }
        }
        $html .= '</select>';

        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_post_selectbox($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $posts = get_pages(array('numberposts' => -1, 'post_type' => 'page', 'post_parent' => 0));
        $front_page_elements = $value;

        if (empty($front_page_elements) || !count($front_page_elements)) {
            $front_page_elements = array('null');
        }
        //i_print($value);

        $html = '';
        $html .= '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label> <br>';

        $html .= '<ul id="featured_posts_list">';

        foreach ($front_page_elements as $element) {
            $html .= '<li class="featured_post_ex"><select name="' . self::cpbp_option_name($field) . '[]" id="field_' . $field['id'] . '_' . $field['id_key'] . '" ' . $attrs . ' >';
            $html .= '<option value="null" > --- </option>';
            foreach ($posts as $post) {
                $i_selected = '';
                if ($element == $post->ID) $i_selected = 'selected';
                $html .= '<option value="' . $post->ID . '" ' . $i_selected . '  >' . $post->post_title . '</option>';
            }
            $html .= '</select><span class="dashicons dashicons-sort i_dragicon" title="Drag for sorting"></span> ';
            $html .= '<p class=""><a href="#" class="i_remove_feature_post"><span class="dashicons dashicons-no"></span> Remove</a></p></li>';
        }

        $html .= '</ul>';
        $html .= '<a href="#" class="i_add_featured_post"><span class="dashicons dashicons-plus"></span>Add featured post</a>';
        //$html.= '<input type="hidden" id="i_the_max_id" value="'.$element_counter.'" />';

        return $html;
    }

    public static function create_section_for_post_selector($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $posts = get_pages(array('numberposts' => -1, 'post_type' => 'page', 'post_parent' => 0));
        //i_print($value);

        $html = '';
        $html .= '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label> <br>';

        $html .= '<select name="' . self::cpbp_option_name($field) . '" id="field_' . $field['id'] . '_' . $field['id_key'] . '" ' . $attrs . ' >';
        $html .= '<option value="" > --- </option>';
        foreach ($posts as $post) {
            $i_selected = '';
            if ($value == $post->ID) $i_selected = 'selected';
            $html .= '<option value="' . $post->ID . '" ' . $i_selected . '  >' . $post->post_title . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    public static function create_section_for_image_url($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        $class = '';
        if (trim($value) == '') $class = 'i_hidden';
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cpbp_option_name($field, $field['id_key']) . '" value="' . $value . '" ' .
            $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" placeholder="' . CPLINK::isset_return($field, 'placeholder') . '" class="i_input i_input_url upload_image_button" >';
        $html .= '<img src="' . $value . '" class="i_preview_img i_preview_field_' . $field['id'] . '_' . $field['id_key'] . ' ' . $class . '" >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_color_picker($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (!$value) $value = $field['default'];
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cpbp_option_name($field) . '" value="' . $value . '" ';
        $html .= $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" class="i_input i_color_picker"  >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }


    public static function create_section_for_date_picker($field, $value = '')
    {
        $attrs = '';
        if (CPLINK::isset_return($field, 'required'))
            $attrs .= ' required ';
        if (!$value) $value = $field['default'];
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" name="' . self::cpbp_option_name($field) . '" value="' . $value . '" ';
        $html .= $attrs . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" class="i_input i_datepicker"  >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_intro_view($field, $value = '')
    {
        //if( !$value )
        $value = CPLINK::isset_return($field, 'value');
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        $html .= '<input type="text" value="' . htmlspecialchars($value) . '" ' . ' id="field_' . $field['id'] . '_' . $field['id_key'] . '" class="i_input i_click_checkall" readonly  >';
        $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    public static function create_section_for_html_view($field, $value = '')
    {
        //if( !$value )
        $value = CPLINK::isset_return($field, 'value');
        $html = '<label for="field_' . $field['id'] . '_' . $field['id_key'] . '">' . $field['title'] . '</label>';
        if ($field['subtitle'])
            $html .= '<p class="subtitle">' . $field['subtitle'] . '</p>';
        if ($field['description'])
            $html .= '<p class="description">' . $field['description'] . '</p>';

        return $html;
    }

    /*
     * export orders request function
     */
    public static function export_orders_to_sage_request()
    {
        if (!(isset($_REQUEST['action']) && 'i_cpbp_export_orders_to_sage_request' == $_POST['action']))
            return;
        if (!empty($_POST['order_ids'])) {

            CPBP::max_server_ini();
            $return = self::export_orders_to_sage($_POST['order_ids']);

        } else {
            $return = array(
                'success' => false,
                'html' => '<h3>' . __('Please Select Order', CPBP_NAME) . '</h3>'
            );
        }


        echo json_encode($return);
        exit;
    }

    public static function export_orders_to_sage($orders = [])
    {
        $request_data_result = [];
        $error_results = array();

        if (!empty($orders)) {
            //here we need customer_number and ar_division_number from options
            global $CPBP_Sage;
            global $wpdb;
            global $cpbp_scope_cf, $cpbp_modules_cf, $cpbp_shipping_methods;
            $cpbp_modules_options = $cpbp_modules_cf;
            $cpbp_options = $cpbp_scope_cf;
            $table_prefix = CPBP_DB_PREFIX;

            //Table structure for table `_queue`
            $table_name = $table_prefix . 'queue';
            $global_data_to_send = [];

            foreach ($orders as $order) {
                $order = intval($order);
                $orderq = get_post($order);
                $db_result = $wpdb->get_results("SELECT * FROM $table_name WHERE external_cash_number = $order");
                $db_result = $db_result[0];
                if (empty($orderq)) {
                    $export_count = 1;
                    if (!is_null($db_result->export_count)) {
                        $export_count = $db_result->export_count + 1;
                    }
                    $updating_info = array('status' => '2', 'update_time' => wp_date("Y-m-d h:i:s"));
                    $msg = __('The Payment is missing from payments list', CPBP_NAME);
                    $updating_info['message'] = $msg;
                    $updating_info['export_count'] = $export_count;
                    $update_result = $wpdb->update($table_name, $updating_info, array('external_cash_number' => $order));
                    $error_results[ strval($order) ] = $msg;
                    if($cpbp_scope_cf['email_enabled']){
                        CPLINK::sendApiErrorMessageToClient('Payment - '.$order . ' - '.$msg, '', 'billpayExport');
                    }
                    continue;
                }
                $order_date = $db_result->created_time;

                $data_to_send = [];

                if(!empty($order)){
                    $data_to_send['external_cash_number'] = $order;
                }

                $cp_ar_division_no = get_post_meta($order,'cp_ar_division_no',true);
                if(!empty($cp_ar_division_no)){
                    $data_to_send['ar_division_number'] = $cp_ar_division_no;
                }

                $cp_customer_no = get_post_meta($order,'cp_customer_no',true);
                if(!empty($cp_customer_no)){
                    $data_to_send['customer_number'] = $cp_customer_no;
                }

                $customer_id = get_post_meta($order,'_customer_user',true);
                $web_user_id = get_user_meta($customer_id, 'cp_web_user_id', true);
                if(!empty($web_user_id)){
                    $data_to_send['user_id'] = $web_user_id;
                }

                $data_to_send['items'] = [];
                $woocommerce_order_items_table = $wpdb->prefix.'woocommerce_order_items';
                $woocommerce_order_items_result = $wpdb->get_results("SELECT * FROM $woocommerce_order_items_table WHERE `order_id` = $order");
                if(!empty($woocommerce_order_items_result)){
                    foreach ($woocommerce_order_items_result as $woocommerce_order_item_key => $item){
                        $order_item_datas = [];
                        $order_item_id = $item->order_item_id;
                        //array_push($order_item_ids,$item->order_item_id);
                        $woocommerce_order_itemmeta_table = $wpdb->prefix.'woocommerce_order_itemmeta';
                        $woocommerce_order_itemmeta_invoice_no_result = $wpdb->get_row("SELECT * FROM $woocommerce_order_itemmeta_table WHERE `order_item_id` = $order_item_id and `meta_key` = 'Invoice No'");
                        $woocommerce_order_itemmeta_line_total_result = $wpdb->get_row("SELECT * FROM $woocommerce_order_itemmeta_table WHERE `order_item_id` = $order_item_id and `meta_key` = '_line_total'");
                        $woocommerce_order_itemmeta_invoice_type_result = $wpdb->get_row("SELECT * FROM $woocommerce_order_itemmeta_table WHERE `order_item_id` = $order_item_id and `meta_key` = 'invoice_type'");

                        /*i_print($woocommerce_order_itemmeta_invoice_no_result);
                        i_print($woocommerce_order_itemmeta_line_total_result);*/
                        $order_item_datas['invoice_number'] = $woocommerce_order_itemmeta_invoice_no_result->meta_value;
                        $order_item_datas['amount_posted'] = max($woocommerce_order_itemmeta_line_total_result->meta_value, 0);
                        $order_item_datas['invoice_type'] = $woocommerce_order_itemmeta_invoice_type_result->meta_value;

                        array_push($data_to_send['items'],$order_item_datas);
                    }
                }


                $data_to_send['deposit_date'] = $order_date;
                $data_to_send['deposit_description'] = '';

                /*need to be dynamic*/
                $data_to_send['deposit_type'] = 'C';
                $data_to_send['payment_type'] = '';
                /*const _CASH = 'C';
                const _CREDIT_CARD = 'R';
                const _ACH = 'A';*/


                $card_types_array = array(
                    'amex' => '3',
                    'visa' => '4',
                    'mastercard' => '5',
                    'discover' => '6',
                    'jcb' => '7',
                );


                $billing_address = [];
                $billing_address['first_name'] = get_post_meta($order,'_billing_first_name',true);
                $billing_address['last_name'] = get_post_meta($order,'_billing_last_name',true);
                $billing_address['address_1'] = get_post_meta($order,'_billing_address_1',true);
                $billing_address['address_2'] = get_post_meta($order,'_billing_address_2',true);
                $billing_address['city'] = get_post_meta($order,'_billing_city',true);
                $billing_address['state'] = get_post_meta($order,'_billing_state',true);
                $billing_address['postcode'] = get_post_meta($order,'_billing_postcode',true);
                $billing_address['country'] = get_post_meta($order,'_billing_country',true);

                $transaction_amount = get_post_meta($order,'_order_total',true);
                $check_number = get_post_meta($order,'_transaction_id',true);


                $_sageresult = get_post_meta($order, '_sageresult', true);
                $zp_payment_data = get_post_meta( $order, '_zp_payment_data', true );

                global $scopeConfig;


                $payment_method = get_post_meta($order,'_payment_method',true);
                if($payment_method == 'sagepaymentsusaapi'){
                    $data_to_send['deposit_type'] = 'R';
                    $data_to_send['payment_type'] = 'WEBCC';
                    $card_type = self::getPaymentMethodCode($_sageresult['Card Type']);
                    $payment_type = '';
                    if(!empty($card_type)){
                        if(isset($scopeConfig['payment_'.strtolower($card_type)])){
                            $payment_type = $scopeConfig['payment_'.strtolower($card_type)];
                            $data_to_send['payment_type'] = $payment_type;
                        }
                    }
                }elseif($payment_method == 'zeamster'){
                    $data_to_send['deposit_type'] = 'R';
                    $data_to_send['payment_type'] = 'WEBCC';
                    if(isset($zp_payment_data['card_type'])){
                        $card_type = self::getPaymentMethodCode($zp_payment_data['card_type']);
                    }else{
                        $card_type = self::getPaymentMethodCode($zp_payment_data['account_type']);
                    }
                    $payment_type = '';
                    if(!empty($card_type)){
                        if(isset($scopeConfig['payment_'.strtolower($card_type)])){
                            $payment_type = $scopeConfig['payment_'.strtolower($card_type)];
                            $data_to_send['payment_type'] = $payment_type;
                        }
                    }
                }


                if(!empty($_sageresult)){
                    $last_4_dig_string = $_sageresult['Card Number'];
                    $last_4_dig = '';
                    if($last_4_dig_string){
                        $last_4_dig_string = explode("-",$last_4_dig_string);
                        $last_4_dig = end($last_4_dig_string);
                    }
                    $cardType = '';
                    if(isset($card_types_array[ strtolower($_sageresult['Card Type']) ] )){
                        $cardType = $card_types_array[strtolower($_sageresult['Card Type'])];
                    }
                    $expiration_date_year = '';
                    $expiration_date_month = '';
                    if(isset($_sageresult['Expiry Date'])){
                        $expDate = $_sageresult['Expiry Date'];
                        $expiration_date_month = substr($expDate, 0,2);
                        if(strlen($expDate) > 4){
                            $expiration_date_year = substr($expDate, -4);
                        }else{
                            $expiration_date_year = '20'.substr($expDate, -2);
                        }
                    }
                    $currToken = '';
                    $currTokenTemp = get_post_meta($order, '_SageToken', true);

                    if(isset($currTokenTemp)){
                        $currToken = $currTokenTemp;
                    }

                    $data_to_send['cc_guid'] = $currToken;
                    $data_to_send['card_type'] = $cardType; // American Express = 3, Visa = 4, MasterCard = 5, Discover = 6, JCB = 7
                    $data_to_send['card_holder_name'] = substr($billing_address['first_name'] . ' ' . $billing_address['last_name'],0,30);
                    $data_to_send['last_4_cc_numbers'] = $last_4_dig;
                    $data_to_send['expiration_date_year'] = $expiration_date_year;
                    $data_to_send['expiration_date_month'] = $expiration_date_month;
                    $data_to_send['cc_transaction_id'] = $check_number;
                    $data_to_send['cc_authorization_number'] = $_sageresult['code'];
                    //$data_to_send['payment_type_category'] = "P";
                    if(isset($_sageresult['timestamp'])){
                        $data_to_send['authorization_date'] = date_format(date_create($_sageresult['timestamp']), 'Ymd');
                        $data_to_send['authorization_time'] = date_format(date_create($_sageresult['timestamp']), 'His');
                    }
                    $data_to_send['transaction_amount'] = $transaction_amount;

                    /*$data_to_send['avs_address1'] = $billing_address['address_1'];
                    $data_to_send['avs_address2'] = $billing_address['address_2'];
                    $data_to_send['avs_zip'] = $billing_address['postcode'];
                    $data_to_send['avs_city'] = $billing_address['city'];
                    $data_to_send['avs_state'] = $billing_address['state'];*/

                    //if($cpbp_scope_cf['truncate_addresses']){
                        $data_to_send['avs_address1'] = substr($billing_address['address_1'],0,30);
                        $data_to_send['avs_address2'] = substr($billing_address['address_2'],0,30);
                        $data_to_send['avs_zip'] = substr($billing_address['postcode'],0,10);
                        $data_to_send['avs_city'] = substr($billing_address['city'],0,20);
                        $data_to_send['avs_state'] = substr($billing_address['state'],0,2);
                    //}
                    $data_to_send['avs_country'] = CPLINK::get_country_iso3($billing_address['country']);
                }elseif(!empty($zp_payment_data)){
                    $zp_payment_data_local = get_post_meta( $order, '_zp_payment_data_local', true );
                    $token_table_name = $wpdb->prefix . 'woocommerce_payment_tokens';

                    if(isset($zp_payment_data['saved_account_vault_id'])){
                        $token = $zp_payment_data['saved_account_vault_id'];
                    }else{
                        $token = $zp_payment_data['account_vault_id'];
                    }
                    $db_result = $wpdb->get_row("SELECT * FROM $token_table_name WHERE token = '$token'",ARRAY_A);
                    $token_metadata = WC_Payment_Token_Data_Store::get_metadata( $db_result['token_id'] );
                    //i_print($zp_payment_data);


                    $last_4_dig = $zp_payment_data['last_four'];

                    $cardType = '';
                    if(isset($zp_payment_data['card_type'])){
                        $card_type = $zp_payment_data['card_type'];
                    }else{
                        $card_type = $zp_payment_data['account_type'];
                    }
                    if (isset($card_types_array[strtolower($card_type)])) {
                        $cardType = $card_types_array[strtolower($card_type)];
                    }

                    if(!empty($token_metadata)){
                        $expiration_date_year = $token_metadata['expiry_year'][0];
                        $expiration_date_month = $token_metadata['expiry_month'][0];
                    }elseif (!empty($zp_payment_data_local)){
                        $expiration_date_year = $zp_payment_data_local['expiry_year'];
                        $expiration_date_month = $zp_payment_data_local['expiry_month'];
                    }

                    $currToken = $token;

                    $data_to_send['cc_guid'] = $currToken;
                    $data_to_send['card_type'] = $cardType; // American Express = 3, Visa = 4, MasterCard = 5, Discover = 6, JCB = 7
                    $data_to_send['card_holder_name'] = substr($billing_address['first_name'] . ' ' . $billing_address['last_name'], 0, 30);
                    $data_to_send['last_4_cc_numbers'] = $last_4_dig;
                    $data_to_send['expiration_date_year'] = $expiration_date_year;
                    $data_to_send['expiration_date_month'] = $expiration_date_month;
                    $data_to_send['cc_transaction_id'] = $check_number;
                    $data_to_send['cc_authorization_number'] = $zp_payment_data['auth_code'];
                    $data_to_send['payment_type_category'] = "P";
                    if (isset($zp_payment_data['transaction_date'])) {
                        $data_to_send['authorization_date'] = date_format(date_create($zp_payment_data['transaction_date']), 'Ymd');
                        $data_to_send['authorization_time'] = date_format(date_create($zp_payment_data['transaction_date']), 'His');
                    }
                    $data_to_send['transaction_amount'] = $transaction_amount;

                    $data_to_send['avs_address1'] = substr($billing_address['address_1'],0,30);
                    $data_to_send['avs_address2'] = substr($billing_address['address_2'],0,30);
                    $data_to_send['avs_zip'] = substr($billing_address['postcode'],0,10);
                    $data_to_send['avs_city'] = substr($billing_address['city'],0,20);
                    $data_to_send['avs_state'] = substr($billing_address['state'],0,2);
                    $data_to_send['avs_country'] = CPLINK::get_country_iso3($billing_address['country']);
                }

                array_push($global_data_to_send,$data_to_send);
            }
            //i_print($global_data_to_send);exit;

            if (!empty($global_data_to_send)) {
                //$createCashreceiptsResult = $CPBP_Sage->createCashreceipts($global_data_to_send);
                $api_response = $CPBP_Sage->createCashreceipts($global_data_to_send);
                //if()
                //i_print($api_response);
                /*i_print($api_response,true);*/
                if ($api_response->success) {
                    $request_data_result['success'] = true;
                    $count = 0;
                    $html = '';
                    foreach ($api_response->data as $item) {
                        $order_id = intval($item->data->external_cash_number);
                        $db_result = $wpdb->get_results("SELECT * FROM $table_name WHERE external_cash_number = $order_id");
                        $db_result = $db_result[0];
                        $export_count = 1;
                        if (!is_null($db_result->export_count)) {
                            $export_count = $db_result->export_count + 1;
                        }
                        $updating_info = array('status' => '2', 'update_time' => wp_date("Y-m-d h:i:s"));
                        $updating_info['message'] = $item->message;
                        $updating_info['export_count'] = $export_count;
                        if ($item->success) {
                            $updating_info['status'] = '1';
                            /*$request_data_result['data'][$count]['message'] = $api_response[0]->message;
                            $request_data_result['data'][$count]['message'] = $api_response[0]->message;*/
                            update_post_meta($order_id, 'cp_sales_order_number', sanitize_text_field($item->data->external_cash_number));
                        }
                        /*else{
                            $updating_info['status'] = '2';
                        }*/
                        $update_resutl = $wpdb->update($table_name, $updating_info, array('external_cash_number' => $order_id));
                        $count++;
                        $html .= '<div class="order_export_status">';
                        $message_from_sage = $item->message;
                        $message = str_replace("Lines:", "", $message_from_sage);
                        $message = str_replace("Header:", "", $message);
                        if ($item->success && $item->message == '') {
                            $message = '<div class="cpbp_resp_">'.$order_id . ' - Successfully exported</div>';
                        } else {
                            $message = '<div class="cpbp_resp_error">'.$order_id . ' - '.$message.'</div>';
                            $data_to_attach = [];
                            foreach ($global_data_to_send as $item) {
                                if ($item['external_order_number'] == $order_id) {
                                    $data_to_attach = $item;
                                }
                            }
                            if($cpbp_scope_cf['email_enabled']){
                                CPLINK::sendApiErrorMessageToClient('Payment - '.$order_id . ' - '.$message_from_sage, '', 'billpayExport');
                            }
                        }
                        $html .= $message;
                        $html .= '</div>';
                    }
                    $request_data_result['html'] = $html;
                } else {
                    //i_print($api_response);
                    $request_data_result['success'] = false;
                    $request_data_result['html'] = $api_response->message;
                    //i_print($api_response,true);
                    /*$request_data_result['html'] = __('Please check the API Base URL',CPBP_NAME);*/
                    if(is_null($api_response) || (isset($api_response->success) && !$api_response->success)){
                        //i_print($api_response);
                        $request_data_result['success'] = false;
                        $request_data_result['html'] = __('There is no connection with api server',CPBP_NAME);
                        //CPBP::sendApiErrorMessageToClient('There is no connection with api server', $global_data_to_send, 'orderExport');

                        if($cpbp_scope_cf['email_enabled']){
                            CPLINK::sendApiErrorMessageToClient('There is no connection with api server', '', 'billpayExport');
                        }
                    }
                }
            } else {
                $request_data_result['html'] = '';
                $request_data_result['success'] = false;
                //CPBP::sendApiErrorMessageToClient('There is no data to send', '', 'orderExport');

                if($cpbp_scope_cf['email_enabled']){
                    CPLINK::sendApiErrorMessageToClient('There is no data to send', '', 'billpayExport');
                }
            }
            $request_data_result['error_results'] = $error_results;
        } else {
            $request_data_result['html'] = __('There is no chosen orders',CPBP_NAME);
            $request_data_result['success'] = false;
            //CPBP::sendApiErrorMessageToClient('There is no chosen orders', '', 'orderExport');
            /*if($cpbp_scope_cf['email_enabled']){
                CPLINK::sendApiErrorMessageToClient('There is no chosen orders', '', 'billpayExport');
            }*/
        }

        return $request_data_result;

    }

    public static function getPaymentMethodCode($ccType)
    {
        $cPmntMethCode = '';
        if (!empty($ccType)) {
            $ccType = strtolower($ccType);

            $visaTypes = ['v', 'vi', 'vis', 'visa'];
            $mCardTypes = ['m', 'mc', 'mcard', 'mascard', 'mastercard'];
            $amExTypes = ['a', 'ae', 'amex', 'americanexpress', 'american express'];
            $discoverTypes = ['d', 'di', 'ds', 'dis', 'discover'];
            $JCBTypes = ['j', 'jcb'];

            if (in_array($ccType, $visaTypes)) {
                $cPmntMethCode = 'VI';
            } else if (in_array($ccType, $mCardTypes)) {
                $cPmntMethCode = 'MC';
            } else if (in_array($ccType, $amExTypes)) {
                $cPmntMethCode = 'AE';
            } else if (in_array($ccType, $discoverTypes)) {
                $cPmntMethCode = 'DI';
            } else if (in_array($ccType, $JCBTypes)) {
                $cPmntMethCode = 'JCB';
            }
        }
        return $cPmntMethCode;
    }

    public static function delete_queue_orders()
    {
        if (!empty($_POST['order_ids'])) {
            $orders = $_POST['order_ids'];
            global $wpdb;
            $table_prefix = CPBP_DB_PREFIX;

            //Table structure for table `_queue`
            $table_name = $table_prefix . 'queue';
            foreach ($orders as $order) {
                $ql_result = $wpdb->query("UPDATE $table_name SET active = 0 where external_cash_number = $order");
            }
            $return['success'] = true;
        } else {
            $return = array(
                'success' => false,
                'html' => '<h3>' . __('Please Select Order', CPBP_NAME) . '</h3>'
            );
        }


        echo json_encode($return);
        exit;
    }

    public static function import_order_to_queue()
    {
        if (!empty($_POST['order_id'])) {
            $order_id = intval($_POST['order_id']);
            $order = new WC_Order($order_id);
            $order_date = $order->order_date;

            global $wpdb;
            $table_prefix = CPBP_DB_PREFIX;

            //Table structure for table `_queue`
            $table_name = $table_prefix . 'queue';

            /*$data = array(
                'web_sales_order_no' => $order_id,
                'message' => '',
                'export_count' => 0,
                'created_time' => wp_date("Y-m-d h:i:s",strtotime($order_date)),
            );

            $result = $wpdb->insert( $table_name, $data);*/
            $result = $wpdb->query("UPDATE $table_name SET active = 1 where web_sales_order_no = $order_id");
            if ($result) {
                $return = array(
                    'success' => true,
                    'html' => '<span>' . __('Successfully imported!', CPBP_NAME) . '</span>'
                );
            } else {
                $return = array(
                    'success' => false,
                    'html' => '<span>' . __('Something went wrong!', CPBP_NAME) . '</span>'
                );
            }
        } else {
            $return = array(
                'success' => false,
                'html' => '<span>' . __('Something went wrong!', CPBP_NAME) . '</span>'
            );
        }

        echo json_encode($return);
        exit;
    }

    /*
     * AJAX
     */
    public static function cpbp_import()
    {
        if (!(isset($_REQUEST['action']) && 'cpbp_import' == $_POST['action']))
            return;

        CPBP::max_server_ini();

        if( $_POST['cpbp_import_type'] == 'invoices' ) {
            $additional_import = $_POST;
            $additional_import['cpbp_import_type'] = 'invoices_history';
            $return = CPBP::cpbp_import($additional_import);
        }
        $return = CPBP::cpbp_import($_POST);

        echo json_encode($return);
        exit;
    }


}