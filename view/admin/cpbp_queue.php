<?php
global $wpdb;
$table_prefix = CPBP_DB_PREFIX;

//Table structure for table `_queue`
$table_name = $table_prefix . 'queue';
$result = $wpdb->get_results("SELECT * FROM $table_name where active = 1 ORDER BY id DESC");
$countOfOrders = count($result);
//i_print($result);
?>
<div id="cpbp_queue-area">
    <h1 id="cpbp_queue-area-title">Queue</h1>
    <div id="cpbp_adminhtml_queue_grid" data-grid-id="cpbp_adminhtml_queue_grid">
        <div class="admin__data-grid-header admin__data-grid-toolbar">
            <div class="_massaction admin__data-grid-header-row row">
                <div id="cpbp_adminhtml_queue_grid_massaction" class="admin__grid-massaction col-md-6">

                    <form action="" id="cpbp_adminhtml_queue_grid_massaction-form" method="post" novalidate="novalidate">
                        <div class="admin__grid-massaction-form">
                            <select id="cpbp_adminhtml_queue_grid_massaction-select"
                                    class="required-entry local-validation admin__control-select "
                                    data-ui-id="cpbp_elink-queue-grid-massaction-select">
                                <option class="admin__control-select-placeholder" value="" selected="">Actions</option>
                                <option value="delete">Delete</option>
                                <option value="order_export_now">Export Orders</option>
                            </select>
                            <span class="outer-span" id="cpbp_adminhtml_queue_grid_massaction-form-hiddens"></span>
                            <span class="outer-span" id="cpbp_adminhtml_queue_grid_massaction-form-additional"></span>
                            <button id="cpbp_id_global_action_for_queue" title="Submit" type="button"
                                    class="button button-primary"
                                    data-ui-id="cpbp_widget-button-2">
                                <span>Submit</span>
                            </button>
                        </div>
                    </form>


                    <div class="admin__control-support-text">
                    <span id="cpbp_adminhtml_queue_grid-total-count" data-ui-id="cpbp_elink-queue-grid-total-count">
                        <?php echo $countOfOrders; ?>
                    </span>
                        records found
                        <span id="cpbp_adminhtml_queue_grid_massaction-count" class="mass-select-info _empty">
                            <strong class="selected_count" data-role="counter">0</strong>
                        <span>selected</span>
                    </span>
                    </div>
                </div>

            </div>
            <div class="admin__data-grid-header-row">
                <div class="admin__filter-actions">
                    <button id="cpbp_reset-filter-button" title="Reset Filter" type="button"
                            class="button button-primary action-reset" onclick="" data-action="grid-filter-reset"
                            data-ui-id="cpbp_widget-button-0">
                        <span>Reset Filter</span>
                    </button>
                </div>
            </div>
        </div>
        <div class="admin__data-grid-wrap admin__data-grid-wrap-static">

            <table id="cpbp_queue-area-table" class="wp-list-table widefat fixed striped table-view-list cp_table">
                <!-- Rendering column set -->
                <thead>
                <tr>
                    <th><span>Mass Actions</span></th>
                    <th class="data-grid-th _sortable _descend  col-real_order_id"><span>Cache #</span></th>
                    <th class="data-grid-th _sortable not-sort  col-created_time"><span>Created Time</span></th>
                    <th class="data-grid-th _sortable not-sort  col-update_time"><span>Update Time</span></th>
                    <th class="data-grid-th _sortable not-sort  col-status"><span>Status</span></th>
                    <th class="data-grid-th _sortable not-sort  col-message"><span>Message</span></th>
                    <th class="data-grid-th _sortable not-sort  col-export_count"><span>Export Count</span></th>
                    <th class="data-grid-th col-actions last no-link col-action"><span>Action</span></th>
                </tr>
                <tr class="data-grid-filters" data-role="filter-form">
                    <td data-column="massaction" class="col-select col-massaction">

                        <div class="mass-select-wrap">
                            <select id="cpbp_adminhtml_queue_grid_massaction-mass-select" class="action-select-multiselect"
                                    data-menu="grid-mass-select">
                                <!--<optgroup label="Mass Actions">-->
                                <!--<option disabled="" selected=""></option>-->
                                <option value="">

                                </option>
                                <option value="selectAll">
                                    Select All
                                </option>
                                <option value="unselectAll">
                                    Unselect All
                                </option>
                                <option value="selectVisible">
                                    Select Visible
                                </option>
                                <option value="unselectVisible">
                                    Unselect Visible
                                </option>
                                <!--</optgroup>-->
                            </select>
                        </div>
                    </td>
                    <td class=" col-real_order_id">
                        <input type="text" name="real_order_id" id="cpbp_adminhtml_queue_grid_filter_real_order_id" value=""
                               class="input-text admin__control-text no-changes">
                    </td>
                    <td class="col-created_time">
                        <div class="range">
                            <div class="range-line date">
                                <!--<label for="from">From</label>-->
                                <input type="text" id="cpbp_created_from" name="created_from" placeholder="From" autocomplete="off">
                                <!--<label for="to">to</label>-->
                                <input type="text" id="cpbp_created_to" name="created_to" placeholder="To" autocomplete="off">

                                <!--                                    <input type="text" name="" id="cpbp_" value="" class="input-text admin__control-text no-changes _has-datepicker" placeholder="From" autocomplete="on">
                                                                    <button type="button" class="ui-datepicker-trigger v-middle">
                                                                        <span>Date selector</span>
                                                                    </button>-->
                            </div>
                        </div>
                    </td>
                    <td class=" col-update_time">
                        <div class="range">
                            <div class="range-line date">
                                <!--<label for="from">From</label>-->
                                <input type="text" id="cpbp_updated_from" name="from" placeholder="From" autocomplete="off">
                                <!--<label for="to">to</label>-->
                                <input type="text" id="cpbp_updated_to" name="to" placeholder="To" autocomplete="off">
                            </div>
                        </div>
                    </td>
                    <td class=" col-status">
                        <select name="status" id="cpbp_adminhtml_queue_grid_filter_status"
                                class="no-changes admin__control-select">
                            <option value=""></option>
                            <option value="New">New</option>
                            <option value="Sent">Sent</option>
                            <option value="Error on Send">Error on Send</option>
                        </select>
                    </td>
                    <td class="col-message">
                        <input type="text" name="message" id="cpbp_adminhtml_queue_grid_filter_message" value=""
                               class="input-text admin__control-text no-changes">
                    </td>
                    <td class=" col-export_count">
                        <input type="text" name="export_count" id="cpbp_adminhtml_queue_grid_filter_export_count" value=""
                               class="input-text admin__control-text no-changes">
                    </td>
                    <td data-column="action" class="col-actions last no-link col-action">
                        &nbsp;
                    </td>
                </tr>
                </thead>
                <tbody>
                <?php if ($countOfOrders > 0) { ?>
                    <?php foreach ($result as $order) { ?>
                        <?php
                        $orderq = wc_get_order($order->external_cash_number);

                        if(true){
                            ?>
                            <tr data-role="row" title="" class="even _clickable">
                                <td class="col-select col-massaction data-grid-checkbox-cell">
                                    <label class="data-grid-checkbox-cell-inner" for="cpbp_id_<?php echo $order->external_cash_number; ?>">
                                        <input type="checkbox" name="queue_ids" id="cpbp_id_<?php echo $order->external_cash_number; ?>" data-role="select-row" value="<?php echo $order->external_cash_number; ?>"
                                               class="admin__control-checkbox">
                                        <label for="id_<?php echo $order->external_cash_number; ?>"></label>
                                    </label>
                                </td>
                                <td class="col-real_order_id">
                                    <?php echo $order->external_cash_number; ?>
                                </td>
                                <td class="col-created_time">
                                    <?php echo $order->created_time; ?>
                                </td>
                                <td class="col-update_time">
                                    <?php echo $order->update_time; ?>
                                </td>
                                <td class="col-status">
                                    <?php
                                    switch ($order->status) {
                                        case '1':
                                            _e("Sent",CPBP_NAME);
                                            break;
                                        case '2':
                                            _e("Error on Send",CPBP_NAME);
                                            break;
                                        default:
                                            _e("New",CPBP_NAME);
                                            break;
                                    }
                                    ?>
                                </td>
                                <td class="col-message">
                                    <?php echo $order->message; ?>
                                </td>
                                <td class="col-export_count">
                                    <?php echo $order->export_count; ?>
                                </td>
                                <td class=" col-actions col-action  last">
                                    <?php //if($order->status != '1'): ?>
                                    <a class="export_order" data-order-id="<?php echo $order->external_cash_number; ?>" href="#"><?php _e('Export Cache',CPBP_NAME);?></a>
                                    <?php //endif; ?>
                                </td>
                            </tr>
                        <?php } //end if?>
                    <?php } //end foreach ?>
                <?php } //end if ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="cplink_response_wrapper">
    <div class="cplink_settings_response cplink_response">
        <span class="result_icon" data-error="dashicons-warning" data-loading="dashicons-ellipsis"
              data-success="dashicons-yes"><i class="dashicons "></i></span>
        <div class="cplink_response_txt"></div>
        <span class="cplink_close_response"> <i class="dashicons dashicons-no-alt"></i> </span>
    </div>
</div>