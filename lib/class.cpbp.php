<?php

global $CP_Sage;

class CPBP
{
    private static $initiated = false;
    public static $table_prefix = '';
    public static $user_data = array();
    public static $scopConfig = array();
    public static $module_options = array();
    public static $shipping_options = array();
    public static $page_endpoints = array();
    public static $deposit_types = array(
        'C' => array('type' => '_CASH', 'label' => 'Check', 'short' => 'CHK'),
        'R' => array('type' => '_CREDIT_CARD', 'label' => 'Credit Card', 'short' => 'CC'),
        'A' => array('type' => '_ACH', 'label' => 'ACH', 'short' => 'ACH'),
    );
    protected static $cpbp_product_id = '1';
    protected static $cpbp_cart_woo_key = 'cpbp_woo_persistent_cart_';
    public static $import_statuses = array(
        '0' => 'In Progress',
        '1' => 'Completed',
        '2' => 'Failed',
        '3' => 'Waiting for next functionality'
    );
    public static $import_run_results = array(
        '0' => 'Request In Progress',
        '1' => 'Request completed successfully',
        '2' => 'Request Failed',
        '3' => 'Request completed successfully, But we need to continue the functionality!!!'
    );

    public static $import_schedules = array(
        'cashreceipts' => array(
            'name'=>'cashreceipts',
            'interval'=>2,
            'before_run' => array(
                'cashreceipts_history'=>array('name'=> 'cashreceipts_history', 'type'=>'sync')
                //'invoices_trackings'=>array('name'=> 'invoices_trackings', 'type'=>'purge'),
                //'invoices_history'=>array('name'=> 'invoices_history', 'type'=>'sync'),
                //'invoices'=>array('name'=> 'invoices', 'type'=>'purge')
            ),
            'after_run' => array(),
            'type' => 'purge'
        ),
    );


    public static function init()
    {
        if (!self::$initiated) {
            self::init_hooks();
        }
        self::init_default_product();

    }

    /**
     * Initializes WordPress hooks
     */
    private static function init_hooks()
    {
        global $wpdb;
        self::$initiated = true;
        self::$table_prefix = CPBP_DB_PREFIX;
        self::$cpbp_cart_woo_key = 'cpbp_woo_persistent_cart_'. get_current_blog_id();
        global $CP_Sage, $CPBP_Sage, $cpbp_scope_cf, $cpbp_modules_cf, $cpbp_shipping_methods;

        if (!$CP_Sage) {
            //$scopConfig = $cpbp_scope_cf = self::$scopConfig = get_option(CPBP::get_settings_name(), true);
            /*$cpbp_modules_cf = self::$module_options = get_option(CPBP::get_settings_name('modules'), true);
            $cpbp_shipping_methods = self::$shipping_options = get_option(CPBP::get_settings_name('shipping_methods'), true);
            $CP_Sage = new CP_Sage($scopConfig);*/
        }
        if( !$CPBP_Sage ){
            $scopConfig = $cpbp_scope_cf = self::$scopConfig = get_option(CPBP::get_settings_name(), true);
            $CPBP_Sage = new CPBP_Sage($scopConfig);
        }

        self::$cpbp_product_id = get_option('cpbp_product_id', true);

        $cp_view_payments = true; //CPLINK::get_user_meta('cp_view_payments');
        if ($cp_view_payments) {
            $bill_payment_endpoints = array(
                'bill_payments', 'bill_payment'
            );
            if(CPBP::get_scopConfig('allow_make_payments')){
                array_push($bill_payment_endpoints,'create_bill_payment');
            }
        } else {
            $bill_payment_endpoints = array();
        }

        $page_endpoints = self::$page_endpoints = $bill_payment_endpoints;
        add_filter('query_vars', array('CPBP', 'cp_query_vars'), 0);

        //self::run_multicart();

        //add_filter('woocommerce_add_cart_item_data', array('CPBP', 'maybe_multicart'), 25, 2);
        //add_action( 'template_redirect', array('CPBP', 'run_multicart'), 0 );
        //add_action( 'wp_loaded', array( 'CPBP', 'run_multicart' ) );
        add_action( 'template_redirect', array( 'CPBP', 'run_multicart' ) );

        add_action('wp_enqueue_scripts', array('CPBP', 'load_resources'));
        add_action('wp_head', array('CPBP', 'wp_head'), 10, 2);

        add_filter('woocommerce_locate_template', array('CPBP', 'woo_plugin_template'), 100, 3);
        add_filter('woocommerce_account_menu_items', array('CPBP', 'customize_my_account_links'), 10, 1);

        add_filter('woocommerce_available_payment_gateways', array('CPBP', 'bp_invoice_available_payment_gateways'), 99, 1);
        add_filter('woocommerce_coupons_enabled', array('CPBP', 'hide_coupon_field_for_bill_pay'));
        //add_filter('woocommerce_add_cart_item_data', array('CPBP', 'add_cart_item_data'), 25, 2); //Pause maybe stop
        add_filter('woocommerce_before_calculate_totals', array('CPBP', 'bp_recalculate_price'), 25, 2);
        add_filter('wc_add_to_cart_message_html', array('CPBP', 'bp_invoice_wc_add_to_cart_message_html'), 10, 1);
        add_filter('woocommerce_cart_product_cannot_add_another_message', array('CPBP', 'bp_invoice_wc_add_to_cart_message_html'), 10, 1);
        add_filter('woocommerce_is_sold_individually', array('CPBP', 'bp_invoice_remove_quantity_fields'), 10, 2);
        add_filter('woocommerce_cart_item_permalink', array('CPBP', 'bp_invoice_cart_item_permalink'), 10, 3);

        add_action('woocommerce_add_order_item_meta', array('CPBP', 'bp_add_order_item_meta'), 10, 2);

        add_action('wp_ajax_cpbp_create_bill_pay_cart', array('CPBP', 'cpbp_create_bill_pay_cart') );
        add_action('wp_ajax_nopriv_cpbp_create_bill_pay_cart', array('CPBP', 'cpbp_create_bill_pay_cart') );
        add_action('wp_ajax_cpbp_create_bill_pay', array('CPBP', 'cpbp_create_bill_pay') );
        add_action('wp_ajax_nopriv_cpbp_create_bill_pay', array('CPBP', 'cpbp_create_bill_pay') );
        add_action('wp_ajax_cpbp_create_bill_pay_deposit', array('CPBP', 'cpbp_create_bill_pay_deposit') );
        add_action('wp_ajax_nopriv_cpbp_create_bill_pay_deposit', array('CPBP', 'cpbp_create_bill_pay_deposit') );

        add_action('wp_ajax_cpbp_get_invoices', array('CPBP', 'cpbp_get_invoices') );
        add_action('wp_ajax_nopriv_cpbp_get_invoices', array('CPBP', 'cpbp_get_invoices') );

        add_action('wp_ajax_cpbp_remove_from_cart', array('CPBP', 'cpbp_remove_from_cart') );
        add_action('wp_ajax_nopriv_cpbp_remove_from_cart', array('CPBP', 'cpbp_remove_from_cart') );

        add_action( 'woocommerce_checkout_order_processed', array('CPBP', 'create_bill_pay_order'), 1, 3);
        add_filter( 'woocommerce_payment_successful_result', array('CPBP', 'sanitize_bill_pay_successful_result'), 1, 2 );
        add_action( 'woocommerce_thankyou', array('CPBP', 'sanitize_wc_thank_you_page'), 10, 1 );
        add_filter( 'woocommerce_email_recipient_new_order', array('CPBP', 'sanitize_bill_pay_email_recipient'), 10, 3 );
        add_filter( 'woocommerce_email_recipient_customer_on_hold_order', array('CPBP', 'sanitize_bill_pay_email_recipient'), 10, 3 );
        add_filter( 'woocommerce_email_recipient_customer_processing_order', array('CPBP', 'sanitize_bill_pay_email_recipient'), 10, 3 );
        add_filter( 'woocommerce_email_recipient_customer_pending_order', array('CPBP', 'sanitize_bill_pay_email_recipient'), 10, 3 );


        if ( isset($_GET['bill_checkout']) || is_checkout() ) {
            $cpbp_product_id = self::$cpbp_product_id;
            if (!WC()->cart->is_empty()) {
                foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                    if ($cart_item['product_id'] == $cpbp_product_id) {
                        $is_deposit = false;
                        /*i_print($cart_item);*/
                        //bp_product_data
                        if($cart_item['bp_product_data']['bp_invoice_type'] == 'PP'){
                            $is_deposit = true;
                        }
                        add_filter('gettext', array('CPBP', 'bill_pay_order_text'), 20);
                    }
                }
            }
        }

        $cp_inactive_user = CPLINK::get_user_meta('cp_inactive_user');

        if ($cp_inactive_user) {
            add_action('get_header', array('CPBP', 'sage_logout_inactive_user'), 10);
            add_filter('login_message', array('CPBP', 'sage_inactive_login_message'), 10);
        }

        foreach ($page_endpoints as $page_endpoint) {
            add_rewrite_endpoint($page_endpoint, EP_PAGES);
            add_action('woocommerce_account_' . $page_endpoint . '_endpoint', array('CPBP', 'account_' . $page_endpoint . '_endpoint_content'), 10, 1);
        }

        self::schedule_cron_jobs();
        //self::cpbp_run_import('cashreceipts'); exit;
        //if( defined('DOING_CRON') && DOING_CRON )
        //add_action('cplink_schedule_import', array('CPLINK', 'cplink_run_import'));

        $import_schedules = self::$import_schedules;

        foreach ($import_schedules as $import_schedule){

            $schedule_name = 'cplink_schedule_import_'.$import_schedule['name'];
            //$schedule_fn_name = 'cplink_run_import_'.$import_schedule['name'];

            add_action($schedule_name, array('CPBP', 'cpbp_run_import'), 10, 1);
        }

        add_action('cpbp_schedule_export', array('CPBP', 'cpbp_run_export'));

        $settings_name = self::get_settings_name();
    }

    /**
     * creating product for bill pay
     **/
    public static function init_default_product(){

        $posts = get_posts(array(
            'numberposts'   => -1,
            'post_type'     => 'product',
            'meta_key'      => 'cplink_bilpay_product',
            'meta_value'    => '1'
        ));
        if(count($posts) > 0){
            $default_product_id = get_option('cpbp_product_id',true);
            if(empty($default_product_id)){
                $default_product_id = $posts[0]->ID;
                update_option('cpbp_product_id',$default_product_id);
            }elseif($posts[0]->ID != $default_product_id){
                $default_product_id = $posts[0]->ID;
                update_option('cpbp_product_id',$default_product_id);
            }
        }else{
            $post = array(
                'post_status' => "publish",
                'post_title' => 'CPBP Product',
                'post_type' => "product",
            );

            //Create post
            $post_id = wp_insert_post( $post );
            if($post_id){
                update_post_meta( $post_id, 'cplink_bilpay_product', '1' );
                update_post_meta( $post_id, '_stock_status', 'instock');
                update_post_meta( $post_id, '_regular_price', "1" );
                update_post_meta( $post_id, '_price', "1" );
                update_post_meta( $post_id, '_virtual', "1" );
                update_option('cpbp_product_id',$post_id,true);
            }
        }

    }


    public static function run_multicart(){
        if( isset( $_REQUEST['wc-ajax'] ) || wp_doing_ajax() )
            return false;

        if ( is_admin() || defined('DOING_AJAX') || isset( $_GET['wc-ajax'] )
            || isset( $_GET['bill_checkout'] ) || isset( $_GET['wc-bill_checkout'] )
        ) // $_GET['wc-bill_checkout']
            return false;

        if ( is_checkout() || is_404() )
            return false;

        if( strpos($_SERVER['REQUEST_URI'], 'create_bill_payment') !== false )
            return false;

        /*$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        update_option('tufta_link', $actual_link);
        update_option('tufta_ip', $_SERVER['REMOTE_ADDR']);*/
        /*
         * For Dev/Debug
         * $cpbp_history_url = get_option('cpbp_history_url');
        $actual_link = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        update_option('cpbp_history_url', $cpbp_history_url.','.$actual_link);*/

        $cpbp_product_id = self::$cpbp_product_id;

        $have_billpay = false;
        if (!WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if ($cart_item['product_id'] == $cpbp_product_id) {
                    $have_billpay = true;
                }
            }
        } else {
            $have_billpay = true;
        }
        if( $have_billpay ) {
            self::restore_woo_cart();
        }
    }

    public static function save_woo_cart(){
        global $wp_query; //i_print($wp_query);
        $cpbp_product_id = self::$cpbp_product_id;
        $current_blog_id = get_current_blog_id();
        $cpbp_cart_woo_key = self::$cpbp_cart_woo_key;
        $user_id = get_current_user_id();

        $have_billpay = false;
        if (!WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if ($cart_item['product_id'] == $cpbp_product_id) {
                    $have_billpay = true;
                }
            }
        }

        if( !$have_billpay ){
            $woo_persistent_cart = get_user_meta($user_id, '_woocommerce_persistent_cart_'. $current_blog_id, true);
            update_user_meta($user_id, $cpbp_cart_woo_key, $woo_persistent_cart);
            //i_print($woo_persistent_cart); exit;
        }
    }

    public static function restore_woo_cart()
    {
        //WC_Form_Handler::update_cart_action();
        //do_action('woocommerce_update_cart_action_cart_updated');
        //WC()->cart->persistent_cart_update(); // 2
        //do_action('update_cart_action'); // 3
        //return;
        $user_id = get_current_user_id();
        $current_blog_id = get_current_blog_id();
        $cpbp_cart_woo_key = self::$cpbp_cart_woo_key;
        $saved_cart = get_user_meta($user_id, $cpbp_cart_woo_key, true);

        if( $saved_cart && isset($saved_cart['cart']) ){
            $cart_data = $saved_cart['cart'];
            if ( $cart_data ) {
                WC()->cart->empty_cart();

                foreach ( $cart_data as $product ) {

                    // Validate Product data
                    $product_id   = isset( $product['product_id'] )    ? (int) $product['product_id']   : 0;
                    $quantity     = isset( $product['quantity'] )      ? (int) $product['quantity']     : 1;
                    $variation_id = isset( $product['variation_id'] )  ? (int) $product['variation_id'] : 0;
                    $variation    = isset( $product['variation'] )     ? $product['variation']          : array();

                    WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );
                }
                WC()->cart->calculate_totals();
            }
            update_user_meta($user_id, $cpbp_cart_woo_key, '');
            //WC()->session->destroy_cart_session();
            //update_user_meta($user_id, '_woocommerce_persistent_cart_'. $current_blog_id, $saved_cart);
            //$saved_cart = get_user_meta( $user->ID, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
            //WC()->session->cart = $saved_cart['cart'];
            /*WC()->session->set( 'cart', $saved_cart['cart'] );
            $cpbp_product_id = self::$cpbp_product_id;
            WC()->cart->add_to_cart($cpbp_product_id, 1);*/
            //WC_Form_Handler::update_cart_action();
            //WC()->cart->calculate_totals();
            //do_action('woocommerce_after_calculate_totals');
            //WC()->cart->persistent_cart_update(); // 2
            //do_action('update_cart_action'); // 3
        } else {
            if (!WC()->cart->is_empty()) {
                WC()->cart->empty_cart();
                WC()->cart->calculate_totals();
                header("Location: ".$_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }

    public static function max_server_ini( $max_execution_time = 0, $memory_limit = '2048M' )
    {
        ini_set('max_execution_time', $max_execution_time);
        ini_set('memory_limit', $memory_limit);
    }

    public static function get_scopConfig($option_name)
    {
        if ( !is_array(self::$scopConfig) || !count(self::$scopConfig)) {
            self::$scopConfig = get_option(CPBP::get_settings_name(), true);
        }
        $scopConfig = self::$scopConfig;
        if (isset($scopConfig[$option_name]))
            return $scopConfig[$option_name];

        return false;
    }

    public static function get_module_option($module_name)
    {
        if (!count(self::$module_options)) {
            self::$module_options = get_option(CPBP::get_settings_name('modules'), true);
        }
        $module_options = self::$module_options;
        if (isset($module_options[$module_name]))
            return $module_options[$module_name];

        return false;
    }

    public static function plugin_activation()
    {
        global $wpdb;
        global $table_prefix;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_prefix = CPBP_DB_PREFIX;

        require_once(CPBP_PLUGIN_DIR . 'lib/data/dbDelta.php');

        update_option('cpbp_db_version', CPBP_DB_VERSION);
    }

    /**
     * Removes all connection options
     * @static
     */
    public static function plugin_deactivation()
    {

    }


    public static function schedule_cron_jobs()
    {
        $import_schedules = self::$import_schedules;
        // 'hourly', 'daily', and 'twicedaily'.
        //wp_clear_scheduled_hook( 'cplink_schedule_import' );

        /*if (!wp_next_scheduled('cplink_schedule_import'))
            wp_schedule_event(time(), 'cplink_10_minute', 'cplink_schedule_import');*/

        /*if (!wp_next_scheduled('cplink_schedule_export'))
            wp_schedule_event(time(), 'daily', 'cplink_schedule_export');*/

        foreach ($import_schedules as $import_module => $import_schedule){
            $schedule_name = 'cplink_schedule_import_'.$import_schedule['name'];

            $schedule_interval = $import_schedule['interval'];
            if (!wp_next_scheduled($schedule_name, array($import_module)))
                wp_schedule_event(time(), 'cplink_'.$schedule_interval.'_minute', $schedule_name, array($import_module) );
        }
    }

    public static function load_resources()
    {
        //CPBP_PLUGIN_URL.'resources/style/admin_style.css
        wp_enqueue_style('cplink_ui_style', CPLINK_PLUGIN_URL . 'resources/js/datepicker/jquery-ui.css', array(), CPLINKVersion, 'all');
        wp_enqueue_script('cplink_ui_js', CPLINK_PLUGIN_URL . 'resources/js/datepicker/jquery-ui.js', array('jquery'), CPLINKVersion, true);
        wp_enqueue_style('cpbp_fancybox_style', CPBP_PLUGIN_URL . 'resources/js/fancybox/jquery.fancybox.min.css', array(), CPBPVersion, 'all');
        wp_enqueue_style('cpbp_style', CPBP_PLUGIN_URL . 'resources/style/front_style.css', array(), CPBPVersion, 'all');
        //wp_enqueue_style('cpbp_responsive_style', CPBP_PLUGIN_URL . 'resources/style/front-responsive.css', array(), CPBPVersion, 'all');
        wp_enqueue_script('cpbp_fancybox_script', CPBP_PLUGIN_URL . 'resources/js/fancybox/jquery.fancybox.min.js', array('jquery'), CPBPVersion, true);
        wp_enqueue_script('cpbp_front_script', CPBP_PLUGIN_URL . 'resources/js/front_js.js', array('jquery'), CPBPVersion, true);
        wp_enqueue_script('cpbp_front2_script', CPBP_PLUGIN_URL . 'resources/js/front2_js.js', array('jquery'), CPBPVersion, true);

        global $woocommerce;
        $checkout_page_url = function_exists( 'wc_get_cart_url' ) ? wc_get_checkout_url() : $woocommerce->cart->get_checkout_url();

        wp_localize_script( 'cpbp_front_script', 'cpbp_infos',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'checkout_page_url' => $checkout_page_url
            )
        );
    }
    public static function wp_head()
    {
        if( isset($_GET['bill_checkout']) ){
            wp_enqueue_style('cpbp_style_bill_checkout', CPBP_PLUGIN_URL . 'resources/style/front_bill_checkout_style.css', array(), CPBPVersion, 'all');
        }
    }

    public static function register_cpt_()
    {
    }

    public static function generate_bill_button($map_id = '')
    {
        $cpbp_product_id = self::$cpbp_product_id;

        if (!$map_id)
            $map_id = get_the_ID();

        $add_cart_url = (function_exists('wc_get_checkout_url')) ? wc_get_checkout_url() : ''; //wc_get_checkout_url //wc_get_cart_url
        echo '<a href="' . $add_cart_url . '?add-to-cart=' . $cpbp_product_id . '&bp_invoice_id=' . $map_id . '" ';
        echo ' class="woocommerce-button view cplink_toggle_btn"> ' . __('Pay', 'woocommerce') . '</a>';
    }


    public static function get_settings_name($settings_tab = '')
    {
        $settings_name = CPBP_SETTINGS_NAME;

        if ($settings_tab == 'general')
            $settings_tab = '';

        if ($settings_tab)
            $settings_name .= '-' . $settings_tab;

        return $settings_name;
    }

    public static function cp_query_vars($vars)
    {
        $vars[] = 'bill_payments';
        return $vars;
    }


    /*
     * Woo Functionality
     */


    public static function bill_pay_order_text($changed_text){
        $chekout_label = 'Invoices';
        if(isset($_GET['deposit'])){
            $chekout_label = 'Deposit';
        }
        $text = array(
            'Your order' => $chekout_label,
            'product' => 'Invoice'
        );
        $changed_text = str_ireplace(  array_keys($text),  $text,  $changed_text );
        return $changed_text;
    }

    public static function hide_coupon_field_for_bill_pay($enabled)
    {
        if (is_admin() && !defined('DOING_AJAX'))
            return $enabled;

        $cpbp_product_id = self::$cpbp_product_id;
        $cart = WC()->cart->get_cart();
        foreach ($cart as $id => $cart_item) {
            if ($cart_item['data']->get_id() == $cpbp_product_id) {
                return false;
            }
        }
        return $enabled;
    }

    public static function add_cart_item_data($cart_item_meta, $product_id)
    {
        $cpbp_product_id = self::$cpbp_product_id;

        if (isset($_REQUEST ['bp_invoice_id'])) {
            //Empty Cart for this case
            /*if (!WC()->cart->is_empty())
                WC()->cart->empty_cart();*/

            $bp_invoice_data = array();
            if (isset($_REQUEST ['bp_invoice_id'])) {
                $bp_invoice_id = $_REQUEST ['bp_invoice_id'];
                $invoice_item = CPLINK::get_sage_invoice($bp_invoice_id, '*');

                $invoice_no = $invoice_item['invoice_no'];
                $header_seq_no = $invoice_item['header_seq_no'];
                //i_print($invoice_item); exit;
                //$br_map_title = get_the_title($bp_invoice_id);
                add_filter('private_title_format', function ($format) { //Remove Private prefix text from title if post is private
                    return '%s';
                });

                $brm_inventory_number = get_post_meta($bp_invoice_id, 'brm_inventory_number', true);
                $invoice_title = '' . $invoice_no; //Invoice N
                $bp_invoice_data ['bp_invoice_title'] = sanitize_text_field($invoice_title);
                $bp_invoice_data ['bp_invoice_id'] = sanitize_text_field($bp_invoice_id);
                $bp_invoice_data ['bp_invoice_no'] = sanitize_text_field($invoice_no);
                $bp_invoice_data ['bp_header_seq_no'] = sanitize_text_field($header_seq_no);
                $cart_item_meta ['bp_product_data'] = $bp_invoice_data;

                $_SESSION['bp_invoice_id'] = $bp_invoice_id;
                $_SESSION['bp_invoice_no'] = $invoice_no;
                $_SESSION['bp_invoice_title'] = $invoice_title;
            }
        } else {
            if( !WC()->cart->is_empty() ){
                foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                    if ( $cart_item['product_id'] == $cpbp_product_id ) {
                        WC()->cart->remove_cart_item( $cart_item_key );
                    }
                }
            }
        }

        return $cart_item_meta;
    }

    public static function bp_recalculate_price($cart_object)
    {

        if (is_admin() && !defined('DOING_AJAX'))
            return;

        //i_print( $cart_object->get_cart() ); exit;

        foreach ($cart_object->get_cart() as $hash => $value) {

            if ( isset($value['bp_product_data']) && CPLINK::i_real_set($value['bp_product_data']) ) {
                $bp_product_data = $value['bp_product_data'];
                $bp_invoice_no = $bp_product_data['bp_invoice_no'];

                //$invoice_item = CPLINK::get_sage_invoice($bp_invoice_id, '*');
                $product_price = $bp_product_data['bp_invoice_amount'];

                if ($bp_product_data['bp_invoice_title'])
                    $value['data']->set_name($bp_product_data['bp_invoice_title']);

                if ($product_price) {
                    $value['data']->set_price($product_price);
                }
            }
        }
    }

    public static function bp_add_order_item_meta($item_id, $values)
    {

        if (isset($values ['bp_product_data'])) {
            $custom_data = $values ['bp_product_data'];
            wc_add_order_item_meta($item_id, 'cplink_bilpay_order', $custom_data['bp_invoice_id']); //Item ID
            wc_add_order_item_meta($item_id, 'Invoice Title', $custom_data['bp_invoice_title']);
            wc_add_order_item_meta($item_id, 'Invoice No', $custom_data['bp_invoice_no']);
            wc_add_order_item_meta($item_id, 'invoice_type', $custom_data['bp_invoice_type']);
        }
    }

    public static function bp_invoice_wc_add_to_cart_message_html($message)
    {
        if (isset($_SESSION['bp_invoice_title'])) {
            $message = str_replace('CPBP Product', $_SESSION['bp_invoice_title'], $message);
            unset($_SESSION['bp_invoice_title']);
        }

        return $message;
    }

    public static function bp_invoice_remove_quantity_fields($return, $product)
    {
        $cpbp_product_id = self::$cpbp_product_id;
        if ($product->get_id() == $cpbp_product_id)
            return true;
    }

    public static function bp_invoice_cart_item_permalink($permalink, $cart_item, $cart_item_key)
    {
        if (is_cart()) {
            if ( isset($cart_item['bp_product_data']) && CPLINK::i_real_set($cart_item['bp_product_data'])) {
                $br_map_data = $cart_item['bp_product_data'];
                $permalink = '#';
            }
        }
        return $permalink;
    }

    public
    static function bp_invoice_available_payment_gateways($available_gateways)
    {
        if ( is_admin() )
            return $available_gateways;

        $cpbp_product_id = self::$cpbp_product_id;


        $only_billpay = false;
        if (!WC()->cart->is_empty()) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if ($cart_item['product_id'] == $cpbp_product_id) {
                    $only_billpay = true;
                }
            }
        }

        if( $only_billpay ){
            $invoice_available_gateways = CPBP::get_scopConfig('active_payment_methods'); //array('sagepaymentsusaapi', 'zeamster');
            if (!is_admin() && $available_gateways) {
                foreach ($available_gateways as $gateway_key => $gateway) {
                    if (!in_array($gateway_key, $invoice_available_gateways)) {
                        unset($available_gateways[$gateway_key]);
                    }
                }
            }
        }

        return $available_gateways;
    }


    public static function woo_plugin_template($template, $template_name, $template_path)
    {
        /*if( !self::get_module_option('sales_order_enabled') ) { //
            if( strpos($template_name, 'myaccount/') !== false || strpos($template_name, 'order/') !== false )
                return $template;
        }*/

        global $woocommerce;
        $_template = $template;
        if (!$template_path)
            $template_path = $woocommerce->template_url;

        $plugin_path = CPBP_PLUGIN_DIR . '/templates/woocommerce/';

        // Look within passed path within the theme - this is priority
        $template = locate_template(
            array(
                $template_path . $template_name,
                $template_name
            )
        );

        if (!$template && file_exists($plugin_path . $template_name))
            $template = $plugin_path . $template_name;
        if (!$template)
            $template = $_template;

        return $template;
    }

    public static function wc_page_endpoint_title($title)
    {
        global $wp_query, $wp;

        if (!is_null($wp_query) && !is_admin() && is_main_query() && is_page() && in_the_loop()) {
            $title = self::wc_endpoint_title($title);
        }

        return $title;
    }

    public static function wc_endpoint_title($title)
    {
        global $wp_query, $wp;

        if (is_wc_endpoint_url()) { //self::get_module_option('sales_order_enabled') &&
            $endpoint = WC()->query->get_current_endpoint(); //echo '--'.$endpoint.'--';
            if ($endpoint == 'view-order') {
                $title = 'Order';
                $order_n = '';
                $order_id = $wp->query_vars['view-order'];
                $order = wc_get_order($order_id);
                if ($order) {
                    $order_n = $order->get_order_number();
                    $cp_sales_order_number = $order->get_meta('cp_sales_order_number'); //$cp_sales_order_number = get_post_meta($order_id, 'cp_sales_order_number', true);

                    if ($cp_sales_order_number) {
                        $title = 'Sales Order';
                        $order_n = $cp_sales_order_number;
                    }
                }
                /* translators: %s: order number */
                $endpoint_title = sprintf(__($title . ' #%s', 'woocommerce'), $order_n);
                $title = $endpoint_title ? $endpoint_title : $title;

                remove_filter('the_title', 'wc_page_endpoint_title');
            }
        }

        if (isset($wp_query->query_vars['bill_payments'])) {
            $title = 'Payments';
            $bill_payment_n = '';

            $bill_payment_n = get_query_var('bill_payments');

            if ($bill_payment_n) {
                $title = 'Payment';
                $bill_payment_item = self::get_sage_bill_payment($bill_payment_n, 'bill_payment_no');
                if (count($bill_payment_item))
                    $bill_payment_n = $bill_payment_item['bill_payment_no'];
                $endpoint_title = sprintf(__($title . ' #%s', 'woocommerce'), $bill_payment_n);
            }
            $title = $endpoint_title ? $endpoint_title : $title;

            remove_filter('the_title', 'wc_page_endpoint_title');
        }

        return $title;
    }

    public static function customize_my_account_links($menu_links)
    {
        $pos = 6;

        $cp_view_bill_payment = true; //CPLINK::get_user_meta('cp_view_bill_payment');
        if ($cp_view_bill_payment) { //self::get_module_option('bill_payments_enabled')
            $new = array('bill_payments' => 'My Payments');
            $menu_links = array_slice($menu_links, 0, $pos, true)
                + $new
                + array_slice($menu_links, $pos, NULL, true);
        }
        return $menu_links;
    }

    public static function account_bill_payments_endpoint_content($paged)
    {
        global $wp_query;

        $paged = get_query_var('bill_payments');
        $page = ($paged && is_numeric($paged)) ? absint($paged) : 1;
        $items_per_page = 10;
        $user_id = get_current_user_id();
        if (CPLINK::isset_return($_GET, 'user_id')) {
            $user_id = $_GET['user_id'];
        }

        $bill_payments = array();
        //if( !isset( $_GET['bp_action'] ) ){
            $bill_payments = self::get_cpbp_orders();
        //}

        //$bill_payments_pending = self::get_sage_bill_payments(false, $paged, $items_per_page);
        $bill_payments_history = self::get_sage_bill_payments(true, $paged, $items_per_page);
        //$bill_payments_history = self::get_sage_bill_payments_history($paged, $items_per_page);
        //$bill_payments_history = array_merge($bill_payments, $bill_payments_history);
        //$total = ceil( count( self::get_sage_bill_payments(1, -1, 'id') ) / $items_per_page );

        //$bill_payments = CPLINK::get_sage_invoices($paged, $items_per_page);
        $total = ceil(count(CPLINK::get_sage_invoices(1, -1, 'id')) / $items_per_page);
        echo '<div class="cp_bill_payments_div">';
        wc_get_template(
            'sage/bill_payments/index.php', array('bill_payments' => $bill_payments, 'bill_payments_history' => $bill_payments_history)
        ); //require_once(CPBP_PLUGIN_DIR . 'templates/woocommerce/sage/bill_payments/index.php');

        wc_get_template(
            'sage/pagination.php', array('base' => esc_url(wc_get_endpoint_url('bill_payments')), 'total' => $total, 'page' => $page, 'items_per_page' => $items_per_page)
        );
        echo '</div>';
    }

    public static function account_bill_payment_endpoint_content($bill_payment_id)
    {
        global $wp_query;
        //$bill_payment = get_query_var( 'bill_payment' );
        echo '<div class="cp_bill_payments_div cpbp_print_area">';
        wc_get_template(
            'sage/bill_payments/single.php', array('bill_payment_id' => $bill_payment_id)
        ); //require_once(CPBP_PLUGIN_DIR . 'templates/woocommerce/sage/bill_payments/single.php');
        echo '<div>';
    }

    public static function account_create_bill_payment_endpoint_content()
    {
        global $wp_query;
        echo '<div class="cp_bill_payments_div cpbp_print_area">';
        if( isset($_GET['bill_checkout']) ){
            echo '<style type="text/css">li.cart-item,li.cart-item+.header-divider  {display: none;}</style>';
            wc_get_template(
                'sage/bill_payments/checkout.php'
            );
        } else {
            wc_get_template(
                'sage/bill_payments/create-payment.php'
            );
        }

        echo '<div>';
    }


    public
    static function get_cpbp_order($cpbp_order_id)
    {
        global $wpdb;
        $cpbp_order = array();
        $user_id = get_current_user_id();
        $table_prefix = self::$table_prefix;
        $queue_table_name = $table_prefix . 'queue';
        $ar_division_number = CPLINK::get_user_meta('cp_ar_division_no'); //'01';//
        $customer_number = CPLINK::get_user_meta('cp_customer_no'); //'0000007'; //'ABF'; //
        $cash_receipts_table = $table_prefix . 'cash_receipts';
        //_customer_user

        $sql = "SELECT posts.ID, posts.post_date, postmeta2.meta_value AS total_amount, postmeta3.meta_value as payment_data";

        $sql.= " FROM $wpdb->posts AS posts";
        $sql.= " INNER JOIN $wpdb->postmeta AS postmeta ON posts.ID = postmeta.post_id";
        $sql.= " INNER JOIN $wpdb->postmeta AS postmeta2 ON posts.ID = postmeta2.post_id";
        $sql.= " INNER JOIN $wpdb->postmeta AS postmeta3 ON posts.ID = postmeta3.post_id";

        //Alternative individual get Order Items Sum
        //$sql.= ', SUM(woo_order_itemmeta.`meta_value`) AS total_amount';
        //$sql.= " INNER JOIN {$wpdb->prefix}woocommerce_order_items AS woo_order_items ON posts.ID = woo_order_items.order_id";
        //$sql.= " INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS woo_order_itemmeta ON woo_order_items.order_item_id = woo_order_itemmeta.order_item_id";
        //$sql.= " WHERE posts.post_type = 'cpbp_order' AND postmeta.meta_key = '_customer_user' AND  postmeta.meta_value = $user_id";
        //AND woo_order_itemmeta.meta_key = '_line_total'";

        $sql.= " WHERE posts.ID = $cpbp_order_id AND posts.post_type = 'cpbp_order' 
        AND postmeta.meta_key = '_customer_user' 
        AND postmeta.meta_value = $user_id
        AND postmeta2.meta_key = '_order_total'
        AND postmeta3.meta_key = '_sageresult'
        AND posts.ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'cp_sales_order_number')
        AND posts.ID IN (SELECT `external_cash_number` FROM $queue_table_name WHERE `active` = 1)";

        $sql.= " GROUP BY posts.ID";
        $sql.= " ORDER BY posts.post_date"; //ORDER BY `deposit_date` DESC, `deposit_number` DESC

        //echo $sql;

        $cpbp_orders = $wpdb->get_results($sql, ARRAY_A);

        $bill_payments = self::get_sage_bill_payments(false, 1, 0, '*', ' AND `external_cash_number`='.$cpbp_order_id.' ');
        $bill_payments_ext_ids = array();
        if( count($bill_payments) ){
            foreach ($bill_payments as $bill_payment_key => $bill_payment){
                $bill_payments_ext_ids[$bill_payment_key] = $bill_payment['external_cash_number'];
            }
        } //i_print($bill_payments); echo 'ok';

        if( count($cpbp_orders) ) {
            $cpbp_order = $cpbp_orders[0];
        } else {
            $cpbp_order = $bill_payments[0];
        }

        if( $cpbp_order ){
            $payment_data = maybe_unserialize($cpbp_order['payment_data']);
            if( is_array($payment_data) && count($payment_data) ){
                $cpbp_order['deposit_number'] = ''; //$payment_data['code'];
                $cpbp_order['credit_card_entry_number'] = ''; //$payment_data['reference'];
            }
        }
        return $cpbp_order;
    }
    public
    static function get_cpbp_orders($paged = 1, $items_per_page = -1, $select_what = '*')
    {
        global $wpdb;
        if (!is_numeric($paged))
            $paged = 1;
        $user_id = get_current_user_id();
        $offset = ($paged - 1) * $items_per_page;
        $table_prefix = self::$table_prefix;
        $queue_table_name = $table_prefix . 'queue';
        $ar_division_number = CPLINK::get_user_meta('cp_ar_division_no'); //'01';//
        $customer_number = CPLINK::get_user_meta('cp_customer_no'); //'0000007'; //'ABF'; //
        $cash_receipts_table = $table_prefix . 'cash_receipts';
        //_customer_user

        $sql = "SELECT posts.ID, posts.post_date, postmeta2.meta_value AS total_amount, postmeta3.meta_value as payment_data";

        $sql.= " FROM $wpdb->posts AS posts";
        $sql.= " INNER JOIN $wpdb->postmeta AS postmeta ON posts.ID = postmeta.post_id";
        $sql.= " INNER JOIN $wpdb->postmeta AS postmeta2 ON posts.ID = postmeta2.post_id";
        $sql.= " INNER JOIN $wpdb->postmeta AS postmeta3 ON posts.ID = postmeta3.post_id";

        //Alternative individual get Order Items Sum
        //$sql.= ', SUM(woo_order_itemmeta.`meta_value`) AS total_amount';
        //$sql.= " INNER JOIN {$wpdb->prefix}woocommerce_order_items AS woo_order_items ON posts.ID = woo_order_items.order_id";
        //$sql.= " INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS woo_order_itemmeta ON woo_order_items.order_item_id = woo_order_itemmeta.order_item_id";
        //$sql.= " WHERE posts.post_type = 'cpbp_order' AND postmeta.meta_key = '_customer_user' AND  postmeta.meta_value = $user_id";
        //AND woo_order_itemmeta.meta_key = '_line_total'";

        $sql.= " WHERE posts.post_type = 'cpbp_order' 
        AND postmeta.meta_key = '_customer_user' 
        AND postmeta.meta_value = $user_id
        AND postmeta2.meta_key = '_order_total'
        AND postmeta3.meta_key = '_sageresult'
        AND posts.ID NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'cp_sales_order_number')
        AND posts.ID IN (SELECT `external_cash_number` FROM $queue_table_name WHERE `active` = 1)";

        $sql.= " GROUP BY posts.ID";
        $sql.= " ORDER BY posts.post_date DESC"; //ORDER BY `deposit_date` DESC, `deposit_number` DESC

        //echo $sql;

        $cpbp_orders = $wpdb->get_results($sql, ARRAY_A);


        $bill_payments = self::get_sage_bill_payments(false, 1, 0); //, 'external_cash_number'
        $bill_payments_ext_ids = array();
        if( count($bill_payments) ){
            foreach ($bill_payments as $bill_payment_key => $bill_payment){
                $bill_payments_ext_ids[$bill_payment_key] = $bill_payment['external_cash_number'];
            }
        }

        if( count($cpbp_orders) ){
            foreach ($cpbp_orders as $cpbp_order_id => $cpbp_order){
                /*if( in_array($cpbp_order['ID'], $bill_payments_ext_ids) ) {
                    unset($cpbp_orders[$cpbp_order_id]);
                    continue;
                }*/
                $payment_data = maybe_unserialize($cpbp_order['payment_data']);
                if( is_array($payment_data) && count($payment_data) ){
                    $cpbp_orders[$cpbp_order_id]['deposit_number'] = ''; //$payment_data['code'];
                    $cpbp_orders[$cpbp_order_id]['credit_card_entry_number'] = ''; //$payment_data['reference'];
                }
                //i_print($payment_data);
            }
        }

        $cpbp_orders = array_merge($cpbp_orders, $bill_payments);

        return $cpbp_orders;
    }

    public
    static function get_sage_bill_payments($history = false, $paged = 1, $items_per_page = 10, $select_what = '*', $where_q = '')
    {
        global $wpdb;
        if (!is_numeric($paged))
            $paged = 1;
        $offset = ($paged - 1) * $items_per_page;
        $table_prefix = self::$table_prefix;
        $ar_division_number = CPLINK::get_user_meta('cp_ar_division_no'); //'01';//
        $customer_number = CPLINK::get_user_meta('cp_customer_no'); //'0000007'; //'ABF'; //
        $cash_receipts_table = $table_prefix . 'cash_receipts';
        if( $history ) {
            $cash_receipts_table = $cash_receipts_table . '_history';
        }
        $bill_payment_lines_table = $table_prefix . 'cash_receipt_lines';
        $join_sql = '';
        if( $select_what == '*' ) {
            //$select_what = 'lines.total_amount';
            //$join_sql = 'INNER JOIN '.$bill_payment_lines_table.' lines ON (m.userid1 = u1.userid)';
            //$bill_payment_lines_table
            $select_what.= ', (SELECT SUM(`amount_posted`)
                FROM '.$bill_payment_lines_table.' WHERE `deposit_number`='.$cash_receipts_table.'.deposit_number';
            $select_what.= ' AND `ar_division_number` = '.$cash_receipts_table.'.ar_division_number AND `customer_number` = '.$cash_receipts_table.'.customer_number)';
            $select_what.= ' AS total_amount';
        }

        $sql = "SELECT $select_what FROM $cash_receipts_table ";
        $where = " WHERE `ar_division_number` = '$ar_division_number' AND LOWER(`customer_number`) = LOWER('$customer_number')";
        $where.= $where_q;

        if (isset($_GET['bp_action']) && $_GET['bp_action'] == 'search') {
            foreach ($_GET as $search_key => $search_val) {
                if (isset($search_val) && !empty($search_val)) {
                    switch ($search_key) {
                        case 'bp_confirmation':
                            $where .= " AND `external_cash_number` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'bp_reference':
                            $where .= " AND `credit_card_entry_number` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'bp_deposit':
                            $where .= " AND `deposit_number` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'bp_invoice':
                            $where .= " AND `invoice_number` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'item_code':
                            $where .= " AND `bill_payment_no` IN (";
                            $where .= "SELECT $bill_payment_lines_table.`bill_payment_no` FROM $bill_payment_lines_table WHERE `" . $search_key . "` LIKE '%" . $search_val . "%'  ";
                            $where .= ")";
                            break;

                        //balance
                        case 'bp_date_from':
                            if( $search_val ) {
                                $search_val = date('Y-m-d H:i:s', strtotime($search_val));
                                $where .= " AND `deposit_date` >= '" . $search_val . "'";
                            }

                            break;
                        case 'bp_date_to':
                            if( $search_val ) {
                                $search_val = date('Y-m-d H:i:s', strtotime( $search_val) );
                                $where .= " AND `deposit_date` <= '" . $search_val."'";
                            }
                            break;

                        //total
                        case 'min_total':
                            $search_val = 0 + $search_val;
                            $where .= " AND `total` >= " . $search_val;

                            break;
                        case 'max_total':
                            $search_val = 0 + $search_val;
                            $where .= " AND `total` <= " . $search_val;
                            break;
                    }
                }
            }
        }
        $sql .= $where;
        $sql .= " ORDER BY `deposit_date` DESC, `deposit_number` DESC";//echo $sql;
        if ($items_per_page > 0)
            $sql .= " LIMIT $offset,$items_per_page";
        //echo $sql;

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public
    static function get_sage_bill_payments_history($paged = 1, $items_per_page = 10, $select_what = '*')
    {
        global $wpdb;
        if (!is_numeric($paged))
            $paged = 1;
        $offset = ($paged - 1) * $items_per_page;
        $table_prefix = self::$table_prefix;

        $ar_division_number = CPLINK::get_user_meta('cp_ar_division_no'); //'01';//
        $customer_number = CPLINK::get_user_meta('cp_customer_no'); //'0000007'; //'ABF'; //

        $cash_receipts_table = $table_prefix . 'cash_receipts_history';
        $bill_payment_lines_table = $table_prefix . 'cash_receipt_lines';

        $sql = "SELECT $select_what FROM $cash_receipts_table ";
        $where = " WHERE `ar_division_number` = '$ar_division_number' AND LOWER(`customer_number`) = LOWER('$customer_number')";

        if (isset($_GET['bp_action']) && $_GET['bp_action'] == 'search') {
            foreach ($_GET as $search_key => $search_val) {
                if (isset($search_val) && !empty($search_val)) {
                    switch ($search_key) {
                        case 'bp_confirmation':
                            $where .= " AND `external_cash_number` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'bp_reference':
                            $where .= " AND `credit_card_entry_number` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'bp_deposit':
                            $where .= " AND `deposit_number` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'bp_invoice':
                            $where .= " AND `invoice_number` LIKE '%" . $search_val . "%' ";
                            break;

                        case 'item_code':
                            $where .= " AND `bill_payment_no` IN (";
                            $where .= "SELECT $bill_payment_lines_table.`bill_payment_no` FROM $bill_payment_lines_table WHERE `" . $search_key . "` LIKE '%" . $search_val . "%'  ";
                            $where .= ")";
                            break;

                        //balance
                        case 'bp_date_from':
                            if( $search_val ) {
                                $search_val = date('Y-m-d H:i:s', strtotime($search_val));
                                $where .= " AND `deposit_date` >= '" . $search_val . "'";
                            }

                            break;
                        case 'bp_date_to':
                            if( $search_val ) {
                                $search_val = date('Y-m-d H:i:s', strtotime( $search_val) );
                                $where .= " AND `deposit_date` <= '" . $search_val."'";
                            }
                            break;

                        //total
                        case 'min_total':
                            $search_val = 0 + $search_val;
                            $where .= " AND `total` >= " . $search_val;

                            break;
                        case 'max_total':
                            $search_val = 0 + $search_val;
                            $where .= " AND `total` <= " . $search_val;
                            break;
                    }
                }
            }
        }
        $sql .= $where;
        $sql .= " ORDER BY `deposit_date` DESC, `deposit_number` DESC";//echo $sql;
        if ($items_per_page > 0)
            $sql .= " LIMIT $offset,$items_per_page";
        //echo $sql;

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public
    static function get_sage_bill_payment($bill_payment_id, $col = '*')
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;

        $orders_table = $table_prefix . 'bill_payments';
        $sql = "SELECT $col FROM `$orders_table` WHERE `id` = '$bill_payment_id' "; //echo $sql;
        if (!current_user_can('administrator')) {
            $ar_division_no = CPLINK::get_user_meta('cp_ar_division_no');
            $customer_no = CPLINK::get_user_meta('cp_customer_no');
            $sql .= " AND `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no')";
        }

        $bill_payment = $wpdb->get_row($sql, ARRAY_A);
        if (empty($bill_payment))
            $bill_payment = array();

        return $bill_payment;
    }

    public
    static function get_order_bill_payments($sales_order_no)
    {
        global $wpdb;

        $table_prefix = self::$table_prefix;
        $ar_division_no = CPLINK::get_user_meta('cp_ar_division_no'); //'01';//
        $customer_no = CPLINK::get_user_meta('cp_customer_no'); //'0000007'; //'ABF'; //

        $orders_table = $table_prefix . 'bill_payments';
        $sql = "SELECT * FROM `$orders_table` WHERE `sales_order_no` = $sales_order_no AND `ar_division_no` = '$ar_division_no' AND LOWER(`customer_no`) = LOWER('$customer_no')";
        $sql .= " ORDER BY `bill_payment_date` DESC";//echo $sql;

        $bill_payments = $wpdb->get_results($sql, ARRAY_A);
        return $bill_payments;
    }

    public
    static function get_sage_bill_payment_line($bill_payment_no, $header_seq_no = '')
    {
        global $wpdb;
        return CPLINK::get_sage_invoice_line($bill_payment_no, $header_seq_no); // temporary
        $table_prefix = self::$table_prefix;

        $bill_payment_lines_table = $table_prefix . 'bill_payment_lines';
        $sql = "SELECT * FROM `$bill_payment_lines_table` WHERE `bill_payment_no` = '$bill_payment_no'"; //echo $sql;
        if ($header_seq_no)
            $sql .= " AND `header_seq_no` = '$header_seq_no'";

        $bill_payment_lines = $wpdb->get_results($sql, ARRAY_A);
        return $bill_payment_lines;
    }

    public
    static function get_sage_bill_payment_serials($bill_payment_no = '', $header_seq_no = '', $line_key = '', $col = '*')
    {
        global $wpdb;
        $table_prefix = self::$table_prefix;

        $bill_payment_serials_table = $table_prefix . 'bill_payment_serials';
        $sql = "SELECT $col FROM `$bill_payment_serials_table` WHERE `bill_payment_no` = '$bill_payment_no' AND `header_seq_no` = '$header_seq_no' AND `line_key` = '$line_key'"; //echo $sql;

        $bill_payment_serials = $wpdb->get_row($sql, ARRAY_A);
        return $bill_payment_serials;
    }

    public
    static function get_sage_bill_payment_trackings($bill_payment_no = '', $header_seq_no = '', $line_key = '', $col = '*')
    {
        global $wpdb;
        return CPLINK::get_sage_invoice_trackings($bill_payment_no, $header_seq_no, $line_key, $col);
        $table_prefix = self::$table_prefix;

        $bill_payment_serials_table = $table_prefix . 'bill_payment_trackings';
        $sql = "SELECT $col FROM `$bill_payment_serials_table` WHERE `bill_payment_no` = '$bill_payment_no' "; //echo $sql;

        if ($header_seq_no)
            $sql .= " AND `header_seq_no` = '$header_seq_no'";

        $bill_payment_serials = $wpdb->get_results($sql, ARRAY_A);
        return $bill_payment_serials;
    }

    //Actions
    //This function will appear on cpbp_schedule_import cronjob
    public
    static function cpbp_run_import($import_module = '')
    {
        global $CP_Sage, $CPBP_Sage;
        global $cpbp_modules_cf;
        //$cpbp_modules_cf = self::$module_options = get_option(CPBP::get_settings_name('modules'), true);

        self::max_server_ini();

        $current_time = date('Y-m-d H:i:s');
        $last_import_date = get_option('cpbp_last_import_date', true);

        $import_schedules = self::$import_schedules;
        if (!isset($import_schedules[$import_module]))
            return false;

        $import_rain = $import_schedules[$import_module];

        $CPBP_Sage->ItsCron();
        $import_module_sources = $import_rain['name'];
        $cpbp_import_source = $import_rain['type'];

        $before_run = CPLINK::isset_return($import_rain, 'before_run', array());
        $after_run = CPLINK::isset_return($import_rain, 'after_run', array());
        $before_run[$import_module] = $import_module_sources;
        $run_imports = array_merge($before_run, $after_run);

        //i_print($run_imports); exit;

        if (count($run_imports)) {
            foreach ($run_imports as $run_module => $run_module_data) {

                if (is_array($run_module_data)) {
                    $run_module_type = $run_module_data['name'];
                    $run_module_src = $run_module_data['type'];
                } else {
                    $run_module_type = $run_module_data;
                    $run_module_src = $cpbp_import_source;
                }

                $import_module_enabled = CPLINK::isset_return($cpbp_modules_cf, $run_module . '_enabled', 1);
                if ($import_module_enabled) {
                    if ($run_module_src == 'purge') {
                        $import_info = array(
                            'cplink_import_type' => $run_module_type,
                            'cplink_import_source' => 'sync'
                        );
                        $import_sync_result = CPBP::cpbp_import($import_info);
                    }
                    $import_info = array(
                        'cplink_import_type' => $run_module_type,
                        'cplink_import_source' => $run_module_src
                    );
                    $import_result = CPBP::cpbp_import($import_info);
                }
            }
        }

        update_option('cpbp_last_import_date', $current_time);
    }

    public
    static function requireCPBPAdmin()
    {
        if (!class_exists('CPBP_Admin')) {
            require_once(CPBP_PLUGIN_DIR . 'lib/class.cpbp-admin.php');
        }
    }

    //This function will appear on cpbp_schedule_export cronjob
    public
    static function cpbp_run_export()
    {
        global $CP_Sage, $cpbp_scope_cf, $cpbp_modules_cf, $wpdb;
        $order_export_enabled = CPLINK::isset_return($cpbp_scope_cf, 'cash_export');
        $max_exporting_attempts = CPLINK::isset_return($cpbp_scope_cf, 'max_exporting_attempts');
        $exportable_order_status = CPLINK::isset_return($cpbp_scope_cf, 'exportable_status');
        //i_print($cpbp_scope_cf,true);
        /*i_print($exportable_order_status,true);
        exit;*/
        self::max_server_ini();

        self::requireCPBPAdmin();

        if ($order_export_enabled) {
            $orders_to_export = array();
            $table_prefix = CPBP_DB_PREFIX;

            //Table structure for table `_queue`
            $table_name = $table_prefix . 'queue';
            /*$sql_where = '';*/
            $sql_where = 'WHERE `active` = 1';
            if (is_numeric($max_exporting_attempts) || !empty($exportable_order_status)) {

                if (is_numeric($max_exporting_attempts)) {
                    $sql_where .= ' AND `export_count` <= ' . intval($max_exporting_attempts);
                }
                if (!empty($exportable_order_status)) {
                    $array_string = implode("','", $exportable_order_status);
                    $sql_where .= " AND `status` IN ('" . $array_string . "')";
                }
                /*i_print($sql_where);
                exit;*/
            }
            $query = "SELECT `external_cash_number` FROM $table_name $sql_where ORDER BY `external_cash_number` DESC";
            $db_result = $wpdb->get_results($query, ARRAY_A);

            /*i_print($query);
            i_print($db_result);*/
                /*exit;*/

            if (!empty($db_result)) {
                foreach ($db_result as $order) {
                    $orders_to_export[] = $order['external_cash_number'];
                }
            }

            $export_orders_to_sage = CPBP_Admin::export_orders_to_sage($orders_to_export);
        }
    }

    public static function getResponseCount($req_info, $req_result)
    {
        global $CP_Sage;
        $response_n = 0;

        if ($req_info['success']) {
            if (isset($req_info['counts'])) {
                $request_counts = $req_info['counts']['request'];
                $req_status = CPLINK::isset_return($req_result, 'status');
                if ($req_status !== 0 && $req_result) { //echo 'here'.$request_counts;
                    $response_n = $request_counts;
                }
            }
        }
        return $response_n;
    }

    public static function create_bill_pay_order($order_id, $posted_data, $order)
    {
        $order_items = $order->get_items();
        $order_id = $order->get_id();
        foreach( $order_items as $product ) {
            if($product->get_product_id() == self::$cpbp_product_id) {
                //set_post_type( $order_id, 'cpbp_order' );
                update_post_meta($order_id, 'cpbp_order', '1');
                $order->update_status( 'cpbp_order' );
            }
        }
    }

    public static function sanitize_bill_pay_successful_result($result, $order_id)
    {
        //i_print($result); exit;
        if ( get_post_meta($order_id, 'cpbp_order', true) ){
            $dont_redirects_actions = array(
                'order-pay' // Payment method: Fortis (Credit Card) - wait for payment with new card
            );
            if( isset($result['redirect']) ) {
                foreach ($dont_redirects_actions as $dont_redirects_action){
                    if( strpos($result['redirect'], $dont_redirects_action) ) {
                        $result['redirect'] = $result['redirect'].'&bill_checkout';
                        return $result;
                    }
                }
            }

            //
            $cpbp_thank_you_url = self::order_convert_cpbp_order($order_id);
            $result = array(
                'result' => "success",
                'order_id' => $order_id,
                'redirect' => $cpbp_thank_you_url
            );
        }

        return $result;

        /*$order_items = $order->get_items();
        $order_id = $order->get_id();
        foreach( $order_items as $product ) {
            if($product->get_product_id() == self::$cpbp_product_id) {
                set_post_type( $order_id, 'cpbp_order'  );
            }
        }*/
    }

    function sanitize_wc_thank_you_page( $order_id ) {
        //$order = wc_get_order( $order_id );

        if ( get_post_meta($order_id, 'cpbp_order', true) ){
            $cpbp_thank_you_url = self::order_convert_cpbp_order($order_id);
            wp_redirect( $cpbp_thank_you_url );
            exit;
        }
    }

    public static function order_convert_cpbp_order($order_id)
    {
        self::empty_cpbp_cart();

        $order = new WC_Order($order_id);
        $user_id = $order->get_user_id();

        $cp_ar_division_no = get_user_meta($user_id,'cp_ar_division_no',true);
        $cp_customer_no = get_user_meta($user_id,'cp_customer_no',true);

        update_post_meta($order_id,'cp_ar_division_no',$cp_ar_division_no);
        update_post_meta($order_id,'cp_customer_no',$cp_customer_no);

        //$order = wc_get_order($order_id);
        set_post_type( $order_id, 'cpbp_order' );

        global $wpdb;

        $table_prefix = CPBP_DB_PREFIX;

        //Table structure for table `_queue`
        $table_name = $table_prefix . 'queue';

        $data = array(
            'external_cash_number' => $order_id,
            'message' => '',
            'export_count' => 0,
            'created_time' => wp_date("Y-m-d h:i:s"),
        );

        $sql = "SELECT `id` FROM `$table_name` WHERE `external_cash_number` = '$order_id'";

        $find_id = $wpdb->get_row($sql, ARRAY_A);
        if(!$find_id){
            $resultOfInsert = $wpdb->insert($table_name, $data);
        }

        return wc_get_account_endpoint_url('bill_payments').'?cpbp_thank_you='.$order_id;
    }

    public static function sanitize_bill_pay_email_recipient($recipient, $order, $email)
    {
        if ( get_post_meta($order->get_id(), 'cpbp_order', true) ) {
            $recipient = '';
        }

        return $recipient;
    }

    public static function cpbp_import($import_info)
    {
        CPBP::max_server_ini();

        global $CP_Sage, $CPBP_Sage;
        $status = false;
        $err_msg = '<div> There is ERROR!!! </div>';

        $return = array(
            'status' => $status,
            'html' => $err_msg
        );

        $import_type = $import_info['cplink_import_type'];
        $import_source = $import_info['cplink_import_source'];
        $req_result = array();

        //Possible actions:
        //Global – Imports the whole module from Sage to eCommerce Portal.
        //Sync – Imports the changes of each module from Sage to eCommerce Portal.
        //Purge – Imports SQL database keys to compare if there are keys left in eCommerce Portal which
        // have been deleted in Sage then it removes them in eCommerce Portal as well

        $CPBP_Sage->setImportSource($import_source);
        $CPBP_Sage->setImportType($import_type);
        switch ($import_source) {
            case 'purge':
            case 'global':
                $modified_from = '-1';
                break;
            default:
                $modified_from = '';
                break;
        }

        $page = 1;
        $limit = 1000;
        $response_n = $limit;

        switch ($import_type) {
            case 'cashreceipts': //cash_import
                $export_orders = self::cpbp_run_export();
                while ($response_n == $limit) {
                    $req_result = $CPBP_Sage->getCashReceipts($page, $limit, $modified_from);
                    $req_info = $CPBP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount($req_info, $req_result);
                    $page++;
                }
                $status = true;
                $err_msg = 'All Cashe Receipts are imported successfully!';
                break;
            case 'cashreceipts_history': //cash_history_import
                while ($response_n == $limit) {
                    $req_result = $CPBP_Sage->getCashReceiptsHistory($page, $limit, $modified_from);
                    $req_info = $CPBP_Sage->getRequestInfo();
                    $response_n = self::getResponseCount($req_info, $req_result);
                    $page++;
                }
                $status = true;
                $err_msg = 'All Cash Receipts History are imported successfully!';
                break;
            case 'childmethods':
                /*while ($response_n == $limit) {
                    $req_result = $CPBP_Sage->getShippingMethods(null, $page, $limit, $modified_from);
                    $req_info = $CPBP_Sage->getRequestInfo();

                    $response_n = self::getResponseCount($req_info, $req_result);
                    $page++;
                }*/
                $status = true;
                $err_msg = 'All Child Methods are imported successfully!';
                break;
            default:
                $status = false;
                $err_msg = 'There is no any import action for your request!';
                break;
        }

        if (in_array($import_source, $CPBP_Sage->getStoreWhenSource())) {
            $CPBP_Sage->PurgeData();
        }

        $req_status = CPLINK::isset_return($req_result, 'status');
        $api_error = $CPBP_Sage->getAPIError();
        if ($api_error['errorNo']) {
            $return = array(
                'status' => 2,
                'html' => 'API ERROR: ' . $api_error['errorMessage']
            );

            CPLINK::sendApiErrorMessageToClient($api_error['errorMessage'], '', $import_type . 'Import');
        } else {
            if ($req_status === 0) {
                $return = array(
                    'status' => 0,
                    'html' => CPLINK::isset_return($req_result, 'message')
                );
                CPLINK::sendApiErrorMessageToClient('Request Failed. Please check your API. | <b>' . $import_type . ' Import</b>', '', $import_type . 'Import');
            } else {
                $return = array(
                    'status' => $status,
                    'html' => $err_msg
                );
            }
        }

        $return['last_import_data'] = $CPBP_Sage->getLastImportData();
        return $return;
    }

    /*
     * Ajax Requesting
     */
    public static function cpbp_get_invoices()
    {
        if (!(isset($_REQUEST['action']) && 'cpbp_get_invoices' == $_POST['action']))
            return;

        $return = array(
            'status' => true,
            'html' => ''
        );
        $html = '';
        $where = ' AND `header_seq_no` != "" AND `header_seq_no` != "null" ';
        $paged = (isset($_POST['page']) && is_numeric($_POST['page'])) ? absint($_POST['page']) : 1;
        $s_invoice_no = (isset($_POST['s_invoice_no'])) ? $_POST['s_invoice_no'] : '';
        $s_inv_date_from = (isset($_POST['s_inv_date_from']) && $_POST['s_inv_date_from'] != '') ? date( 'Y-m-d', strtotime( $_POST['s_inv_date_from'])) : '';
        $s_inv_date_to = (isset($_POST['s_inv_date_to']) && $_POST['s_inv_date_to'] != '') ? date( 'Y-m-d', strtotime( $_POST['s_inv_date_to'])) : '';
        if( $s_invoice_no )
            $where.= ' AND `invoice_no` LIKE "%'.$s_invoice_no.'%"';
        if( $s_inv_date_from )
            $where.= ' AND `invoice_date` > "'.$s_inv_date_from.'"';
        if( $s_inv_date_to )
            $where.= ' AND `invoice_date` < "'.$s_inv_date_to.'"';

        $items_per_page = 10;

        $invoices = CPLINK::get_sage_invoices($paged, $items_per_page, '*', $where);
        $pending_invoices_list = self::cpbp_get_invoice_pending_payments();

        //i_print($pending_invoices_list);

        $i = 0;
        if( count($invoices) ){
            foreach ($invoices as $invoice){
                $invoice_id = $invoice['id'];
                $invoice_no = $invoice['invoice_no'];
                $invoice_date = date( 'm/d/Y', strtotime( $invoice['invoice_date'] ));
                $invoice_type = $invoice['invoice_type'];
                $total = $invoice['balance'] - $invoice['payments_today'];
                if(!empty($pending_invoices_list) && array_key_exists($invoice_no,$pending_invoices_list)){
                    $total = $total - $pending_invoices_list[$invoice_no];
                }
                if($total > 0){

                    $html.= '<tr class="bp_popup_checkbox_tr">';
                    $html.= '<td  class="bp_popup_checkbox"><input name="inv_items['.$i.'][selected]" id="invItems_'.$i.'_selected" class="bp_invoice_items_selected" type="checkbox"></td>';
                    $html.= '<td data-th="Invoice #" class="bp_popup_invoice"><input name="inv_items['.$i.'][inv_no]" id="invItems_'.$i.'_invNo" type="hidden" value="'.$invoice_no.'">';
                    $html.= '<input name="inv_items['.$i.'][inv_type]" id="invItems_'.$i.'_inv_type" type="hidden" value="'.$invoice_type.'">';
                    $html.= '<input name="inv_items['.$i.'][item_id]" id="invItems_'.$i.'_itemId" class="bp_invoice_item_id" type="hidden" value="'.$invoice_id.'">'.$invoice_no.'</td>';
                    $html.= '<td data-th="Date" class="bp_popup_date"><input name="inv_items['.$i.'][inv_date]" id="invItems_'.$i.'_invDate" type="hidden" value="'.$invoice_date.'">'.$invoice_date.'</td>';
                    $html.= '<td data-th="Amount" class="bp_popup_amount"><span>$</span> <input  name="inv_items['.$i.'][amount_posted]" class="bp_invoice_items_posted" id="invItems_'.$i.'_amount_posted" type="number" autocomplete="off" value="'.$total.'"></td>';
                    $html.= '<td data-th="Balance" class="bp_popup_balance">$'.$total.'<input name="inv_items['.$i.'][inv_balance]" id="invItems_'.$i.'_inv_balance" type="hidden" value="'.$total.'"></td>';
                    $html.= '</tr>';

                    $i++;
                }
            }
        }
        $return['html'] = $html;

        echo json_encode($return);
        exit;
    }
    public static function cpbp_get_invoice_pending_payments(){
        $ar_division_no = CPLINK::get_user_meta('cp_ar_division_no'); //'01';//
        $customer_no = CPLINK::get_user_meta('cp_customer_no');

        $args = array(
            'fields' => 'ids',
            'posts_per_page'   => -1,
            'post_type'     => 'cpbp_order',
            'meta_query'    => array(
                'relation'      => 'AND',
                array(
                    'key'       => 'cp_ar_division_no',
                    'value'     => $ar_division_no,
                    'compare'   => '=',
                ),
                array(
                    'key'       => 'cp_customer_no',
                    'value'     => $customer_no,
                    'compare'   => '=',
                ),
                array(
                    'key' => 'cp_sales_order_number',
                    'compare' => 'NOT EXISTS'
                ),
            ),
        );

        $post_q = new WP_Query($args);

        global $wpdb;
        $table_prefix = CPBP_DB_PREFIX;

        $table_name = $table_prefix . 'queue';
        $pending_invoices_list = array();
        if( count($post_q->posts) ){
            $posts = $post_q->posts;
            foreach ($posts as $post){
                $db_result = $wpdb->get_results("SELECT * FROM $table_name WHERE external_cash_number = $post and active = 1");
                if(!empty($db_result)){
                    $woocommerce_order_items_table = $wpdb->prefix.'woocommerce_order_items';
                    $woocommerce_order_items_result = $wpdb->get_results("SELECT * FROM $woocommerce_order_items_table WHERE `order_id` = $post");
                    if(!empty($woocommerce_order_items_result)){
                        foreach ($woocommerce_order_items_result as $woocommerce_order_item_key => $item){
                            //$order_item_datas = [];
                            $order_item_id = $item->order_item_id;
                            //array_push($order_item_ids,$item->order_item_id);
                            $woocommerce_order_itemmeta_table = $wpdb->prefix.'woocommerce_order_itemmeta';
                            $woocommerce_order_itemmeta_invoice_no_result = $wpdb->get_row("SELECT * FROM $woocommerce_order_itemmeta_table WHERE `order_item_id` = $order_item_id and `meta_key` = 'Invoice No'");
                            $woocommerce_order_itemmeta_line_total_result = $wpdb->get_row("SELECT * FROM $woocommerce_order_itemmeta_table WHERE `order_item_id` = $order_item_id and `meta_key` = '_line_total'");
                            //$woocommerce_order_itemmeta_invoice_type_result = $wpdb->get_row("SELECT * FROM $woocommerce_order_itemmeta_table WHERE `order_item_id` = $order_item_id and `meta_key` = 'invoice_type'");


                            $invoice_number = $woocommerce_order_itemmeta_invoice_no_result->meta_value;
                            $amount_posted = max($woocommerce_order_itemmeta_line_total_result->meta_value, 0);
                            //$order_item_datas['invoice_type'] = $woocommerce_order_itemmeta_invoice_type_result->meta_value;
                            if(array_key_exists($invoice_number,$pending_invoices_list)){
                                $pending_invoices_list[$invoice_number] = $pending_invoices_list[$invoice_number] + $amount_posted;
                            }else{
                                $pending_invoices_list[$invoice_number] = $amount_posted;
                            }
                        }
                    }
                }
            }
        }

        return $pending_invoices_list;
    }

    public static function cpbp_remove_from_cart()
    {
        if (!(isset($_REQUEST['action']) && 'cpbp_remove_from_cart' == $_POST['action']))
            return;

        $user_id = get_current_user_id();
        if( !$user_id )
            return false;

        $return = array(
            'status' => true,
            'html' => ''
        );
        $html = '';

        $remove_cart_item_key = (isset($_POST['cart_item_key']) ) ? $_POST['cart_item_key'] : '';
        $cpbp_cart = self::get_cpbp_cart();
        if( is_numeric( $remove_cart_item_key ) && $remove_cart_item_key >= 0 ) {
            if ( count( $cpbp_cart ) ) {
                foreach ($cpbp_cart as $cart_item_key => $cart_item) {
                    if( $cart_item_key == $remove_cart_item_key ) {
                        unset($cpbp_cart[$cart_item_key]);
                    }
                }

                update_user_meta($user_id, 'cpbp_cart', $cpbp_cart);
            }
        }

        $return['html'] = $html;

        echo json_encode($return);
        exit;
    }


    public static function cpbp_create_bill_pay_cart()
    {
        if (!(isset($_REQUEST['action']) && 'cpbp_create_bill_pay_cart' == $_POST['action']))
            return;

        $return = array(
            'status' => true,
            'orders_data' => []
        );

        global $woocommerce;

        $bill_invoices = (isset($_POST['bill_invoices'])) ? $_POST['bill_invoices'] : array();
        //i_print($bill_invoices);
        $cpbp_product_id = self::$cpbp_product_id;
        if (count($bill_invoices)) {
            foreach ($bill_invoices as $bill_invoice) {
                //Empty Cart for this case
                $cart_item_meta = array();
                $bp_invoice_data = array();
                if (isset($bill_invoice['invoice_id']) && $bill_invoice['invoice_id']) {
                    $bp_invoice_id = $bill_invoice['invoice_id'];
                    $bp_invoice_amount = $bill_invoice['amount'];

                    $invoice_item = CPLINK::get_sage_invoice($bp_invoice_id, '*');

                    if ($invoice_item) {

                        $invoice_no = $invoice_item['invoice_no'];
                        $header_seq_no = $invoice_item['header_seq_no'];
                        //$invoice_balance = $invoice_item['balance'];
                        $invoice_type = $invoice_item['invoice_type'];
                        $invoice_date = $invoice_item['invoice_date'];

                        $pending_invoices_list = self::cpbp_get_invoice_pending_payments();
                        $total_balance = $invoice_item['balance'] - $invoice_item['payments_today'];
                        if(!empty($pending_invoices_list) && array_key_exists($invoice_no,$pending_invoices_list)){
                            $total_balance = $total_balance - $pending_invoices_list[$invoice_no];
                        }

                        //Check if invoice already exist, then remove and create fresh version
                        if (!WC()->cart->is_empty()) {
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                if ($cart_item['product_id'] == $cpbp_product_id) {
                                    if ($cart_item['bp_product_data']['bp_invoice_id'] == $bp_invoice_id) {
                                        $woocommerce->cart->remove_cart_item( $cart_item_key );
                                    }
                                }
                            }
                        }
                        $invoice_title = '' . $invoice_no; //Invoice N
                        $bp_invoice_data ['bp_invoice_title'] = sanitize_text_field($invoice_title);
                        $bp_invoice_data ['bp_invoice_id'] = sanitize_text_field($bp_invoice_id);
                        $bp_invoice_data ['bp_invoice_no'] = sanitize_text_field($invoice_no);
                        $bp_invoice_data ['bp_invoice_date'] = sanitize_text_field($invoice_date);
                        $bp_invoice_data ['bp_header_seq_no'] = sanitize_text_field($header_seq_no);
                        $bp_invoice_data ['bp_invoice_amount'] = sanitize_text_field($bp_invoice_amount);
                        $bp_invoice_data ['bp_balance'] = sanitize_text_field($total_balance);
                        $bp_invoice_data ['bp_invoice_type'] = sanitize_text_field($invoice_type);
                        $bp_invoice_data['product_id'] = $cpbp_product_id;

                        $bill_invoice['product_id'] = $cpbp_product_id;
                        $bill_invoice ['bp_product_data'] = $bp_invoice_data;

                        $cart_item_key = self::add_to_cart($bill_invoice);
                        $bp_invoice_data['cart_item_key'] = $cart_item_key;
                        array_push($return['orders_data'], $bp_invoice_data);
                    }
                }
            }
        }

        echo json_encode($return);
        exit;
    }
    public static function add_to_cart( $bill_invoice )
    {
        $user_id = get_current_user_id();
        if( !$user_id )
            return false;

        $cpbp_cart = self::get_cpbp_cart();

        $is_exist = false;
        if( isset( $bill_invoice['invoice_id'] ) && !empty($cpbp_cart) ){
            foreach ($cpbp_cart as $cpbp_cart_key => $cpbp_cart_item){
                if( $cpbp_cart_item['invoice_id'] == $bill_invoice['invoice_id'] ){
                    $cpbp_cart[$cpbp_cart_key] = $bill_invoice;
                    $is_exist = true;
                }
            }
        }
        if( !$is_exist )
            array_push($cpbp_cart, $bill_invoice);

        update_user_meta($user_id, 'cpbp_cart', $cpbp_cart);

        return count($cpbp_cart)-1;
    }

    public static function get_cpbp_cart(){
        $user_id = get_current_user_id();
        if( !$user_id )
            return false;

        $cpbp_cart = get_user_meta($user_id, 'cpbp_cart', true);

        if( !$cpbp_cart )
            $cpbp_cart = array();

        return $cpbp_cart;
    }
    public static function remove_cpbp_cart_item( $cart_item_key ){
        $user_id = get_current_user_id();
        if( !$user_id )
            return false;

        $cpbp_cart = get_user_meta($user_id, 'cpbp_cart', true);

        if( !$cpbp_cart )
            $cpbp_cart = array();

        return $cpbp_cart;
    }
    public static function empty_cpbp_cart( ){
        $user_id = get_current_user_id();
        if( !$user_id )
            return false;

        return update_user_meta($user_id, 'cpbp_cart', '');
    }


    public static function cpbp_create_bill_pay()
    {
        if (!(isset($_REQUEST['action']) && 'cpbp_create_bill_pay' == $_POST['action']))
            return;

        $return = array(
            'status' => true,
            'orders_data' => []
        );

        global $woocommerce;
        $bill_invoices = (isset($_POST['bill_invoices'])) ? $_POST['bill_invoices'] : array();

        $cpbp_product_id = self::$cpbp_product_id;
        if (count($bill_invoices)) {
            foreach ($bill_invoices as $bill_invoice) {
                if($bill_invoice['amount'] == 0){
                    $return = array(
                        'status' => false,
                        'orders_data' => []
                    );
                    echo json_encode($return);
                    exit;
                }
            }
            //Save normal cart items to recover after BillPay
            if (!WC()->cart->is_empty()) {

                //Save Woo Real Cart, because we will use cart bucket to run checkout then we need to restore the Woo Real Cart
                self::save_woo_cart();

                //Empty Cart for this case
                WC()->cart->empty_cart();
            }

            foreach ($bill_invoices as $bill_invoice) {
                $cart_item_meta = array();
                $bp_invoice_data = array();
                if (isset($bill_invoice['invoice_id']) && $bill_invoice['invoice_id']) {
                    $bp_invoice_id = $bill_invoice['invoice_id'];
                    $bp_invoice_amount = $bill_invoice['amount'];

                    $invoice_item = CPLINK::get_sage_invoice($bp_invoice_id, '*');
                    /*i_print($invoice_item);*/
                    if ($invoice_item) {

                        $invoice_no = $invoice_item['invoice_no'];
                        $header_seq_no = $invoice_item['header_seq_no'];
                        //$invoice_balance = $invoice_item['balance'];
                        $invoice_type = $invoice_item['invoice_type'];
                        $invoice_date = $invoice_item['invoice_date'];

                        $pending_invoices_list = self::cpbp_get_invoice_pending_payments();
                        $total_balance = $invoice_item['balance'] - $invoice_item['payments_today'];
                        if(!empty($pending_invoices_list) && array_key_exists($invoice_no,$pending_invoices_list)){
                            $total_balance = $total_balance - $pending_invoices_list[$invoice_no];
                        }

                        //Check if invoice already exist, then remove and create fresh version
                        if (!WC()->cart->is_empty()) {
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                if ($cart_item['product_id'] == $cpbp_product_id) {
                                    if ($cart_item['bp_product_data']['bp_invoice_id'] == $bp_invoice_id) {
                                        $woocommerce->cart->remove_cart_item( $cart_item_key );
                                    }
                                }
                            }
                        }
                        //i_print($invoice_item); exit;
                        //$br_map_title = get_the_title($bp_invoice_id);
                        add_filter('private_title_format', function ($format) { //Remove Private prefix text from title if post is private
                            return '%s';
                        });

                        $invoice_title = '' . $invoice_no; //Invoice N
                        $bp_invoice_data ['bp_invoice_title'] = sanitize_text_field($invoice_title);
                        $bp_invoice_data ['bp_invoice_id'] = sanitize_text_field($bp_invoice_id);
                        $bp_invoice_data ['bp_invoice_no'] = sanitize_text_field($invoice_no);
                        $bp_invoice_data ['bp_invoice_date'] = sanitize_text_field($invoice_date);
                        $bp_invoice_data ['bp_header_seq_no'] = sanitize_text_field($header_seq_no);
                        $bp_invoice_data ['bp_invoice_amount'] = sanitize_text_field($bp_invoice_amount);
                        $bp_invoice_data ['bp_balance'] = sanitize_text_field($total_balance);
                        $bp_invoice_data ['bp_invoice_type'] = sanitize_text_field($invoice_type);
                        $cart_item_meta ['bp_product_data'] = $bp_invoice_data;

                        //$_SESSION['bp_invoice_id'] = $bp_invoice_id;
                        //$_SESSION['bp_invoice_no'] = $invoice_no;
                        //$_SESSION['bp_invoice_title'] = $invoice_title;
                        $cart_item_key = $woocommerce->cart->add_to_cart($cpbp_product_id, 1, 0, array(), $cart_item_meta);
                        $bp_invoice_data['product_id'] = $cpbp_product_id;
                        $bill_invoice['product_id'] = $cpbp_product_id;
                        $bill_invoice ['bp_product_data'] = $bp_invoice_data;
                        self::add_to_cart($bill_invoice);
                        $bp_invoice_data['cart_item_key'] = $cart_item_key;
                        array_push($return['orders_data'], $bp_invoice_data);
                    }
                }
            }
        }

        echo json_encode($return);
        exit;
    }
    public static function cpbp_create_bill_pay_deposit()
    {
        if (!(isset($_REQUEST['action']) && 'cpbp_create_bill_pay_deposit' == $_POST['action']))
            return;

        $return = array(
            'status' => true,
            'orders_data' => []
        );

        global $woocommerce;
        $bill_invoices = (isset($_POST['bill_invoices'])) ? $_POST['bill_invoices'] : array();

        $cpbp_product_id = self::$cpbp_product_id;
        if (count($bill_invoices)) {

            //Save normal cart items to recover after BillPay
            if (!WC()->cart->is_empty()) {

                //Save Woo Real Cart, because we will use cart bucket to run checkout then we need to restore the Woo Real Cart
                self::save_woo_cart();

                //Empty Cart for this case
                WC()->cart->empty_cart();
            }

            foreach ($bill_invoices as $bill_invoice) {
                $cart_item_meta = array();
                $bp_invoice_data = array();
                if (isset($bill_invoice['amount']) && $bill_invoice['amount']) {
                    $bill_pay_deposit_uniqu_id = get_option('bill_pay_deposit_uniqu_id',true);
                    if(isset($bill_pay_deposit_uniqu_id) && is_numeric($bill_pay_deposit_uniqu_id)){
                        $bill_pay_deposit_uniqu_id = $bill_pay_deposit_uniqu_id + 1;
                    }else{
                        $bill_pay_deposit_uniqu_id = 10000;
                    }
                    update_option('bill_pay_deposit_uniqu_id',$bill_pay_deposit_uniqu_id,true);
                    /*$num_length = strlen((string)$bill_pay_deposit_uniqu_id);
                    $uniq_num_prefix = '';
                    for($i = 0;$i<5-$num_length;$i++){
                        $uniq_num_prefix .= '0';
                    }*/
                    //$bp_invoice_id = 'PP' . $uniq_num_prefix . $bill_pay_deposit_uniqu_id;
                    $bp_invoice_id = 'PP' . $bill_pay_deposit_uniqu_id;
                    //i_print($bp_invoice_id);
                    $bp_invoice_amount = $bill_invoice['amount'];

                    //$invoice_item = CPLINK::get_sage_invoice($bp_invoice_id, '*');
                    /*i_print($invoice_item);*/
                    //if ($invoice_item) {

                        $invoice_no = $bp_invoice_id;
                        $header_seq_no = '';
                        $invoice_balance = '';
                        $invoice_type = 'PP';

                        //Check if invoice already exist, then remove and create fresh version
                        if (!WC()->cart->is_empty()) {
                            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                                if ($cart_item['product_id'] == $cpbp_product_id) {
                                    if ($cart_item['bp_product_data']['bp_invoice_id'] == $bp_invoice_id) {
                                        $woocommerce->cart->remove_cart_item( $cart_item_key );
                                    }
                                }
                            }
                        }
                        //i_print($invoice_item); exit;
                        //$br_map_title = get_the_title($bp_invoice_id);
                        add_filter('private_title_format', function ($format) { //Remove Private prefix text from title if post is private
                            return '%s';
                        });

                        $invoice_title = '' . $invoice_no; //Invoice N
                        $bp_invoice_data ['bp_invoice_title'] = sanitize_text_field($invoice_title);
                        $bp_invoice_data ['bp_invoice_id'] = sanitize_text_field($bp_invoice_id);
                        $bp_invoice_data ['bp_invoice_no'] = sanitize_text_field($invoice_no);
                        $bp_invoice_data ['bp_header_seq_no'] = sanitize_text_field($header_seq_no);
                        $bp_invoice_data ['bp_invoice_amount'] = sanitize_text_field($bp_invoice_amount);
                        $bp_invoice_data ['bp_balance'] = sanitize_text_field($invoice_balance);
                        $bp_invoice_data ['bp_invoice_type'] = sanitize_text_field($invoice_type);
                        $cart_item_meta ['bp_product_data'] = $bp_invoice_data;

                        //$_SESSION['bp_invoice_id'] = $bp_invoice_id;
                        //$_SESSION['bp_invoice_no'] = $invoice_no;
                        //$_SESSION['bp_invoice_title'] = $invoice_title;
                        $cart_item_key = $woocommerce->cart->add_to_cart($cpbp_product_id, 1, 0, array(), $cart_item_meta);
                        $bp_invoice_data['product_id'] = $cpbp_product_id;
                        $bp_invoice_data['cart_item_key'] = $cart_item_key;
                        array_push($return['orders_data'], $bp_invoice_data);
                    //}
                }
            }
        }

        echo json_encode($return);
        exit;
    }
    //ETC
    public static function add_shipping_address_custom_field($address_fields)
    {
        if (!isset($address_fields['shipping_cp_shipto_code'])) {
            $address_fields['shipping_cp_shipto_code'] = array(
                'label' => '',
                'required' => false,
                'type' => 'hidden',
                'priority' => -1,
            );
        }

        return $address_fields;
    }
}