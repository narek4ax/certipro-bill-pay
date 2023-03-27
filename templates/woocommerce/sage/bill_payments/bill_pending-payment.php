<div id="bp_pending_payments">
    <h3>Pending Payments</h3>

    <?php
    $my_bill_payments = $bill_payments;
    //$panding_bill_payments = $bill_payments_pending;

    //i_print($panding_bill_payments);
    //i_print($my_bill_payments);

    echo '<table class="sage-bill_payments-table sage-table woocommerce-orders-table my_account_orders">';
    echo '<thead><tr><th><span class="nobr">Date</span></th><th><span class="nobr">Confirmation</span></th><th><span class="nobr">Reference</span></th>';
    echo '<th><span class="nobr">Deposit</span></th><th><span class="nobr">Amount</span></th><th><span class="nobr"></span></th>';
    echo '</tr></thead>';

    //    }
    if( count($my_bill_payments) ) {
        foreach ($my_bill_payments as $bill_payment) { //i_print($bill_payment);
            $bill_payment_id = $external_cash_number = (isset($bill_payment['ID']))?$bill_payment['ID']:$bill_payment['external_cash_number'];
            $deposit_date = (isset($bill_payment['post_date']))?$bill_payment['post_date']:$bill_payment['deposit_date'];
            $total_amount = $bill_payment['total_amount'];
            $deposit_type = 'R'; //$bill_payment['deposit_type'];
            $deposit_number = $bill_payment['deposit_number']; //i_print($sales_order);
            $payment_reference = $bill_payment['credit_card_entry_number']; //?
            $deposit_type_label = '';
            if( isset($deposit_types[$deposit_type]) )
                $deposit_type_label = $deposit_types[$deposit_type]['short'];

            $bill_payment_view_url = wc_get_endpoint_url('bill_payment', $bill_payment_id, wc_get_page_permalink('myaccount'));

            echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order cplink_tr">';

            echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__bp_date" data-title="Date\">';
            if ($deposit_date)
                echo date('m/d/Y', strtotime($deposit_date));
            echo '</td>';

            echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__bp_confirm" data-title="Order">';
            echo $external_cash_number;
            echo '</td>';

            echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__bp_reference" data-title="">';
            echo $deposit_type_label . ' ' . $payment_reference;
            echo '</td>';

            echo '<td class="bp_deposit">';
            echo '' . $deposit_number . '';
            echo '</td>';

            echo '<td class="bp_amount">';
            echo '' . wc_price($total_amount) . '';
            echo '</td>';

            echo '<td class="bp_pp_view">';
            echo '<a href="'.$bill_payment_view_url.'" class="woocommerce-button view cplink_toggle_btn">'.__( 'View', 'woocommerce' ).'</a>';
            echo '</td>';

            echo '</tr>';
        }
    } else {
        echo '<div class="message info empty"><span>'.__('You have placed no payments.', CPLINK_NAME).'</span></div>';
    }

    //}

    echo '</table>';
    ?>

</div>