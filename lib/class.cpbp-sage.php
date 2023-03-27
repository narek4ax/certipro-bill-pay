<?php
if( class_exists('CP_Sage') && !class_exists('CPBP_Sage') ) {

    class CPBP_Sage extends CP_Sage
    {
        /*private $headers = array();
        private $errorNo = 0;
        private $errorMessage = '';
        private $api_url = "";
        private $table_prefix = "";
        private $current_date = "";
        private $current_method_table = "";
        private $is_cron = false;
        private $import_source = 'sync';
        private $import_type = '';
        private $q_progress = array();
        private $last_import_data = array();
        private $users_collector = array();
        private $purge_keys = array();
        private $req_info = array(
            'success' => '',
            'counts' => array(
                'total' => '',
                'request' => ''
            )
        );
        protected $store_when_source = array(
            'purge',
            'global'
        );*/

        function __construct(){
            global $scopeConfig;
            $scopeConfig = get_option('cplink-settings');
            parent::__construct($scopeConfig); //i_print($this->current_date);
            $this->table_prefix = CPBP_DB_PREFIX;
        }


        /*function getStoreWhenSource()
        {
            return $this->store_when_source;
        }*/


        function getCashReceipts($page = 0, $limit = 20, $modified_from = '-1', $local = false)
        {
            global $wpdb;
            $import_source = $this->import_source;
            $table_prefix = $this->table_prefix;
            $table_name = $this->current_method_table =  $table_prefix . 'cash_receipts';
            $endpoint = 'cashreceipts';
            $t_keys = array('deposit_number', 'ar_division_number', 'customer_number', 'credit_card_entry_number', 'check_number');

            if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
                $this->purge_keys = array();
                foreach ($t_keys as $t_key){
                    $this->purge_keys[$t_key] = 'i_null';
                }
                $fake_data = $this->purge_keys;
                $this->store_temp_data($table_name, $fake_data, $t_keys);
            }

            if ($local) {
                $result = array();
                $result_data = $this->get_local_data($table_name, 0, '-1');
                if (count($result_data)) {
                    $result = $result_data;
                }
                return $result;
            }

            $option_key = str_replace('/', '_', $endpoint);

            $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
            if( is_object($modified_from) )
                return $modified_from;

            $result = array();
            $data = array();

            //$data = array('ar_division_number' => $ar_division_number, 'customer_number' => $customer_number, 'invoice_number' => $invoice_number, 'header_sequence_number' => $header_sequence_number);
            $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);
            //i_print($response_data); //exit;
            $result = array();

            if ($this->errorNo) {
                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
            } else {
                $result = array();
                if ($response_data->success) {
                    $result_data = $response_data->data;

                    if (count($result_data))
                        foreach ($result_data as $result_item) {
                            if ( in_array($import_source, $this->store_when_source ) ) {
                                //$delete = $this->db_empty_table($table_name);
                                $result[] = $result_item;
                                $t_data = array(
                                    'deposit_number' => $result_item->deposit_number,
                                    'ar_division_number' => $result_item->ar_division_number,
                                    'customer_number' => $result_item->customer_number,
                                    'credit_card_entry_number' => $result_item->credit_card_entry_number,
                                    'check_number' => $result_item->check_number,
                                );
                                $this->store_temp_data($table_name, $t_data, $t_keys);
                            }
                            if ($import_source != 'purge') {
                                $t_data = array(
                                    'deposit_number' => $result_item->deposit_number,
                                    'deposit_date' => $result_item->deposit_date,
                                    'deposit_description' => $result_item->deposit_description,
                                    'deposit_type' => $result_item->deposit_type,
                                    'ar_division_number' => $result_item->ar_division_number,
                                    'customer_number' => $result_item->customer_number,
                                    'credit_card_entry_number' => $result_item->credit_card_entry_number,
                                    'check_number' => $result_item->check_number,
                                    'bank_code' => $result_item->bank_code,
                                    'posting_amount' => $result_item->posting_amount,
                                    'customer_balance' => $result_item->customer_balance,
                                    'user_id' => $result_item->user_id,
                                    'external_cash_number' => $result_item->external_cash_number,
                                );

                                $this->storeCashReceiptLines(
                                    $result_item->deposit_number,
                                    $result_item->ar_division_number,
                                    $result_item->customer_number,
                                    $result_item->credit_card_entry_number,
                                    $result_item->check_number,
                                    $result_item->items
                                );
                                $result[] = $t_data;

                                $t_result = $this->insert_db($table_name, $t_data, $t_keys); //array('ar_division_no', 'customer_no', 'invoice_no')
                                //i_print($result_item);
                                /*if ($t_result) {
                                    $this->removeCashReceiptQueue($result_item->external_cash_number);
                                }*/
                            }

                        }
                    //i_print($result);

                    $last_import_data = array(
                        'date' => $this->current_date,
                        'status' => '1',
                        'run_result' => '1',
                        'in_progress' => '0'
                    );
                    update_option('clink_' . $option_key . '_last_import', $last_import_data);
                } else {

                    $last_import_data = array(
                        'date' => $this->current_date,
                        'status' => '2',
                        'run_result' => '2',
                        'in_progress' => '0'
                    );
                    update_option('clink_' . $endpoint . '_last_import', $last_import_data);

                    $result = array(
                        'status' => 0,
                        'message' => $response_data->message
                    );
                }
            }
            $this->last_import_data = $last_import_data;
            //i_print($this->last_import_data);
            return $result;
        }

        function getCashReceiptsHistory($page = 0, $limit = 20, $modified_from = '-1', $local = false)
        {
            global $wpdb;
            $import_source = $this->import_source;
            $table_prefix = $this->table_prefix;
            $table_name = $this->current_method_table =  $table_prefix . 'cash_receipts_history';
            $endpoint = 'cashreceipts/history';
            $option_key = 'cashreceipts_history';

            $t_keys = array(
                'bank_code', 'deposit_date', 'deposit_number', 'deposit_type',
                'ar_division_number', 'customer_number', 'credit_card_entry_number',
                'check_number', 'sequence_number'
            );

            if ( in_array($import_source, $this->store_when_source ) ) { //get purge keys and setup temp table
                $this->purge_keys = array();
                foreach ($t_keys as $t_key){
                    $this->purge_keys[$t_key] = 'i_null';
                }
                $fake_data = $this->purge_keys;
                $this->store_temp_data($table_name, $fake_data, $t_keys);
            }

            if ($local) {
                $result = array();
                $result_data = $this->get_local_data($table_name, 0, '-1');
                if (count($result_data)) {
                    $result = $result_data;
                }
                return $result;
            }

            $modified_from = $this->ModifiedFromInProgress($modified_from, $endpoint);
            if( is_object($modified_from) )
                return $modified_from;

            $result = array();
            $data = array();

            //$data = array('ar_division_number' => $ar_division_number, 'customer_number' => $customer_number, 'invoice_number' => $invoice_number, 'header_sequence_number' => $header_sequence_number);
            $response_data = $this->sendRequest($endpoint, $data, -1, -1, "POST", $page, $limit, $modified_from);
            //i_print($response_data); //exit;
            $result = array();

            if ($this->errorNo) {
                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                update_option('clink_' . $endpoint . '_last_import', $last_import_data);
            } else {
                $result = array();
                if ($response_data->success) {
                    $result_data = $response_data->data;

                    if (count($result_data))
                        foreach ($result_data as $result_item) {
                            if ( in_array($import_source, $this->store_when_source ) ) {
                                //$delete = $this->db_empty_table($table_name);
                                $result[] = $result_item;
                                $t_data = array(
                                    'bank_code' => $result_item->bank_code,
                                    'deposit_date' => $result_item->deposit_date,
                                    'deposit_number' => $result_item->deposit_number,
                                    'deposit_type' => $result_item->deposit_type,
                                    'ar_division_number' => $result_item->ar_division_number,
                                    'customer_number' => $result_item->customer_number,
                                    'credit_card_entry_number' => $result_item->credit_card_entry_number,
                                    'check_number' => $result_item->check_number,
                                    'sequence_number' => $result_item->sequence_number,
                                );
                                $this->store_temp_data($table_name, $t_data, $t_keys);
                            }
                            if ($import_source != 'purge') {
                                $t_data = array(
                                    'bank_code' => $result_item->bank_code,
                                    'deposit_date' => $result_item->deposit_date,
                                    'deposit_number' => $result_item->deposit_number,
                                    'deposit_type' => $result_item->deposit_type,
                                    'ar_division_number' => $result_item->ar_division_number,
                                    'customer_number' => $result_item->customer_number,
                                    'credit_card_entry_number' => $result_item->credit_card_entry_number,
                                    'check_number' => $result_item->check_number,
                                    'sequence_number' => $result_item->sequence_number,
                                    'posting_date' => $result_item->posting_date,
                                    'transaction_type' => $result_item->transaction_type,
                                    'invoice_number' => $result_item->invoice_number,
                                    'invoice_type' => $result_item->invoice_type,
                                    'deposit_description' => $result_item->deposit_description,
                                    'cash_amount' => $result_item->cash_amount,
                                    'discount_amount' => $result_item->discount_amount,
                                    'user_id' => $result_item->user_id,
                                    'external_cash_number' => $result_item->external_cash_number,
                                );
                                //i_print($t_data);

                                $result[] = $t_data;

                                $t_result = $this->insert_db($table_name, $t_data, $t_keys); //array('ar_division_no', 'customer_no', 'invoice_no')

                                if ($t_result) {
                                    $this->removeCashReceiptQueue($result_item->external_cash_number);
                                }
                            }

                        }
                    //i_print($result);

                    $last_import_data = array(
                        'date' => $this->current_date,
                        'status' => '1',
                        'run_result' => '1',
                        'in_progress' => '0'
                    );
                    update_option('clink_' . $option_key . '_last_import', $last_import_data);
                } else {

                    $last_import_data = array(
                        'date' => $this->current_date,
                        'status' => '2',
                        'run_result' => '2',
                        'in_progress' => '0'
                    );
                    update_option('clink_' . $option_key . '_last_import', $last_import_data);

                    $result = array(
                        'status' => 0,
                        'message' => $response_data->message
                    );
                }
            }

            $this->last_import_data = $last_import_data;
            return $result;
        }

        function storeCashReceiptLines($deposit_number, $ar_division_number, $customer_number, $credit_card_entry_number, $check_number, $lines, $modified_from = '-1', $local = false)
        {

            global $wpdb;
            $table_prefix = $this->table_prefix;
            $table_name = $table_prefix . 'cash_receipt_lines';
            $option_key = 'cash_receipt-lines';

            $result = array();
            if ($local) {
                $result_data = $this->get_local_data($table_name, 0, '-1');
                if (count($result_data)) {
                    $result = $result_data;
                }
                return $result;
            }

            if ($lines) {
                foreach ($lines as $line_data) {
                    if (true) {

                        $t_data = array(
                            'deposit_number' => $deposit_number,
                            'ar_division_number' => $ar_division_number,
                            'customer_number' => $customer_number,
                            'credit_card_entry_number' => $credit_card_entry_number,
                            'check_number' => $check_number,
                            'line_key' => $line_data->line_key,
                            'invoice_number' => $line_data->invoice_number,
                            'invoice_type' => $line_data->invoice_type,
                            'comment' => $line_data->comment,
                            'discount_amount' => $line_data->discount_amount,
                            'amount_posted' => $line_data->amount_posted,
                            'invoice_balance' => $line_data->invoice_balance,
                        );

                        $result[] = $t_data;

                        $t_result = $this->insert_db($table_name, $t_data,
                            array(
                                'deposit_number',
                                'ar_division_number',
                                'customer_number',
                                'credit_card_entry_number',
                                'check_number',
                                'line_key'
                            )
                        );
                    }
                }

                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '1',
                    'run_result' => '1',
                    'in_progress' => '0'
                );
                update_option('clink_' . $option_key . '_last_import', $last_import_data);
                //i_print($result);
            }

            $this->last_import_data = $last_import_data;
            return $result;
        }

        function createCashreceipts($data = array())
        {
            /*$result = array();*/

            /*if (!empty($data['customer_number']) && !empty($data['ar_division_number']) && !empty($data['order_date'])
                    && !empty($data['user_id']) && !empty($data['external_order_number']) && !empty($data['items'])) {*/

            //i_print($data);
            $result = $this->sendRequest('cashreceipts/create', $data, -1, -1, "POST");

            if ($this->errorNo) {
                $last_import_data = array(
                    'date' => $this->current_date,
                    'status' => '2',
                    'run_result' => '2',
                    'in_progress' => '0'
                );
                //update_option('clink_' . $endpoint . '_last_import', $last_import_data);
            } else {
                /*$result = array();
                if ($createSalesorders->success) {
                    $result = $createSalesorders->data;
                }*/
            }

            /*} else {
                $result = (object)array('data' => []);
                $result->data = (object)["success" => false, "message" => "customer_number, ar_division_number, order_date, user_id, external_order_number and items are required!"];
            }*/

            return $result;
        }


        function removeCashReceiptQueue($external_cash_number)
        {
            global $wpdb;
            $table_prefix = $this->table_prefix;
            $table_name = $table_prefix . 'queue';
            $sql = "UPDATE $table_name SET `active` = 0 WHERE `external_cash_number` = '$external_cash_number'"; //i_print($sql);
            return $wpdb->query($sql);
        }

    }

}