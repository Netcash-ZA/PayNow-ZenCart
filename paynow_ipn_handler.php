<?php
/**
 * paynow_ipn_handler
 *
 * Callback handler for Netcash Pay Now IPN
 *
 */

// bof: Load ZenCart configuration
$show_all_errors = false;

$current_page_base = 'paynowipn';
$loaderPrefix = 'paynow_ipn';

require_once ('includes/configure.php');

require_once ('includes/application_top.php');
require_once (DIR_WS_CLASSES . 'payment.php');
// define('IS_ADMIN_FLAG', false);
// require_once (dirname(__FILE__).'/includes/classes/' . 'payment.php');


$zcSessName = '';
$zcSessID = '';

zen_session_start();
$session_started = true;

// eof: Load ZenCart configuration

$show_all_errors = true;
$logdir = defined ( 'DIR_FS_LOGS' ) ? DIR_FS_LOGS : 'includes/modules/payment/paynow';
$debug_logfile_path = $logdir . '/ipn_debug_php_errors-' . time () . '.log';
@ini_set ( 'log_errors', 1 );
@ini_set ( 'log_errors_max_len', 0 );
@ini_set ( 'display_errors', 0 ); // do not output errors to screen/browser/client (only to log file)
@ini_set ( 'error_log', DIR_FS_CATALOG . $debug_logfile_path );
//error_reporting ( version_compare ( PHP_VERSION, 5.3, '>=' ) ? E_ALL & ~ E_DEPRECATED & ~ E_NOTICE : version_compare ( PHP_VERSION, 5.4, '>=' ) ? E_ALL & ~ E_DEPRECATED & ~ E_NOTICE & ~ E_STRICT : E_ALL & ~ E_NOTICE );
error_reporting ( E_ALL );

// Variable Initialization
$pnError = false;
$pnErrMsg = '';
$pnData = array ();
$pnHost = MODULE_PAYMENT_NETCASH_PAYNOW_SERVER;
$pnOrderId = '';
$pnParamString = '';
$pnDebugEmail = defined ( 'MODULE_PAYMENT_NETCASH_PAYNOW_DEBUG_EMAIL_ADDRESS' ) ? MODULE_PAYMENT_NETCASH_PAYNOW_DEBUG_EMAIL_ADDRESS : STORE_OWNER_EMAIL_ADDRESS;

pnlog ( 'Netcash Pay Now IPN call received' );

// Notify Netcash Pay Now that information has been received
if (! $pnError) {
	header ( 'HTTP/1.0 200 OK' );
	flush ();
}

// Get data sent by Netcash Pay Now
if (! $pnError) {
	pnlog ( 'Get posted data' );

	// Posted variables from IPN
	$pnData = pnGetData ();

	pnlog ( 'Netcash Pay Now Data: ' . print_r ( $pnData, true ) );

	if ($pnData === false) {
		$pnError = true;
		$pnErrMsg = PN_ERR_BAD_ACCESS;
	}
}

// Create ZenCart order
if (! $pnError) {
	// Variable initialization
	$ts = time ();
	$pnOrderId = null;
	$zcOrderId = null;
	$txnType = null;

	// Determine the transaction type
	list ( $pnOrderId, $zcOrderId, $txnType ) = pn_lookupTransaction ( $pnData );

	pnlog ( "Transaction details:" . "\n- pnOrderId = " . (empty ( $pnOrderId ) ? 'null' : $pnOrderId) . "\n- zcOrderId = " . (empty ( $zcOrderId ) ? 'null' : $zcOrderId) . "\n- txnType   = " . (empty ( $txnType ) ? 'null' : $txnType) );

	// // bof: Get Saved Session
	pnlog ( 'Retrieving saved session' );

	// Get the Zen session name and ID from Netcash Pay Now data
	list ( $zcSessName, $zcSessID ) = explode ( '=', $pnData ['Extra1'] );

	pnlog ( 'Session Name = ' . $zcSessName . ', Session ID = ' . $zcSessID );

	$table = TABLE_NETCASH_PAYNOW_SESSION; $fieldKey = 'session_id'; $fieldValue = 'saved_session';
	// $table = DB_PREFIX . 'sessions'; $fieldKey = 'sesskey'; $fieldValue = 'value';
	$sql = "SELECT * FROM `{$table}` WHERE `{$fieldKey}` = '{$zcSessID}'";
	$storedSession = $db->Execute ( $sql );

	if ($storedSession->recordCount () < 1) {
		$pnError = true;
		$pnErrMsg = PN_ERR_NO_SESSION;
	} else {
		$_SESSION = unserialize ( base64_decode ( $storedSession->fields [$fieldValue] ) );
	}
	// eof: Get Saved Session

	$postedSessionId = isset($_POST['Extra2']) ? $_POST['Extra2'] : '';
	if (!$pnError) {
		switch ($txnType) {
			/**
			 * New Transaction
			 *
			 * This is for when Zen Cart sees a transaction for the first time.
			 * This doesn't necessarily mean that the transaction is in a
			 * COMPLETE state, but rather than it is new to the system
			 */
			case 'new' :
				// bof: Get ZenCart order details
				pnlog ( 'Recreating Zen Cart order environment' );
				if (defined ( 'DIR_WS_CLASSES' )) {
					pnlog ( 'Additional debug information: DIR_WS_CLASSES is ' . DIR_WS_CLASSES );
				} else {
					pnlog ( ' ***ALERT*** DIR_WS_CLASSES IS NOT DEFINED, currently=' . DIR_WS_CLASSES );
				}
				if (isset ( $_SESSION )) {
					// TODO Removed Session printout because it's too much information
					// pnlog ( 'SESSION IS : ' . print_r ( $_SESSION, true ) );
				} else {
					pnlog ( ' ***ALERT*** $_SESSION IS NOT DEFINED' );
				}

				// Load ZenCart shipping class
				require_once (DIR_WS_CLASSES . 'shipping.php');
				// Load ZenCart payment class
				require_once (DIR_WS_CLASSES . 'payment.php');
				$payment_modules = new payment ( $_SESSION ['payment'] );
				$shipping_modules = new shipping ( $_SESSION ['shipping'] );
				// Load ZenCart order class
				require (DIR_WS_CLASSES . 'order.php');
				$order = new order ();
				// Load ZenCart order_total class
				require (DIR_WS_CLASSES . 'order_total.php');
				$order_total_modules = new order_total ();
				$order_totals = $order_total_modules->process ();
				// eof: Get ZenCart order details
				// bof: Check data against ZenCart order
				pnlog ( 'Checking data against Zen Cart order:' );
				// pnlog ( print_r($order->info, true) );

				global $currencies;
				$pn_order_total = 0;
				$products = $order->products;

				$pn_order_total_sess = $_SESSION['paynow_amount'];
				$pn_order_total = $order->info['total'];

				// if($order->info['currency'] !== 'ZAR') {
				// 	pnlog ( 'Convertion order total from ' . $order->info['currency'] . ' to ZAR. Was ' . $pn_order_total );
				// 	$pn_order_total = toZAR($pn_order_total, $order->info['currency_value']);
				// 	pnlog ( 'Convertion complete. Now: ' . $pn_order_total );
				// }


				// Check order amount
				pnlog ( 'Checking if amounts are the same' );
				// if( !pnAmountsEqual( $pnData['amount_gross'], $order->info['total'] ) )
				if ( !pnAmountsEqual ( $pnData ['Amount'], $pn_order_total_sess )) {
					pnlog ( 'Amount mismatch: PN amount = ' . $pnData ['Amount'] . ', ZC amount = ' . $pn_order_total );
					$pnError = true;
					$pnErrMsg = PN_ERR_AMOUNT_MISMATCH;
					break;
				}
				// eof: Check data against ZenCart order

				// Check if Transaction was Accepted
				if ($pnData['TransactionAccepted'] == 'false') {
					pnlog("Transaction failed, exiting break statement");
					$pnError = true;
					$pnErrMsg = "Transaction Failed Reason: " . $pnData['Reason'];
					break;
				}
				// End Check if Transaction was Accepted

				// Create ZenCart order
				pnlog ( 'Creating Zen Cart order' );
				$zcOrderId = $order->create ( $order_totals );

				// Create Netcash Pay Now order
				pnlog ( 'Creating Netcash Pay Now order' );
				$sqlArray = pn_createOrderArray ( $pnData, $zcOrderId, $ts );
				zen_db_perform ( TABLE_NETCASH_PAYNOW, $sqlArray );

				// Create Netcash Pay Now history record
				pnlog ( 'Creating Netcash Pay Now payment status history record' );
				$pnOrderId = $db->Insert_ID ();

				$sqlArray = pn_createOrderHistoryArray ( $pnData, $pnOrderId, $ts );
				zen_db_perform ( TABLE_NETCASH_PAYNOW_PAYMENT_STATUS_HISTORY, $sqlArray );

				// Update order status (if required)
				$newStatus = MODULE_PAYMENT_NETCASH_PAYNOW_ORDER_STATUS_ID;

				if (isset($pnData ['payment_status']) && $pnData ['payment_status'] == 'PENDING') {
					pnlog ( 'Setting Zen Cart order status to PENDING' );
					$newStatus = MODULE_PAYMENT_NETCASH_PAYNOW_PROCESSING_STATUS_ID;

					$sql = "UPDATE " . TABLE_ORDERS . "
	                    SET `orders_status` = " . MODULE_PAYMENT_NETCASH_PAYNOW_PROCESSING_STATUS_ID . "
	                    WHERE `orders_id` = '" . $zcOrderId . "'";
					$db->Execute ( $sql );
				}

				// Update order status history
				pnlog ( 'Inserting Zen Cart order status history record' );

				$sqlArray = array (
						'orders_id' => $zcOrderId,
						'orders_status_id' => $newStatus,
						'date_added' => date ( PN_FORMAT_DATETIME_DB, $ts ),
						'customer_notified' => '0',
						'comments' => 'Netcash Pay Now status: ' . $pnData ['Reason']
				);
				zen_db_perform ( TABLE_ORDERS_STATUS_HISTORY, $sqlArray );

				// Add products to order
				pnlog ( 'Adding products to order' );
				$order->create_add_products ( $zcOrderId, 2 );

				// Email customer
				pnlog ( 'Emailing customer' );
				$order->send_order_email ( $zcOrderId, 2 );

				// Empty cart
				pnlog ( 'Emptying cart' );
				$_SESSION ['cart']->reset ( true );

				// Deleting stored session information
				$sql = "DELETE FROM `" . TABLE_NETCASH_PAYNOW_SESSION . "`
	                WHERE `session_id` = '" . $zcSessID . "'";
				$db->Execute ( $sql );

				// Sending email to admin
				if (defined('PN_DEBUG') && PN_DEBUG) {
					$subject = "Netcash Pay Now IPN on your site";
					$body = "Hi,\n\n" . "A Netcash Pay Now transaction has been completed on your website\n" . "-------------------------------------------------------------\n" . "Site: " . STORE_NAME . " (" . HTTP_SERVER . DIR_WS_CATALOG . ")\n" . "Order ID: " . $zcOrderId . "\n".
	                    //"User ID: ". $db->f( 'user_id' ) ."\n".
	                    // TODO Implement correct form fields
	                    "Netcash Pay Now Transaction ID: " . $pnData ['RequestTrace'] . "\n" . "Netcash Pay Now Payment Status: " . $pnData ['TransactionAccepted'] . "\n" . "Order Status Code: " . $newStatus;
					zen_mail ( STORE_OWNER, $pnDebugEmail, $subject, $body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, null, 'debug' );
				}
				pnlog("End of case 'new'");
				//$redirectUrl = zen_href_link( FILENAME_CHECKOUT_PROCESS, 'referer=paynow', 'SSL' );
				$redirectUrl = zen_href_link( FILENAME_CHECKOUT_SUCCESS, 'referer=paynow', 'SSL' );
				pnlog("Redirecting to $redirectUrl");
				// TODO Redirect
				zen_redirect($redirectUrl);
				break;

			/**
			 * Pending transaction must be cleared
			 *
			 * This is for when there is an existing order in the system which
			 * is in a PENDING state which has now been updated to COMPLETE.
			 */
			case 'cleared' :

				$sqlArray = pn_createOrderHistoryArray ( $pnData, $pnOrderId, $ts );
				zen_db_perform ( TABLE_NETCASH_PAYNOW_PAYMENT_STATUS_HISTORY, $sqlArray );

				$newStatus = MODULE_PAYMENT_NETCASH_PAYNOW_ORDER_STATUS_ID;
				break;

			/**
			 * Pending transaction must be updated
			 *
			 * This is when there is an existing order in the system in a PENDING
			 * state which is being updated and is STILL in a pending state.
			 *
			 * NOTE: Currently, this should never happen
			 */
			case 'update' :

				$sqlArray = pn_createOrderHistoryArray ( $pnData, $pnOrderId, $ts );
				zen_db_perform ( TABLE_NETCASH_PAYNOW_PAYMENT_STATUS_HISTORY, $sqlArray );

				break;

			/**
			 * Pending transaction has failed
			 *
			 * NOTE: Currently, this should never happen
			 */
			case 'failed' :
				// TODO fix pn_payment_id
				$comments = 'Payment failed (Netcash Pay Now id = ' . $pnData ['RequestTrace'] . ')';
				$sqlArray = pn_createOrderHistoryArray ( $pnData, $pnOrderId, $ts );
				zen_db_perform ( TABLE_NETCASH_PAYNOW_PAYMENT_STATUS_HISTORY, $sqlArray );

				$newStatus = MODULE_PAYMENT_NETCASH_PAYNOW_PREPARE_ORDER_STATUS_ID;

				// Sending email to admin
				$subject = "Netcash Pay Now IPN Transaction on your site";
				$body = "Hi,\n\n" . "A failed Netcash Pay Now transaction on your website requires attention\n" . "--------------------------------------------------------------------\n" . "Site: " . STORE_NAME . " (" . HTTP_SERVER . DIR_WS_CATALOG . ")\n" . "Order ID: " . $zcOrderId . "\n".
	                //"User ID: ". $db->f( 'user_id' ) ."\n".
	                // TODO Fix pn_payment_id
	                "Netcash Pay Now Transaction ID: " . $pnData ['RequestTrace'] . "\n" . "Netcash Pay Now Payment Status: " . $pnData ['TransactionAccepted'] . "\n" . "Order Status Code: " . $newStatus;
				zen_mail ( STORE_OWNER, $pnDebugEmail, $subject, $body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, null, 'debug' );

				break;

			/**
			 * Unknown t
			 *
			 * NOTE: Currently, this should never happen
			 */
			case 'default' :
				pnlog ( "Can not process for txn type '" . $txn_type . ":\n" . print_r ( $pnData, true ) );
				break;
		}
	}

	global $messageStack;
	if(!$messageStack) {
		$messageStack = new messageStack();
	}

	$message = "Payment has failed. Reason: " . $pnData['Reason'];
	if ($pnData['TransactionAccepted'] == 'false' && $pnErrMsg) {
		// Show custom error message
		$message = "Payment has failed. Reason: " . $pnErrMsg;
	}
	if($messageStack) {
		$messageStack->add_session('checkout_payment', $message, 'error');
	} else {
		// admin/includes/classes/message_stack.php:50
		$_SESSION['messageToStack'][] = array('text' => $message, 'type' => 'error');
	}
	zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));

}

// Update Zen Cart order and history status tables
if (! $pnError) {
	if ($txnType != 'new' && ! empty ( $newStatus ))
		pn_updateOrderStatusAndHistory ( $pnData, $zcOrderId, $newStatus, $txnType, $ts );
}

// If an error occurred
if ($pnError) {
	pnlog ( 'Error occurred: ' . $pnErrMsg );
	pnlog ( 'Sending email notification' );

	$subject = "Netcash Pay Now IPN error: " . $pnErrMsg;
	$body = "Hi,\n\n" . "An invalid Netcash Pay Now transaction on your website requires attention\n" . "----------------------------------------------------------------------\n" . "Site: " . STORE_NAME . " (" . HTTP_SERVER . DIR_WS_CATALOG . ")\n" . "Remote IP Address: " . $_SERVER ['REMOTE_ADDR'] . "\n" . "Remote host name: " . gethostbyaddr ( $_SERVER ['REMOTE_ADDR'] ) . "\n" . "Order ID: " . $zcOrderId . "\n";
	// "User ID: ". $db->f("user_id") ."\n";
	// TODO Implement trace
	if (isset ( $pnData ['pn_payment_id'] ))
		$body .= "Netcash Pay Now Transaction ID: " . $pnData ['RequestTrace'] . "\n";
	if (isset ( $pnData ['payment_status'] ))
		$body .= "Netcash Pay Now Payment Status: " . $pnData ['TransactionAccepted'] . "\n";
	$body .= "\nError: " . $pnErrMsg . "\n";

	switch ($pnErrMsg) {
		case PN_ERR_AMOUNT_MISMATCH :
			$body .= "Value received : " . $pnData ['Amount'] . "\n" . "Value should be: " . $order->info ['total'];
			break;

		// For all other errors there is no need to add additional information
		default :
			break;
	}

	zen_mail ( STORE_OWNER, $pnDebugEmail, $subject, $body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, null, 'debug' );
}

// Close log
pnlog ( '', true );
