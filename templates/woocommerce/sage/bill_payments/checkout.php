<?php
global $cp_scope_cf, $cp_modules_cf;

?>

<div id="bp_cash_table_wrapper">
    <?php
    if (!WC()->cart->is_empty()) {
        echo do_shortcode('[woocommerce_checkout]');
    }

    ?>
</div>