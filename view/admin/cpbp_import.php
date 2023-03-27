<?php
$import_items = array(
    'cashreceipts' => 'Cashe Receipts',
    'cashreceipts_history' => 'Cash Receipts History',
    //'childmethods' => 'Child Methods',
);

$import_statuses = CPLINK::$import_statuses;
$import_run_results = CPLINK::$import_run_results;
?>
<div id="import-area" class="cp_import_area" data-import_area="cpbp">
    <h1 id="import-area-title">Import</h1>
    <div id="admin-area-import-grid">
        <form id="importForm" action="" method="post">
            <div id="source_action" class="bulk_import_div">
                <select name="source_action" class="bulk_source_action">
                    <option value="global">Global</option>
                    <option value="synch" selected="">Synch</option>
                    <option value="purge">Purge</option>
                </select>
                <button type="button" name="actionSubmit" class="button button-primary bulk_import_submit"><span>Submit</span></button>
            </div>
            <table id="import-area-table" class="wp-list-table widefat fixed striped table-view-list cp_table">
                <thead>
                <tr class="headings">
                    <th class="data-grid-multicheck-cell">
                        <div>
                            <input id="mass-select-checkbox" class="admin_control-checkbox" type="checkbox">
                            <label for="mass-select-checkbox"></label>
                        </div>
                    </th>

                    <th class="data-grid-th">Description</th>
                    <th class="data-grid-th">Last Run Time</th>
                    <th class="data-grid-th">Last Run Result</th>
                    <th class="data-grid-th">Status</th>
                    <th class="data-grid-th">Source</th>
                    <th class="data-grid-th">Action</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($import_items as $import_item_key => $import_item_desc) {

                    $last_import_data = get_option( 'clink_'.$import_item_key.'_last_import' );
                    $last_import_date = ''; $import_status = ''; $import_run_result = '';
                    if( $last_import_data && is_array($last_import_data) ) {
                        $last_import_date = $last_import_data['date'];
                        $import_status = $import_statuses[ $last_import_data['status'] ];
                        $import_run_result = $import_run_results[ $last_import_data['run_result'] ];
                    }

                    echo '<tr class="data-row i_import_type_wrapper" data-import_desc="'.$import_item_desc.'">';

                    echo '<td class="a-center data-grid-checkbox-cell">
                        <label class="data-grid-checkbox-cell-inner">
                            <input type="checkbox" id="i_' . $import_item_key . '" name="import_modules[]" value="' . $import_item_key . '" class="i_check_item">
                            <label for="i_' . $import_item_key . '"></label>
                        </label>
                    </td>';
                    echo '<td>' . $import_item_desc . '</td>';
                    echo '<td class="import_date">'.$last_import_date.'</td>
                    <td class="import_run_result">'.$import_run_result.'</td>
                    <td class="import_status">'.$import_status.'</td>';

                    echo '<td>
                        <select class="cp_import_source" name="cp_import_' . $import_item_key . '">
                            <option value="global">Global</option>
                            <option value="synch" selected="">Synch</option>
                            <option value="purge">Purge</option>
                        </select>
                    </td>';

                    echo '
                    <td class="last a-center">
                        <div id="action_' . $import_item_key . '">
                            <button type="submit" name="cp_import_' . $import_item_key . '" value="' . $import_item_key . '" class="cp_do_import button button-primary"> Import </button>
                        </div></td>';

                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>
        </form>
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
