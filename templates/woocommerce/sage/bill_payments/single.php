<?php

global $wpdb, $cp_scope_cf, $cp_modules_cf;
$woocommerce_order_items_table = $wpdb->prefix.'woocommerce_order_items';
$woocommerce_order_itemmeta_table = $wpdb->prefix.'woocommerce_order_itemmeta';
//$bill_payment_id = get_query_var('bill_payments');
//i_print($scopConfig);

$deposit_types = CPBP::$deposit_types;

$bill_payment = CPBP::get_cpbp_order($bill_payment_id); //i_print($bill_payment);

if (count($bill_payment)) {
    $external_cash_number = (isset($bill_payment['ID']))?$bill_payment['ID']:$bill_payment['external_cash_number'];
    $deposit_date = (isset($bill_payment['post_date']))?$bill_payment['post_date']:$bill_payment['deposit_date'];
    $deposit_type = 'R'; //$bill_payment['deposit_type'];
    $deposit_number = $bill_payment['deposit_number']; //i_print($sales_order);
    $payment_reference = $bill_payment['credit_card_entry_number']; //?
    $deposit_type_label = '';
    if( isset($deposit_types[$deposit_type]) )
        $deposit_type_label = $deposit_types[$deposit_type]['short'];


    $bill_payment_lines = array();

    //Get order items / Invoices
    if( isset( $bill_payment['external_cash_number'] ) ){ //already exported
    } else {
    }
    $woocommerce_order_items = $wpdb->get_results("SELECT * FROM $woocommerce_order_items_table WHERE `order_id` = $bill_payment_id");
    if(!empty($woocommerce_order_items)){
        foreach ($woocommerce_order_items as $woocommerce_order_item_key => $item){
            $order_item_data = [];
            $order_item_id = $item->order_item_id;

            $order_itemmeta_invoice_no = $wpdb->get_row("SELECT meta_value FROM $woocommerce_order_itemmeta_table WHERE `order_item_id` = $order_item_id and `meta_key` = 'Invoice No'");
            $order_itemmeta_line_total = $wpdb->get_row("SELECT meta_value FROM $woocommerce_order_itemmeta_table WHERE `order_item_id` = $order_item_id and `meta_key` = '_line_total'");

            $order_item_data['invoice_number'] = $order_itemmeta_invoice_no->meta_value;
            $order_item_data['amount_posted'] = max($order_itemmeta_line_total->meta_value, 0);

            array_push($bill_payment_lines, $order_item_data);
        }
    }

    echo '<div class="row view_order_top_div cpbp_view_order_top_div">';
    echo '<div class="large-7 col"> <h2>Invoices Paid</h2> </div>';
    echo '<div class="large-5 col view_order_actions_col"> </div>';
    echo '</div>';

    echo '<table class="sage-bill_payment-table sage-table woocommerce-orders-table my_account_orders">'; //
    echo '<thead><tr>';
    echo '<th><span class="nobr">Invoice Number</span></th><th><span class="nobr">Payment Amount</span></th>';
    echo '</tr></thead>';

    echo '<tbody>';
    if (count($bill_payment_lines))
        foreach ($bill_payment_lines as $bill_payment_line) {
            echo '<tr class="">';
            echo '<td>' . $bill_payment_line['invoice_number'] . '</td>';
            echo '<td>' . wc_price($bill_payment_line['amount_posted']) . '</td>';
            echo '</tr>';
        }
    echo '</tbody>';

    echo '</table>';

    //echo '<marquee direction="right" style="cursor: default;"><h4>To be continued <i class="fa fa-arrow-right"></i> </h4></marquee>';
    ?>
    <div class="sage_additional_info cpbp_additional_info">
        <h2><?php _e('Payment Information', CPLINK_NAME); ?></h2>
        <section class="woocommerce-customer-details">
            <div class="row">
                <div class="col large-12">
                    <div class="col-inner">
                        <h4><?php _e('Additional', CPLINK_NAME); ?></h4>
                        <table class="sage-table">
                            <tbody>
                            <?php
                            $cp_bill_payment_data = array(
                                'Date' => date("M d, Y", strtotime($deposit_date)),
                                'Confirmation' => $external_cash_number,
                                'Reference' => $deposit_type_label . ' ' . $payment_reference,
                                'Deposit' => $deposit_number
                            );
                            foreach ($cp_bill_payment_data as $cp_bill_payment_data_name => $cp_bill_payment_data_val) {
                                echo '<tr>';
                                echo '<th scope="row">' . __($cp_bill_payment_data_name, CPLINK_NAME) . ':</th>';
                                echo '<td>' . $cp_bill_payment_data_val . '</td>';
                                echo '</tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php
}

?>

