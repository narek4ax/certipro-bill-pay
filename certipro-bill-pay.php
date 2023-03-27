<?php
/*
Plugin Name:    CertiPro Bill Pay (Cash receipt)
Plugin URI: 
Description:    
Author: NGA HUB
Version:    0.0.1
Author URI:
*/

// Our prefix is CPBP / cpbp
global $wpdb;
define( 'CPBP_PLUGIN_NAME', 'CertiPro Bill Pay' );
define( 'CPBPVersion', '0.0.1' );
define( 'CPBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CPBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPBP_PROTECTION_H', plugin_basename(__FILE__) );
define( 'CPBP_NAME', 'cpe-link' );
define( 'CPBP_DB_VERSION', '0.1' );
define( 'CPBP_DB_PREFIX', $wpdb->prefix . 'elinkcash_' );
define( 'CPBP_SETTINGS_NAME', 'cpbp-settings' );

define( 'CPBP_SETTINGS_LINK', 'certipro-bill-pay' );
define( 'CPBP_AVAILABLE_PAYMENTS', array('sagepaymentsusaapi', 'zeamster') );


register_activation_hook( __FILE__, array( 'CPBP', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'CPBP', 'plugin_deactivation' ) );

add_action('cplink_init_ready', function(){
    require_once( CPBP_PLUGIN_DIR . 'lib/class.cpbp.php' );
    require_once( CPBP_PLUGIN_DIR . 'lib/class.cpbp-sage.php' );
    global $CPBP_Sage, $cpbp_scope_cf, $cpbp_modules_cf, $cp_shipping_methods;

    //$CPBP_Sage = new CPBP_Sage();

    //add_action( 'init', array( 'CPBP', 'init' ) );
    CPBP::init();

    if ( is_admin() ) {
        require_once( CPBP_PLUGIN_DIR . 'lib/class.cpbp-admin.php' );
        CPBP_Admin::init();
        //add_action('acf/init', array( 'CPBP', 'my_acf_op_init' ) );
    }
    if(isset($_GET['cp_action']) ) {
        if ($_GET['cp_action'] == 'run_export_crone') {
            CPBP::cpbp_run_export();
            exit;
        }
    }
}, 10);


/** exclude product from everywhere **/

function custom_meta_query( $meta_query ){
    $meta_query[] = array(
        'key'=>'cplink_bilpay_product',
        'value' => '1',
        'compare'=>'NOT EXISTS',
    );
    return $meta_query;
}

// The main shop and archives meta query
add_filter( 'woocommerce_product_query_meta_query', 'custom_product_query_meta_query', 10, 2 );
function custom_product_query_meta_query( $meta_query, $query ) {
    //if( ! is_admin() )
        return custom_meta_query( $meta_query );
}

// The shortcode products query
add_filter( 'woocommerce_shortcode_products_query', 'custom__shortcode_products_query', 10, 3 );
function custom__shortcode_products_query( $query_args, $atts, $loop_name ) {
   // if( ! is_admin() )
        $query_args['meta_query'] = custom_meta_query( $query_args['meta_query'] );
    return $query_args;
}

// The widget products query
add_filter( 'woocommerce_products_widget_query_args', 'custom_products_widget_query_arg', 10, 1 );
function custom_products_widget_query_arg( $query_args ) {
    //if( ! is_admin() )
        $query_args['meta_query'] = custom_meta_query( $query_args['meta_query'] );
    return $query_args;
}

add_filter( 'pre_get_posts', 'custom_pre_get_posts_query' );
function custom_pre_get_posts_query( $q ) {
    //i_print($q);
    //if(  !is_admin() ) {
        if ($q->is_main_query()) {
            $meta_query = $q->get('meta_query');
            if (!is_array($meta_query)) {
                $meta_query = [];
            }
            $meta_query[] = array(
                'key' => 'cplink_bilpay_product',
                'value' => '1',
                'compare' => 'NOT EXISTS',
            );
            $q->set('meta_query', $meta_query);

            remove_filter('pre_get_posts', 'custom_pre_get_posts_query');
        }
    //}
}
