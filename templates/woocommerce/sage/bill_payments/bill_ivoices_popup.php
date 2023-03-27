<div class="bp_add_invoices">
    <a href="#invoices_popup" id="bp_add_invoice_btn" class="fancybox button bp_cash_button bp_button">
        Add Invoice(s)            </a>
    <div id="invoices_popup">
        <h2 id="invoices_popup_title">Invoice</h2>
        <form action="" id="invoices_popup_search_form">
            <div>
                <input type="text" value="" name="invoice_no" id="s_invoice_no" placeholder="Invoice #">
            </div>
            <div>
                <input type="text" value="" name="s_inv_date_from" id="s_inv_date_from" placeholder="Date From" autocomplete="off">
            </div>
            <div>
                <input type="text" value="" name="s_inv_date_to" id="s_inv_date_to" placeholder="Date To" autocomplete="off">
            </div>
            <div class="invoices_popup_search loading">
                <button type="submit" id="invoices_popup_search_btn" class="cp_button button">Search</button>
            </div>
        </form>
        <div class="invoices_popup_grid">
            <table class="sage-table invoices_popup_table">
                <thead>
                    <tr>
                        <th class="bp_popup_checkbox_label">&nbsp;</th>
                        <th class="bp_popup_invoice_label">Invoice #</th>
                        <th class="bp_popup_date_label">Date</th>
                        <th class="bp_popup_amount_label">Amount</th>
                        <th class="bp_popup_balance_label">Balance</th>
                    </tr>
                </thead>
                <tbody>
<!--                    <tr>
                        <td class="bp_popup_checkbox">
                            <input name="xxx" class="bp_invoice_items_selected" type="checkbox">
                        </td>
                        <td class="bp_popup_invoice">0</td>
                        <td class="bp_popup_date">05/30/2025</td>
                        <td class="bp_popup_amount">
                            <span>$</span>
                            <input name="xxx" class="bp_invoice_items_posted" type="number">
                        </td>
                        <td class="bp_popup_balance">0</td>
                    </tr>-->
                </tbody>
            </table>
        </div>
        <div class="bp_popup_actions">
                <p class="bp_invoice_count_row">Total <span class="bp_invoice_count">X</span> items found</p>
        </div>
        <div class="bp_action_add_all_items">
            <a class="button primary bp_action_add_items" type="button" data_role="action"><span>Add</span></a>
        </div>
    </div>
</div>