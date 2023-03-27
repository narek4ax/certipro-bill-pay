<?php
global $wpdb;
global $table_prefix;

$charset_collate = $wpdb->get_charset_collate();

//Table structure for table `_queue`
$table_name = $table_prefix . 'queue';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` int(11) unsigned NOT NULL auto_increment,
            `external_cash_number` varchar(255) NOT NULL default '',
            `status` tinyint(4) NOT NULL DEFAULT 0,
            `message` text NOT NULL default '',
            `export_count` int(11) DEFAULT NULL,
            `created_time` datetime NULL,
            `update_time` datetime NULL,
            `active` tinyint(4) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) $charset_collate;COMMIT;";

dbDelta($sql);


//Table structure for table `_cash_receipts`
$table_name = $table_prefix . 'cash_receipts';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `deposit_number` varchar(5) NOT NULL,
            `deposit_date` datetime NOT NULL,
            `deposit_description` varchar(30) DEFAULT '',
            `deposit_type` varchar(1) NOT NULL,
            `ar_division_number` varchar(2) NOT NULL,
            `customer_number` varchar(20) NOT NULL,
            `credit_card_entry_number` varchar(6) NOT NULL DEFAULT '',
            `check_number` varchar(10) NOT NULL DEFAULT '',
            `bank_code` varchar(1) DEFAULT NULL,
            `posting_amount` decimal(13,2) DEFAULT NULL,
            `customer_balance` decimal(13,2) DEFAULT NULL,
            `user_id` varchar(5) DEFAULT NULL,
            `external_cash_number` varchar(255) DEFAULT '',
            `created_date` datetime DEFAULT NULL,
            `modified_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `deposit_number` (`deposit_number`,`ar_division_number`,`customer_number`,`credit_card_entry_number`,`check_number`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_cash_receipts_history`
$table_name = $table_prefix . 'cash_receipts_history';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `bank_code` varchar(1) DEFAULT NULL,
            `deposit_date` datetime NOT NULL,
            `deposit_number` varchar(5) NOT NULL,
            `deposit_type` varchar(1) NOT NULL,
            `ar_division_number` varchar(2) NOT NULL,
            `customer_number` varchar(20) NOT NULL,
            `credit_card_entry_number` varchar(6) NOT NULL DEFAULT '',
            `check_number` varchar(10) NOT NULL DEFAULT '',
            `sequence_number` varchar(6) NOT NULL,
            `posting_date` datetime DEFAULT NULL,
            `transaction_type` varchar(2) DEFAULT NULL,
            `invoice_number` varchar(7) DEFAULT NULL,
            `invoice_type` varchar(2) DEFAULT NULL,
            `deposit_description` varchar(30) DEFAULT '',
            `cash_amount` decimal(12,2) DEFAULT NULL,
            `discount_amount` decimal(12,2) DEFAULT NULL,
            `user_id` varchar(5) DEFAULT NULL,
            `external_cash_number` varchar(255) DEFAULT '',
            `created_date` datetime DEFAULT NULL,
            `modified_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `bank_code` (`bank_code`,`deposit_date`,`deposit_number`,`deposit_type`,`ar_division_number`,`customer_number`,`credit_card_entry_number`,`check_number`,`sequence_number`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_cash_receipt_lines`
$table_name = $table_prefix . 'cash_receipt_lines';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `deposit_number` varchar(5) NOT NULL,
            `ar_division_number` varchar(2) NOT NULL,
            `customer_number` varchar(20) NOT NULL,
            `credit_card_entry_number` varchar(6) NOT NULL DEFAULT '',
            `check_number` varchar(10) NOT NULL DEFAULT '',
            `line_key` varchar(6) NOT NULL,
            `invoice_number` varchar(7) DEFAULT NULL,
            `invoice_type` varchar(2) DEFAULT NULL,
            `comment` varchar(2048) DEFAULT NULL,
            `discount_amount` decimal(12,2) DEFAULT NULL,
            `amount_posted` decimal(12,2) DEFAULT NULL,
            `invoice_balance` decimal(12,2) DEFAULT NULL,
            `created_date` datetime DEFAULT NULL,
            `modified_date` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `line_key` (`deposit_number`,`ar_division_number`,`customer_number`,`credit_card_entry_number`,`check_number`,`line_key`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_import`
$table_name = $table_prefix . 'import';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `function_name` varchar(100) NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `last_run_time` datetime DEFAULT NULL,
            `last_run_result` varchar(255) DEFAULT NULL,
            `status` varchar(150) DEFAULT NULL,
            `sort` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_sales_flat_cash`
$table_name = $table_prefix . 'sales_flat_cash';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `store_id` smallint(5) UNSIGNED DEFAULT NULL,
            `ar_division_number` varchar(2) NOT NULL,
            `customer_number` varchar(20) NOT NULL,
            `customer_id` int(10) UNSIGNED DEFAULT NULL,
            `deposit_type` varchar(1) NOT NULL,
            `check_number` varchar(10) DEFAULT NULL,
            `deposit_description` varchar(30) DEFAULT '',
            `cc_owner` varchar(255) DEFAULT NULL,
            `cc_last4` varchar(255) DEFAULT NULL,
            `cc_type` varchar(255) DEFAULT NULL,
            `cc_exp_month` varchar(255) DEFAULT NULL,
            `cc_exp_year` varchar(255) DEFAULT NULL,
            `ach_account_holder_first_name` varchar(255) DEFAULT '',
            `ach_account_holder_last_name` varchar(255) DEFAULT '',
            `ach_routing_number` varchar(9) DEFAULT '',
            `ach_account_number` varchar(20) DEFAULT '',
            `ach_account_type` varchar(255) DEFAULT '',
            `transaction_id` bigint(20) DEFAULT NULL,
            `token` varchar(38) DEFAULT NULL,
            `post_code_result` varchar(255) DEFAULT NULL,
            `reference` varchar(255) DEFAULT NULL,
            `transaction_type` varchar(20) DEFAULT NULL,
            `firstname` varchar(255) DEFAULT '',
            `lastname` varchar(255) DEFAULT '',
            `street1` varchar(255) DEFAULT '',
            `street2` varchar(255) DEFAULT '',
            `postcode` varchar(255) DEFAULT '',
            `country_code` varchar(3) DEFAULT '',
            `city` varchar(255) DEFAULT '',
            `region` varchar(2) DEFAULT '',
            `fax` varchar(255) DEFAULT '',
            `email_address` varchar(250) DEFAULT '',
            `telephone` varchar(255) DEFAULT '',
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);

//Table structure for table `_sales_flat_cash_item`
$table_name = $table_prefix . 'sales_flat_cash_item';
$sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `cash_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
            `invoice_number` varchar(7) NOT NULL,
            `invoice_type` varchar(2) NOT NULL,
            `amount_posted` decimal(12,6) NOT NULL,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) $charset_collate; COMMIT;";

dbDelta($sql);
