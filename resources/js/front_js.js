jQuery(document).ready(function ($) {
    var dateFormat = "mm/dd/yy",
        from = $("#s_inv_date_from, #sf_bp_date_from, #bp_date_from").datepicker({
            dateFormat: 'mm/dd/yy',
            defaultDate: "+1w",
            changeMonth: true,
            numberOfMonths: 1
        }).on("change", function () {
            to.datepicker("option", "minDate", getDate(this));
        }),
        to = $("#s_inv_date_to, #sf_bp_date_to, #bp_date_to").datepicker({
            dateFormat: 'mm/dd/yy',
            defaultDate: "+1w",
            changeMonth: true,
            numberOfMonths: 1
        }).on("change", function () {
            from.datepicker("option", "maxDate", getDate(this));
        });

    function getDate(element) {
        var date;
        try {
            date = $.datepicker.parseDate(dateFormat, element.value);
        } catch (error) {
            date = null;
        }

        return date;
    }


    var isAjax = false;
    let thiss, ajax_request;

    $(document).on('click', '.bp_popup_checkbox_tr', function (e) {
        let terget_el = $(e.target);
        if (!terget_el.is('input')) {
            $(this).find('.bp_popup_checkbox input[type=checkbox]').click();
        }
    });

    $(document).on('click', '#bp_add_invoice_btn', function (e) {
        e.preventDefault();
        if (isAjax) {
            return false
        }
        thiss = $(this);
        thiss.addClass('loading');

        var fancySelector = $(this).attr('href');
        if ($(fancySelector).length > 0) {
            //console.log('fancySelector inside condition 1 ', fancySelector);
            isAjax = true;
            $.ajax({
                url: cpbp_infos.ajax_url,
                type: 'POST',
                dataType: "json",
                data: {
                    'action': 'cpbp_get_invoices',
                },
                beforeSend: function (xhr) {

                },
                success: function (data) {
                    //console.log('data of get_invoices ajax ', data);
                    if (data.status) {
                        $.fancybox.open($(fancySelector));
                        $('.invoices_popup_table tbody').html(data.html);
                        rend_invoices_popup();
                    }
                    isAjax = false;

                    thiss.removeClass('loading');
                }
            });
        }
    });

    function rend_invoices_popup() {
        $('#bp_cash_table .bp_create_invoice input[name$="[item_id]"]').each(function () {
            let item_id = $(this).val();
            if ($('.invoices_popup_table .bp_popup_invoice input[name$="[item_id]"][value="' + item_id + '"] ').length > 0) {
                $('.invoices_popup_table .bp_popup_invoice input[name$="[item_id]"][value="' + item_id + '"]').parents('tr').remove();
            }

        });
        let rows_length = $('.invoices_popup_table tbody tr').length;
        $('.bp_invoice_count').text(rows_length);
    }

    $('body').on('submit', '#invoices_popup_search_form', invoices_popup_search);

    function invoices_popup_search(e) {
        e.preventDefault();
        if (ajax_request)
            ajax_request.abort();

        thiss = $(this).find('.cp_button');
        thiss.addClass('loading');

        var info = {};
        info['action'] = 'cpbp_get_invoices';
        info['s_invoice_no'] = $('#s_invoice_no').val();
        info['s_inv_date_from'] = $('#s_inv_date_from').val();
        info['s_inv_date_to'] = $('#s_inv_date_to').val();

        ajax_request = $.post(cpbp_infos.ajax_url, info).done(function (data) {
            data = JSON.parse(data);
            $('.invoices_popup_table tbody').html(data.html);
            rend_invoices_popup();

            thiss.removeClass('loading');
        });

        return false;
    }

    $('body').on('blur', '#bp_cash_table .bp_item_to_find_amount_posted', function(){
        let totalPrice = 0;
        if(Number($(this).val()) > 0){
            $(this).removeClass('error_field');
        }else{
            $(this).addClass('error_field');
        }
        $('#bp_cash_table .bp_cart_item_tr').each(function(){
            let billAmount = $(this).find('.bp_item_to_find_amount_posted').val();
            totalPrice = totalPrice + Number(billAmount);
        });
        $('#bp_grand_total .price_wrapper').text(totalPrice);
    });

    $('body').on('click', '.cp_remove_from_cart_button', cp_remove_from_cart);

    function cp_remove_from_cart(e) {
        e.preventDefault();

        /*if (ajax_request)
            ajax_request.abort();*/

        let thiss = $(this).parents('.bp_cart_item_tr');
        if (thiss.hasClass('loading'))
            return false;

        thiss.addClass('loading');

        var info = {};
        info['action'] = 'cpbp_remove_from_cart';
        info['cart_item_key'] = $(this).attr('data-cart_item_key');

        ajax_request = $.post(cpbp_infos.ajax_url, info).done(function (data) {
            data = JSON.parse(data);

            thiss.removeClass('loading');
            thiss.remove();
            $('#bp_grand_total .price_wrapper').text((Number($('#bp_grand_total .price_wrapper').text()) - Number(thiss.find('.bp_item_to_find_amount_posted').val())).toFixed(2));
        });

        return false;
    }


    $(document).on('click', '#invoices_popup .bp_action_add_items', function (e) {
        e.preventDefault();
        if (isAjax) {
            return false
        }

        thiss = $(this);

        //console.log('.bp_invoice_items_selected:checked ', $('.bp_invoice_items_selected:checked').length);
        if ($('.bp_invoice_items_selected:checked').length > 0) {
            thiss.addClass('loading');
            var bill_invoices = [];
            $('.bp_invoice_items_selected:checked').each(function () {
                let invoiceId = $(this).parents('tr').find('.bp_invoice_item_id').val();
                let billAmount = $(this).parents('tr').find('.bp_invoice_items_posted').val();
                if ($.isNumeric(invoiceId) && $.isNumeric(billAmount)) {
                    bill_invoices.push({
                        'invoice_id': invoiceId,
                        'amount': billAmount
                    });
                }
            });
            if (bill_invoices.length > 0) {
                isAjax = true;
                $.ajax({
                    url: cpbp_infos.ajax_url,
                    type: 'POST',
                    dataType: "json",
                    data: {
                        'action': 'cpbp_create_bill_pay_cart', //'cpbp_create_bill_pay'
                        'bill_invoices': bill_invoices
                    },
                    beforeSend: function (xhr) {

                    },
                    success: function (data) {
                        //console.log('data of ajax ', data);
                        if (data.status) {
                            //location.reload(); return;
                            thiss.removeClass('loading');
                            let htmlToAppend = '';
                            let count_of_row = $('#bp_cash_table tbody tr').length - 1;
                            let totalPrice = 0;
                            $('.bp_invoice_items_selected:checked').each(function () {
                                let invoiceId = $(this).parents('tr').find('.bp_invoice_item_id').val();
                                let invoiceNumber = $(this).parents('tr').find('.bp_popup_invoice input[name$="[inv_no]"]').val();
                                let billAmount = $(this).parents('tr').find('.bp_invoice_items_posted').val();
                                let date = $(this).parents('tr').find('.bp_popup_date input[name$="[inv_date]"]').val();
                                let balance = $(this).parents('tr').find('.bp_popup_balance input[name$="[inv_balance]"]').val();
                                let product_id = '';
                                let cart_item_key = '';
                                $.each(data.orders_data, function (order_key, order_val) {
                                    if (order_val.bp_invoice_no == invoiceNumber) {
                                        product_id = order_val.product_id;
                                        cart_item_key = order_val.cart_item_key;
                                    }
                                });
                                totalPrice = totalPrice + Number(billAmount);
                                htmlToAppend += `<tr class="bp_cart_item_tr">
                                                    <td class="bp_create_invoice" data-th="Invoice #">${invoiceNumber}<input name="inv_items[${count_of_row}][item_id]" id="invItems_${count_of_row}_itemId" class="bp_invoice_item_id" type="hidden" value="${invoiceId}" /></td>
                                                    <td class="bp_create_date" data-th="Date">${date}</td>
                                                    <td class="bp_cretae_payment_amount" data-th="Payment Amount"><span class="bt-content">
                                                        <span>$</span>
                                                        <input class="bp_item_to_find_amount_posted" type="number" value="${billAmount}">
                                                    </span></td>
                                                    <td class="bp_create_balance" data-th="Balance">${balance}</td>
                                                    <td class="bp_create_view" data-th="&nbsp;"><a href="#" class="remove cp_remove_from_cart_button" aria-label="Remove this item" data-product_id="${product_id}" data-cart_item_key="${cart_item_key}" data-product_sku="">×</a></td>
                                                </tr>`;
                                count_of_row++;
                            });
                            $(htmlToAppend).insertBefore('#bp_cash_table tbody tr:last-child');
                            $('#bp_grand_total .price_wrapper').text((totalPrice + Number($('#bp_grand_total .price_wrapper').text())).toFixed(2));
                            $.fancybox.close();
                        }

                        isAjax = false;
                    }
                });
            }
        }

    })
    $(document).on('blur', '.new_bill_pay_row .bp_item_to_find_amount_posted', function () {
        $('.bp_item_to_find_inv_number').trigger('blur');
    })

    $(document).on('blur', '.bp_item_to_find_inv_number', function () {
        $('.bp_item_to_find_inv_number').removeAttr('style');
        let invoiceNumber = ($(this).val()).trim();
        let paymentAmouny = ($('.new_bill_pay_row .bp_item_to_find_amount_posted').val()).trim();
        var bill_invoices = [];
        if ($(".new_bill_pay_row .bp_item_to_find_amount_posted").is(":focus")) {
            return false;
        }
        $.each(invoicesData, function (key, val) {
            if (val.invoice_no == invoiceNumber && $('#bp_cash_table .bp_create_invoice input[name$="[item_id]"][value="' + val.id + '"] ').length <= 0) {
                bill_invoices.push({
                    'invoice_id': val.id,
                    'amount': (paymentAmouny == '' ? val.balance : paymentAmouny)
                });
            }
        });
        if (bill_invoices.length > 0) {

            isAjax = true;
            $.ajax({
                url: cpbp_infos.ajax_url,
                type: 'POST',
                dataType: "json",
                data: {
                    'action': 'cpbp_create_bill_pay_cart',
                    'bill_invoices': bill_invoices
                },
                beforeSend: function (xhr) {

                },
                success: function (data) {
                    //console.log('data of ajax ', data);
                    if (data.status) {
                        /*location.reload(); return;*/
                        let htmlToAppend = '';
                        let count_of_row = $('#bp_cash_table tbody tr').length - 1;
                        let totalPrice = 0;
                        $.each(data.orders_data, function (key, val) {
                            let invoiceId = val.bp_invoice_id;
                            let invoiceNumber = val.bp_invoice_no;
                            let billAmount = val.bp_invoice_amount;
                            let date = val.bp_invoice_date;
                            let balance = val.bp_balance;
                            let product_id = val.product_id;
                            let cart_item_key = val.cart_item_key;
                            totalPrice = totalPrice + Number(billAmount);
                            htmlToAppend += `<tr class="bp_cart_item_tr">
                                                    <td class="bp_create_invoice" data-th="Invoice #">${invoiceNumber}<input name="inv_items[${count_of_row}][item_id]" id="invItems_${count_of_row}_itemId" class="bp_invoice_item_id" type="hidden" value="${invoiceId}" /></td>
                                                    <td class="bp_create_date" data-th="Date">${date}</td>
                                                    <td class="bp_cretae_payment_amount" data-th="Payment Amount"><span class="bt-content">
                                                        <span>$</span>
                                                        <input class="bp_item_to_find_amount_posted" type="number" value="${billAmount}">
                                                    </span></td>
                                                    <td class="bp_create_balance" data-th="Balance">${balance}</td>
                                                    <td class="bp_create_view" data-th="&nbsp;"><a href="#" class="remove cp_remove_from_cart_button" aria-label="Remove this item" data-product_id="${product_id}" data-cart_item_key="${cart_item_key}" data-product_sku="">×</a></td>
                                                </tr>`;
                            count_of_row++;
                        });
                        $(htmlToAppend).insertBefore('#bp_cash_table tbody tr:last-child');
                        $('#bp_grand_total .price_wrapper').text((totalPrice + Number($('#bp_grand_total .price_wrapper').text())).toFixed(2));
                        $('.bp_item_to_find_inv_number').val('');
                        $('.new_bill_pay_row .bp_item_to_find_amount_posted').val('');
                        /*$.fancybox.close();*/
                    }
                    /*if(data.status) {
                     location.reload();
                     }*/
                    isAjax = false;
                }
            });
        } else {
            $('.bp_item_to_find_inv_number').css('border', '1px solid red');
        }
    });

    $(document).on('click', '#bill_invoices', function (e) {
        e.preventDefault();
        if (isAjax) {
            return false
        }
        var bill_invoices = [];
        var allAmountExists = true;
        $('#bp_cash_table tbody tr:not(last-child)').each(function () {
            let invoiceId = $(this).find('input[name$="[item_id]"]').val();
            let billAmount = $(this).find('.bp_item_to_find_amount_posted').val();
            if ($.isNumeric(invoiceId) && $.isNumeric(billAmount)) {
                if(billAmount > 0){
                    $(this).find('.bp_item_to_find_amount_posted').removeClass('error_field');
                    bill_invoices.push({
                        'invoice_id': invoiceId,
                        'amount': billAmount
                    });
                }else{
                    $(this).find('.bp_item_to_find_amount_posted').addClass('error_field');
                    allAmountExists = false;
                }
            }
        });
        if(!allAmountExists){
            return false;
        }
        if (bill_invoices.length > 0) {
            $(this).addClass('loading');

            isAjax = true;

            $.ajax({
                url: cpbp_infos.ajax_url,
                type: 'POST',
                dataType: "json",
                data: {
                    'action': 'cpbp_create_bill_pay',
                    'bill_invoices': bill_invoices
                },
                beforeSend: function (xhr) {

                },
                success: function (data) {
                    //console.log('data of ajax ', data);
                    isAjax = false;

                    if (data.status) {
                        window.location.href = cpbp_infos.checkout_page_url + '?bill_checkout' //window.location.href + '?bill_checkout';
                    }

                    $('#bill_invoices').removeClass('loading');
                }
            });
        }
    });

    $(document).on('click', '#bill_invoices_deposit', function (e) {
        e.preventDefault();
        if (isAjax) {
            return false
        }
        var bill_invoices = [];
        bill_invoices.push({
            'amount': $('#deposit_amount').val()
        });
        if (bill_invoices.length > 0) {
            $(this).addClass('loading');

            isAjax = true;

            $.ajax({
                url: cpbp_infos.ajax_url,
                type: 'POST',
                dataType: "json",
                data: {
                    'action': 'cpbp_create_bill_pay_deposit',
                    'bill_invoices': bill_invoices
                },
                beforeSend: function (xhr) {

                },
                success: function (data) {
                    //console.log('data of ajax ', data);
                    isAjax = false;

                    if (data.status) {
                        window.location.href = cpbp_infos.checkout_page_url + '?bill_checkout&deposit'
                        //window.location.href + '?bill_checkout';
                    }
                }
            });
        }
    });

});