<?php
global $cpbp_scope_cf;
$make_payment_url = wc_get_endpoint_url('create_bill_payment', '', wc_get_page_permalink('myaccount'));

if( isset($_GET['cpbp_thank_you']) ){
    echo '<div class="success_mesage"><h4>Your payment has been received</h4></div>';
}
?>
<?php if(isset($cpbp_scope_cf['allow_make_payments']) && $cpbp_scope_cf['allow_make_payments']){?>
<div class="button_wrapper make_payment_wrapper align_right">
    <?php
    echo '<a href="' . $make_payment_url . '" class="primary button">' . __('Make Payment', CPBP_NAME) . '</a>';
    ?>
    <?php if(isset($cpbp_scope_cf['allow_deposits']) && $cpbp_scope_cf['allow_deposits']){
        echo '<a href="' . $make_payment_url . '?deposit" class="primary button">' . __('Deposit', CPBP_NAME) . '</a>';
    } ?>
</div>
<?php } ?>

<?php
$deposit_types = CPBP::$deposit_types;
$my_bill_payments = $bill_payments;

require_once(CPBP_PLUGIN_DIR . 'templates/woocommerce/sage/bill_payments/bill_payment-search.php');
require_once(CPBP_PLUGIN_DIR . 'templates/woocommerce/sage/bill_payments/bill_pending-payment.php');


echo "<h3>Payment History</h3>";

echo '<table class="sage-bill_payments-table sage-table woocommerce-orders-table my_account_orders">';
echo '<thead><tr><th><span class="nobr">Date</span></th><th><span class="nobr">Confirmation</span></th><th><span class="nobr">Reference</span></th>';
echo '<th><span class="nobr">Deposit</span></th><th><span class="nobr">Invoice</span></th><th><span class="nobr">Amount</span></th>';
echo '</tr></thead>';

//global $cp_modules_cf;
//i_print($bill_payments_history);

if( $bill_payments_history && count($bill_payments_history) ) {
    foreach ($bill_payments_history as $bill_payment) {
        $deposit_number = $bill_payment['deposit_number']; //i_print($sales_order);
        $deposit_date = $bill_payment['deposit_date'];
        $deposit_type = $bill_payment['deposit_type'];
        if( isset( $bill_payment['posting_amount']) ){
            $total_amount = $bill_payment['total_amount']; //$bill_payment['posting_amount'];
        } else {
            $total_amount = $bill_payment['cash_amount'];
        }
        //$total_amount = $bill_payment['total_amount'];

        $external_cash_number = $bill_payment['external_cash_number'];
        $credit_card_entry_number = $bill_payment['credit_card_entry_number'];
        $invoice_number = $bill_payment['invoice_number'];
        $deposit_type_label = '';
        if( isset($deposit_types[$deposit_type]) )
            $deposit_type_label = $deposit_types[$deposit_type]['short'];

        echo '<tr class="woocommerce-orders-table__row woocommerce-orders-table__row--status-processing order cplink_tr">';

        echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__bp_date" data-title="Date">';
        if ($deposit_date)
            echo date('m/d/Y', strtotime($deposit_date));
        echo '</td>';

        echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__bp_confirm" data-title="Order">';
        echo $external_cash_number;
        echo '</td>';

        echo '<td class="woocommerce-orders-table__cell woocommerce-orders-table__bp_reference" data-title="">';
        /*if (\CertiProSolutions\Elinkcash\Helper\Common\Deposittype::_CREDIT_CARD == $_item->getDepositType()) {
            echo __('CC') . ' ' . $_item->getCreditCardEntryNumber();
        } else if (\CertiProSolutions\Elinkcash\Helper\Common\Deposittype::_ACH == $_item->getDepositType()) {
            echo __('ACH') . ' ' . $_item->getCreditCardEntryNumber();
        } else {
            echo __('CHK') . ' ' . $_item->getCheckNumber();
        }*/
        echo $deposit_type_label . ' ' . $credit_card_entry_number;
        echo '</td>';

        echo '<td class="woocommerce-orders-table__bill-pay-deposit bp_deposit">';
        echo '' . $deposit_number . '';
        echo '</td>';

        echo '<td class="woocommerce-orders-table__bill-pay-invoice">';
        echo $invoice_number;
        echo '</td>';

        echo '<td class="woocommerce-orders-table__bill-pay-amount">';
        echo '' . wc_price($total_amount) . '';
        echo '</td>';

        echo '</tr>';
    }
}

echo '</table>';

?>

