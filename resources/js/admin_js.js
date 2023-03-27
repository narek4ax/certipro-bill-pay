$ = jQuery;


var minDate;
var maxDate;

var queue_table = {};
jQuery(document).ready(function ($) {


    queue_table = $('#cpbp_queue-area-table').DataTable({
        colReorder: false,
        ordering: false
    });
    $(document).on('change', '.admin__control-checkbox', function() {
        checkboxes();
    });
    function checkboxes()
    {
        let a_find_selector = 'input[type="checkbox"].admin__control-checkbox';
        var find_selector = queue_table.rows().nodes();
        var find_selector_obj = $(find_selector).find(a_find_selector);
        console.log(find_selector);
        var count = 0;

        for (var i = 0; i < find_selector_obj.length; i++) {
            if (find_selector_obj[i].type == "checkbox" && find_selector_obj[i].checked == true) {
                count++;
            }

        }
        $('#cpbp_adminhtml_queue_grid_massaction-count .selected_count').html(count);
    }

    $('#cpbp_mass-select-checkbox').change(function () {
        if ($(this).prop("checked") === true) {
            $('.i_check_item').prop('checked', true);
        } else if ($(this).prop("checked") === false) {
            $('.i_check_item').prop('checked', false);
        }
    });
    $('#cpbp_adminhtml_queue_grid_massaction-mass-select').change(function () {
        let myvalue = $(this).val();
        let row_nodes;
        let all_row_nodes;
        let find_selector = 'input[type="checkbox"].admin__control-checkbox';
        switch(myvalue) {
            case 'selectAll':
                row_nodes = queue_table.rows().nodes();
                $(row_nodes).find(find_selector).prop('checked', true);
                checkboxes();
                break;
            case 'unselectAll':
                row_nodes = queue_table.rows().nodes();
                $(row_nodes).find(find_selector).prop('checked', false);
                checkboxes();
                break;
            case 'selectVisible':
                all_row_nodes = queue_table.rows().nodes();
                $(all_row_nodes).find(find_selector).prop('checked', false);
                row_nodes = queue_table.rows({ page: 'current' }).nodes();
                $(row_nodes).find(find_selector).prop('checked', true);
                checkboxes();
                break;
            case 'unselectVisible':
                row_nodes = queue_table.rows({ page: 'current' }).nodes();
                $(row_nodes).find(find_selector).prop('checked', false);
                checkboxes();
                break;
            default:
            // code block
        }
        $(this).val('');
    });

    $('.cplink_toggle_btn').click(cplink_toggle_el);

    function cplink_toggle_el(e) {
        e.preventDefault();
        let cplink_el = $(this).data('cplink_el');
        let cplink_toggle_el = $('.cplink_toggle_el[data-cplink_el=' + cplink_el + ']');
        if (cplink_toggle_el.hasClass('cplink_hidden')) {
            $(this).text($(this).data('hide_txt'));
            cplink_toggle_el.removeClass('cplink_hidden').slideDown(300);
        } else {
            $(this).text($(this).data('view_txt'));
            cplink_toggle_el.addClass('cplink_hidden').slideUp(300);
        }

        return false;
    }

    //Created Time
    var dateFormat = "yy-mm-dd",
        from = $("#cpbp_created_from")
            .datepicker({
                dateFormat: 'yy-mm-dd',
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1
            })
            .on("change", function () {
                to.datepicker("option", "minDate", getDate(this));
            }),
        to = $("#cpbp_created_to").datepicker({
            dateFormat: 'yy-mm-dd',
            defaultDate: "+1w",
            changeMonth: true,
            numberOfMonths: 1
        })

            .on("change", function () {
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

    //    Updated Time
    var dateFormat_update = "yy-mm-dd",
        from_update = $("#cpbp_updated_from")
            .datepicker({
                dateFormat: 'yy-mm-dd',
                defaultDate: "+1w",
                changeMonth: true,
                numberOfMonths: 1
            })
            .on("change", function () {
                to_update.datepicker("option", "minDate", updateDate(this));
            }),
        to_update = $("#cpbp_updated_to").datepicker({
            dateFormat: 'yy-mm-dd',
            defaultDate: "+1w",
            changeMonth: true,
            numberOfMonths: 1
        })
            .on("change", function () {
                from_update.datepicker("option", "maxDate", updateDate(this));
            });

    function updateDate(element) {
        var date;
        try {
            date = $.datepicker.parseDate(dateFormat_update, element.value);
        } catch (error) {
            date = null;
        }
        return date;
    }

    //    Filter On Created Time
    $('#cpbp_created_from, #cpbp_created_to').on('change', function () {
        console.log("change3 " + $(this).val());
        queue_table.draw();
    });
    //    Filter On Updated Time
    $('#cpbp_updated_from, #cpbp_updated_to').on('change', function () {
        console.log("change2 " + $(this).val());
        queue_table.draw();
    });

    // Apply the filter
    $("#cpbp_queue-area-table  thead input:not(.hasDatepicker)").on('keyup change', function () {
        queue_table.column($(this).parent().index() + ':visible').search(this.value).draw();
    });
    //    Reset All Filters
    $('.action-reset').on('click', function () {
        $("#cpbp_queue-area-table  thead input").val('');
        $("#cpbp_queue-area-table  thead select").val('');
        queue_table.search('').columns().search('').draw();
    });
    $("#cpbp_queue-area-table thead tr.data-grid-filters td").each(function (i) {
        var select = $(this).find('select').on('change', function () {
            queue_table.column(i)
                .search($(this).val())
                .draw();
        });
    });
    $.fn.dataTable.ext.search.push(
        function (settings, data, dataIndex) {
            var created_date = toDate(data[2]);
            var updated_date = toDate(data[3]);

            var created_min = toDate($('#cpbp_created_from').val() + ' 00:00:01');
            var created_max = toDate($('#cpbp_created_to').val() + ' 23:59:59');
            var updated_min = toDate($('#cpbp_updated_from').val() + ' 00:00:01');
            var updated_max = toDate($('#cpbp_updated_to').val() + ' 23:59:59');
            if (
                (isNaN(created_min) || created_date >= created_min) &&
                (isNaN(created_max) || created_date <= created_max) &&
                (isNaN(updated_min) || updated_date >= updated_min) &&
                (isNaN(updated_max) || updated_date <= updated_max)

            ) {
                return true;
            }
            return false;
        }
    );

    function toDate(str) {
        return new Date(str.replace(/^(\d{2}\-)(\d{2}\-)(\d{4})$/, '$2$1$3')).getTime();
    }

    /*ajax for export order*/
    var action_in_process = false;
    $('.cplink_close_response').click(cplink_close_response);

    function cplink_close_response() {
        $(this).parents('.cplink_response_wrapper').hide();
    }

    function show_msg_response(r_message = '', r_type = 'loading') {
        $('.cplink_response_txt').html(r_message).parent().removeClass('cplink_resp_loading cplink_resp_success cplink_resp_error').addClass('cplink_resp_' + r_type).parents('.cplink_response_wrapper').showInlineBlock();

        let result_icon = $('.cplink_response .result_icon');
        if (result_icon.attr('data-' + r_type)) {
            result_icon.children('i').attr('class', 'dashicons ' + result_icon.attr('data-' + r_type));
        } else {
            result_icon.children('i').attr('class', 'dashicons ')
        }
    }

    $(document).on('click', '#cpbp_queue-area-table .export_order', function (event) {
        event.preventDefault();

        var order_id = $(this).attr('data-order-id');
        if (order_id && $.isNumeric(order_id)) {
            var all_orders = [];
            all_orders.push(parseInt(order_id));
            if (action_in_process)
                return false;
            exportOrderAjax(all_orders);
        }
    });
    $(document).on('click', '#cpbp_id_global_action_for_queue', function (event) {
        var actionsSelectValue = $(this).parent().find('#cpbp_adminhtml_queue_grid_massaction-select').val();
        if (actionsSelectValue == 'order_export_now') {
            exportSelectedOrders();
        } else if (actionsSelectValue == 'delete') {
            deleteSelectedOrders();
        }
    });

    function exportSelectedOrders() {
        var all_orders = [];
        row_nodes = queue_table.rows().nodes();
        $(row_nodes).find('input:checkbox[name=queue_ids]:checked').each(function () {
            /*$("input:checkbox[name=queue_ids]:checked").each(function () {*/
            all_orders.push(parseInt($(this).val()));
        });
        if (action_in_process)
            return false;

        exportOrderAjax(all_orders);
    }

    function deleteSelectedOrders() {
        var all_orders = [];
        row_nodes = queue_table.rows().nodes();
        $(row_nodes).find('input:checkbox[name=queue_ids]:checked').each(function () {
            all_orders.push(parseInt($(this).val()));
        });

        if (action_in_process)
            return false;


        if (all_orders.length > 0) {
            if (confirm('Are you sure you want to delete?')) {
                show_msg_response(cplink_infos.deleteMessage, 'loading');
                $.ajax({
                    url: cplink_infos.ajax_url,
                    type: 'POST',
                    dataType: "json",
                    data: {
                        'action': 'i_cpbp_delete_queue_orders',
                        'order_ids': all_orders
                    },
                    beforeSend: function (xhr) {

                    },
                    success: function (data) {
//                    console.log(data);
                        if (data.success) {
                            show_msg_response(cplink_infos.deleteSuccessMessage, 'success');
                        } else {
                            show_msg_response(cplink_infos.deleteErrorMessage, 'error');
                        }

                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    }
                });
                action_in_process = false;
            }
        } else {
            action_in_process = false;
            return false;
        }
    }

    function exportOrderAjax(orders_to_export = []) {
        if (orders_to_export.length > 0) {
            show_msg_response(cplink_infos.exportMessage, 'loading');
            $.ajax({
                url: cplink_infos.ajax_url,
                type: 'POST',
                dataType: "json",
                data: {
                    'action': 'i_cpbp_export_orders_to_sage_request',
                    'order_ids': orders_to_export
                },
                beforeSend: function (xhr) {

                },
                success: function (data) {
//                    console.log(data);
                    $('.cplink_settings_response').addClass('export_response');
                    let errors_html = '';
                    if( data.error_results && Object.keys(data.error_results) ){
                        $.each(data.error_results, function(r_id, r_msg){
                            errors_html+= '<div class="cplink_resp_error">'+r_id+' - '+r_msg+'</div>';
                        });
                    }
                    data.html+= errors_html;

                    if (data.success) {
                        /*show_msg_response(cplink_infos.exportSuccessMessage, 'success');*/
                        show_msg_response(data.html, 'loading');
                    } else {
                        show_msg_response(cplink_infos.exportErrorMessage + ' ' + data.html, 'error');
                        /*setTimeout(function () {
                         location.reload();
                         }, 2000);*/
                    }

                }
            });
            action_in_process = false;
        } else {
            action_in_process = false;
            return false;
        }
    }

    $(document).on('click', '.export_response .cplink_close_response', function () {
        console.log('Trying to reload');
        location.reload();
    });

    /*$('.import_to_queue').on('click', function (event) {
        event.preventDefault()
        var clickedButton = $(this);
        var orderId = clickedButton.attr('data-order-id');
        console.log(orderId);
        if ($.isNumeric(orderId)) {
            $.ajax({
                url: cplink_infos.ajax_url,
                type: 'POST',
                dataType: "json",
                data: {
                    'action': 'i_import_order_to_queue',
                    'order_id': orderId
                },
                beforeSend: function (xhr) {

                },
                success: function (data) {
                    console.log(data);
                    var buttonParent = clickedButton.parent();
                    if (data.success) {
                        buttonParent.html('<div class="ajax_result alert alert-success">' + data.html + '</div>');
                        setTimeout(function () {
                            $('.ajax_result').parents('tr').remove();
                        }, 5000);
                    } else {
                        buttonParent.append('<div class="ajax_result alert alert-danger">' + data.html + '</div>');
                        setTimeout(function () {
                            $('.ajax_result.alert-danger').remove();
                        }, 3000);
                    }
                }
            });
        }
    });*/

});

jQuery.fn.showInlineBlock = function () {
    return this.css('display', 'inline-flex');
};



