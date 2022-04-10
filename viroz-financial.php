<?php

/**
 * Plugin Name: Viroz Financial
 * Plugin URI: https://www.viroz.studio/
 * Description:  Adds functionalities of financial data management.
 * Version: 0.1
 * Author: Viroz Studio
 * Author URI: https://www.viroz.studio/
 */

include 'functions/ajax.php';
include 'views/public.php';

require_once ABSPATH . 'wp-admin/includes/upgrade.php';
global $wpdb;

$tablename = $table_prefix . 'viroz_financial';
$main_sql_create =
  "CREATE TABLE `$tablename` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT,
    `wallet` CHAR(4),
    `quantity` INT,
    `type` CHAR(3),
    `notes` TEXT,
    `title` TEXT,
    `meta` TEXT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
  );";
maybe_create_table( $tablename, $main_sql_create );

add_action( 'admin_enqueue_scripts', 'viroz_financial_scripts' );
function viroz_financial_scripts() {
  wp_register_style( 'viroz_financial_styles', plugin_dir_url( __FILE__ ) . 'styles/styles.css', false, '1.0.0' );
  wp_enqueue_style( 'viroz_financial_styles' );
  wp_enqueue_script('viroz_financial', plugin_dir_url( __FILE__ ).'javascript/functions.js', '1.0', 1 );
  wp_localize_script('viroz_financial', 'ajax_var', array(
    'url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('ajaxnonce')
  ));
}

add_action( 'wp_ajax_viroz_upload_assistance', 'viroz_upload_assistance' );
add_action( 'wp_ajax_nopriv_viroz_upload_assistance', 'viroz_upload_assistance' );


add_action('admin_menu', 'viroz_financial_dashboard_page');
function viroz_financial_dashboard_page(){
    add_menu_page( 'Financial Dashboard', 'Viroz Financial', 'manage_options', 'viroz-financial', 'financial_dashboard_init' );
}

function financial_dashboard_init(){
  include 'views/dashboard.php';
}

function viroz_financial_add_transaction_function($quantity, $wallet, $notes, $repeats, $recurranceQuantity, $recurranceUnit, $recurranceStart, $recurranceEnd, $backdate, $date, $user_id) {
  global $wpdb;
  $t_meta = '';
  $type = 'add';
  if($repeats == 'true') {
    $t_meta = json_encode([
      'recurrance_quantity' => $recurranceQuantity,
      'recurrance_unit' => $recurranceUnit,
      'recurrance_start' => $recurranceStart,
      'recurrance_end' => $recurranceEnd,
      'recurrance_transaction_type' => $type,
    ]);
    $type = 'rep';
  }
  $transaction = [
    'user_id' => $user_id,
    'wallet' => $wallet,
    'quantity' => $quantity,
    'type' => $type,
    'notes' => $notes,
    'meta' => $t_meta,
  ];

  if($backdate == 'true') {
    $transaction['timestamp'] = date('Y-m-d H:i:s', strtotime($date));
  }
  $wpdb->insert($wpdb->prefix . 'viroz_financial', $transaction);
  $id = $wpdb->insert_id;
  if(!$id) {
    return $wpdb->last_error;
  } else {
    return $id;
  }
}

function viroz_financial_get_transactions() {
  global $wpdb;
  $user_id = get_current_user_id();
  $tablename = $wpdb->prefix . 'viroz_financial';
  $query = "SELECT * FROM $tablename
    WHERE type != 'rep'
    AND user_id = '$user_id'
    ORDER BY timestamp DESC";
  $transactions = $wpdb->get_results($query);
  return $transactions;
}

function viroz_financial_get_next_transactions() {
  global $wpdb;
  $tablename = $wpdb->prefix . 'viroz_financial';
  $user_id = get_current_user_id();
  $query = "SELECT * FROM $tablename
    WHERE type = 'rep'
    AND user_id = '$user_id'
    ORDER BY timestamp DESC";
  $transactions = $wpdb->get_results($query);
  return $transactions;
}

function viroz_financial_get_wallets() {
  global $wpdb;
  $tablename = $wpdb->prefix . 'viroz_financial';
  $user_id = get_current_user_id();
  $query = "SELECT DISTINCT wallet FROM $tablename
    WHERE type != 'rep'
    AND user_id = '$user_id'";
  $wallets = $wpdb->get_results($query);
  return $wallets;
}

function viroz_financial_get_balance() {
  global $wpdb;
  $wallet = $_POST['wallet'];
  $tablename = $wpdb->prefix . 'viroz_financial';
  $user_id = get_current_user_id();
  $query = "SELECT wallet, SUM(quantity) as balance FROM $tablename
    WHERE wallet = '$wallet'
    AND user_id = '$user_id'
    AND type != 'rep'";
  $balance = $wpdb->get_results($query);
  return $balance;
}

function viroz_financial_delete_transaction() {
  $t_id = $_POST['transaction'];
  global $wpdb;
  $tablename = $wpdb->prefix . 'viroz_financial';
  $user_id = get_current_user_id();
  $query = "DELETE FROM $tablename
    WHERE id = '$t_id'
    AND user_id = '$user_id'";
  $wpdb->query($query);
  if(!$wpdb->last_error) {
    echo "success";
    return true;
  } else {
    return $wpdb->last_error;
  }
}

function viroz_financial_get_graph_balances() {
  global $wpdb;
  $wallets_query = "";
  if($_POST['wallets'] != '') {
    $wallets = explode(",", $_POST['wallets']);
    $wallets = implode("','", $wallets);
    $wallets_query = "AND wallet IN ('$wallets')";
  }
  $unit = $_POST['unit'];
  $end = $_POST['end'] . ' 23:59:59';
  $start = $_POST['start'] . ' 00:00:00';

  $balances = [];
  $c_date = $start;
  $C = 0;
  while($c_date <= $end) {
    $balances[$C] = [
      'date' => $c_date,
      'balance' => 0,
    ];
    $date_slots[$C] = $c_date;
    $c_date = date('Y-m-d H:i:s', strtotime($c_date . ' +1 ' . $unit));
    $C++;
  }

  $tablename = $wpdb->prefix . 'viroz_financial';
  $user_id = get_current_user_id();
  $query = "SELECT * FROM $tablename
    WHERE timestamp <= '$end'
    AND type != 'rep'
    AND user_id = '$user_id'
    $wallets_query
    ORDER BY timestamp ASC";
  $transactions = $wpdb->get_results($query);

  $tablename = $wpdb->prefix . 'viroz_financial';
  $query = "SELECT * FROM $tablename
    WHERE type = 'rep'
    AND user_id = '$user_id'
    $wallets_query
    ORDER BY timestamp ASC";
  $repeating = $wpdb->get_results($query);

  $created_transactions = [];
  foreach($repeating as $rep_transaction) {
    $date = $rep_transaction->timestamp;
    $quantity = $rep_transaction->quantity;
    $meta = json_decode($rep_transaction->meta);
    $recurrance_unit = $meta->recurrance_unit;
    $recurrance_end = $meta->recurrance_end . ' 00:00:00 UTC';
    $recurrance_start = $meta->recurrance_start . ' 00:00:00 UTC';
    $recurrance_qty = $meta->recurrance_quantity;
    $c_date = $recurrance_start;
    $today = date('Y-m-d H:i:s');

    while($c_date < $end && $c_date < $recurrance_end) {
      if($c_date > $today) {
        $created_transaction = [
          'timestamp' => $c_date,
          'quantity' => $quantity,
        ];
        $created_transactions[] = $created_transaction;
      }
      $c_date = date('Y-m-d H:i:s', strtotime($c_date . " +$recurrance_qty " . $recurrance_unit));
    }
  }

  $all_transactions = array_merge($transactions, $created_transactions);

  foreach($all_transactions as $transaction) {
    $date = $transaction->timestamp;
    $quantity = $transaction->quantity;
    if(!$date || !$quantity) {
      $date = $transaction['timestamp'];
      $quantity = $transaction['quantity'];
    }
    foreach($date_slots as $c => $date_slot) {
      if($date >= $date_slot && ($date < $date_slots[$c + 1] || $date_slots[$c + 1] == null)) {
        $balances[$c]['balance'] += $quantity;
        break;
      }
    }
  }

  $max_value = 0;
  $total = 0;
  foreach($balances as $c => $balance) {
    $total += $balance['balance'];
    if($balance['balance'] > $max_value) {
      $max_value = $balance['balance'];
    }
  }

  $result = [
    'balances' => $balances,
    'date_slots' => $date_slots,
    'max_value' => $max_value,
    'total' => $total,
  ];
  return $result;
}

?>