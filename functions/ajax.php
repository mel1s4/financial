<?php
add_action("wp_ajax_viroz_financial_ajax_api", "viroz_financial_ajax_api_ajax");
add_action("wp_ajax_nopriv_viroz_financial_ajax_api", "viroz_financial_ajax_api_ajax");

function viroz_financial_ajax_api_ajax() {
  if (!is_user_logged_in()) {
    die();
  }
  $operation = $_POST['operation'];
  $function = 'viroz_financial_' . $operation;
  $response = $function();
  echo json_encode($response);
  die();
}

function viroz_financial_add_transaction() {
  $quantity = $_POST['quantity'];
  $wallet = $_POST['wallet'];
  $repeats = $_POST['repeats'];
  $recurranceQuantity = $_POST['recurranceQuantity'];
  $recurranceUnit = $_POST['recurranceUnit'];
  $recurranceStart = $_POST['recurranceStart'];
  $recurranceEnd = $_POST['recurranceEnd'];
  $backdate = $_POST['backdate'];
  $notes = $_POST['notes'];
  $date = $_POST['date'];
  $user_id = get_current_user_id();

  $response = viroz_financial_add_transaction_function($quantity, $wallet, $notes, $repeats, $recurranceQuantity, $recurranceUnit, $recurranceStart, $recurranceEnd, $backdate, $date, $user_id);

  if (is_int($response)) {
    $response = array(
      'status' => 'success',
      'transaction_id' => $response,
      'wallet' => $wallet,
    );
  } else {
    $response = array(
      'status' => 'error',
      'message' => $response,
    );
  }
  return $response;
}
