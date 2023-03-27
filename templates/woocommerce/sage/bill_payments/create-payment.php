<?php
global $cp_scope_cf, $cp_modules_cf;
$invoices = CPLINK::get_sage_invoices(1, -1, 'id,invoice_no,balance,total,payments_today');
/*global $woocommerce;
$items = $woocommerce->cart->get_cart();*/
/*i_print($invoices);*/
$user_id = get_current_user_id();
if( !$user_id )
    exit;

$cpbp_cart = CPBP::get_cpbp_cart();
//i_print($invoices);
$pending_invoices_list = CPBP::cpbp_get_invoice_pending_payments();
foreach ($invoices as $invoice_key=>$invoice){
    $total_balance = $invoice['balance'] - $invoice['payments_today'];
    $invoice_no = $invoice['invoice_no'];
    if(!empty($pending_invoices_list) && array_key_exists($invoice_no,$pending_invoices_list)){
        $total_balance = $total_balance - $pending_invoices_list[$invoice_no];
    }
    $invoices[$invoice_key]['balance'] = $total_balance;
}
?>
<script>
    var invoicesData = <?php echo json_encode($invoices); ?>;
</script>
<div id="bp_cash_table_wrapper">
    <?php if(!isset($_GET['deposit'])){?>
    <?php require_once( CPBP_PLUGIN_DIR . 'templates/woocommerce/sage/bill_payments/bill_ivoices_popup.php' ); ?>
    <table class="sage-table bp_elink_grid cash" id="bp_cash_table">
        <thead>
            <tr>
                <th class="bp_create_invoice_label">Invoice #</th>
                <th class="bp_create_date_label">Date</th>
                <th class="bp_cretae_payment_amount_label">Payment Amount</th>
                <th class="bp_create_balance_label">Balance</th>
                <th class="bp_create_view_label">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $grandTotal = 0;
        if ( count($cpbp_cart) ) {
            $counter = 0;
            foreach ($cpbp_cart as $cart_item_key => $cart_item) {
                if($cart_item['bp_product_data']['bp_invoice_no']) {
                    $product_id = $cart_item['product_id'];
                    $grandTotal += $cart_item['bp_product_data']['bp_invoice_amount'];
                    ?>
                    <tr class="bp_cart_item_tr">
                        <td class="bp_create_invoice">
                            <?php echo $cart_item['bp_product_data']['bp_invoice_no']; ?>
                            <input name="inv_items[<?php echo $counter; ?>][item_id]" id="invItems_<?php echo $counter; ?>_itemId" class="bp_invoice_item_id" type="hidden" value="<?php echo $cart_item['bp_product_data']['bp_invoice_id']; ?>" />
                        </td>
                        <td class="bp_create_date"><?php echo date( 'm/d/Y', strtotime( $cart_item['bp_product_data']['bp_invoice_date'] ))?></td>
                        <td class="bp_cretae_payment_amount">
                            <span>$</span>
                            <input class="bp_item_to_find_amount_posted" type="number"
                                   value="<?php echo $cart_item['bp_product_data']['bp_invoice_amount']; ?>">
                        </td>
                        <td class="bp_create_balance">$<?php echo $cart_item['bp_product_data']['bp_balance']; ?></td>
                        <td class="bp_create_view">
                            <?php
                            echo '<a href="#" class="remove cp_remove_from_cart_button" aria-label="Remove this item" data-product_id="'.$product_id.'" data-cart_item_key="'.$cart_item_key.'" data-product_sku="">×</a>';
                            ?>

                        </td>
                    </tr>

                    <?php
                    $counter++;
                }
            }
        }
        ?>

        <tr class="new_bill_pay_row">
            <td class="bp_create_invoice">
                <input class="bp_item_to_find_inv_number" type="text" value="">
            </td>
            <td class="bp_create_date">-</td>
            <td class="bp_cretae_payment_amount">
                <span>$</span>
                <input class="bp_item_to_find_amount_posted" type="number" value="">
            </td>
            <td class="bp_create_balance">-</td>
            <td class="bp_create_view"></td>
        </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>&nbsp;</td>
                <td class="bp_grand_total">
                    <strong>Grand Total</strong>
                </td>
                <td class="bp_grand_total_price">
                    <strong>
                        <span id="bp_grand_total" class="bp_price">$<span class="price_wrapper"><?php echo $grandTotal; ?></span></span>
                    </strong>
                </td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tfoot>
    </table>
    <div>
        <a id="bill_invoices" class="button bp_btn_checkout bp_input_buttons" href="#">Place Payment</a>
    </div>
    <?php }else{
        ?>
        <table class="sage-table bp_elink_grid deposit">
            <thead>
            <tr>
                <th ><?php _e('Deposit Amount', CPBP_NAME)?></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td data-rwd-label="Deposit Amount">
                    <span>$</span>
                    <input id="deposit_amount" type="number" value="0" autocomplete="off" required="required" >
                </td>
            </tr>
            </tbody>
        </table>
        <div>
            <a id="bill_invoices_deposit" class="button bp_btn_checkout bp_input_buttons" href="#"><?php _e('Deposit', CPBP_NAME)?></a>
        </div>
    <?php } ?>
</div>