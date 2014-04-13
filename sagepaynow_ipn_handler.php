<?php
/**
 * sagepaynow_ipn_handler
 *
 * Callback handler for Sage Pay Now IPN
 *
 */

//// bof: Load ZenCart configuration
$show_all_errors = false;

$current_page_base = 'sagepaynowipn';
$loaderPrefix = 'sagepaynow_ipn';

require_once( 'includes/configure.php' );
require_once( 'includes/application_top.php' );
require_once( DIR_WS_CLASSES .'payment.php' );

$zcSessName = '';
$zcSessID = '';
// eof: Load ZenCart configuration

    $show_all_errors = true;
    $logdir = defined('DIR_FS_LOGS') ? DIR_FS_LOGS : 'includes/modules/payment/sagepaynow';
    $debug_logfile_path = $logdir . '/itn_debug_php_errors-'.time().'.log';
    @ini_set('log_errors', 1);
    @ini_set('log_errors_max_len', 0);
    @ini_set('display_errors', 0); // do not output errors to screen/browser/client (only to log file)
    @ini_set('error_log', DIR_FS_CATALOG . $debug_logfile_path);
    error_reporting(version_compare(PHP_VERSION, 5.3, '>=') ? E_ALL & ~E_DEPRECATED & ~E_NOTICE : version_compare(PHP_VERSION, 5.4, '>=') ? E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_STRICT : E_ALL & ~E_NOTICE);
    
// Variable Initialization
$pnError = false;
$pnErrMsg = '';
$pnData = array();
$pnHost = ( strcasecmp( MODULE_PAYMENT_SAGEPAYNOW_SERVER, 'live' ) == 0 ) ?
    MODULE_PAYMENT_SAGEPAYNOW_SERVER_LIVE : MODULE_PAYMENT_SAGEPAYNOW_SERVER_TEST;
$pnOrderId = '';
$pnParamString = '';
$pnDebugEmail = defined( 'MODULE_PAYMENT_SAGEPAYNOW_DEBUG_EMAIL_ADDRESS')
    ? MODULE_PAYMENT_SAGEPAYNOW_DEBUG_EMAIL_ADDRESS : STORE_OWNER_EMAIL_ADDRESS;

pnlog( 'Sage Pay Now IPN call received' );

//// Notify Sage Pay Now that information has been received
if( !$pnError )
{
    header( 'HTTP/1.0 200 OK' );
    flush();
}

//// Get data sent by Sage Pay Now
if( !$pnError )
{
    pnlog( 'Get posted data' );

    // Posted variables from IPN
    $pnData = pnGetData();

    pnlog( 'Sage Pay Now Data: '. print_r( $pnData, true ) );

    if( $pnData === false )
    {
        $pnError = true;
        $pnErrMsg = PN_ERR_BAD_ACCESS;
    }
}

//// Verify security signature
if( !$pnError )
{
    pnlog( 'Verify security signature' );

    // If signature different, log for debugging
    if( !pnValidSignature( $pnData, $pnParamString ) )
    {
        $pnError = true;
        $pnErrMsg = PN_ERR_INVALID_SIGNATURE;
    }
}

//// Verify source IP (If not in debug mode)
if( !$pnError && !PN_DEBUG )
{
    pnlog( 'Verify source IP' );

    if( !pnValidIP( $_SERVER['REMOTE_ADDR'] ) )
    {
        $pnError = true;
        $pnErrMsg = PN_ERR_BAD_SOURCE_IP;
    }
}

//// Verify data received
if( !$pnError )
{
    pnlog( 'Verify data received' );

    $pnValid = pnValidData( $pnHost, $pnParamString );

    if( !$pnValid )
    {
        $pnError = true;
        $pnErrMsg = PN_ERR_BAD_ACCESS;
    }
}

//// Create ZenCart order
if( !$pnError )
{
    // Variable initialization
    $ts = time();
    $pnOrderId = null;
    $zcOrderId = null;
    $txnType = null;

    // Determine the transaction type
    list( $pnOrderId, $zcOrderId, $txnType ) = pn_lookupTransaction( $pnData );

    pnlog( "Transaction details:".
        "\n- pnOrderId = ". ( empty( $pnOrderId ) ? 'null' : $pnOrderId ) .
        "\n- zcOrderId = ". ( empty( $zcOrderId ) ? 'null' : $zcOrderId ) .
        "\n- txnType   = ". ( empty( $txnType ) ? 'null' : $txnType ) );

    switch( $txnType )
    {
        /**
         * New Transaction
         *
         * This is for when Zen Cart sees a transaction for the first time.
         * This doesn't necessarily mean that the transaction is in a
         * COMPLETE state, but rather than it is new to the system
         */
        case 'new':

            //// bof: Get Saved Session
            pnlog( 'Retrieving saved session' );

            // Get the Zen session name and ID from Sage Pay Now  data
            list( $zcSessName, $zcSessID ) = explode( '=', $pnData['custom_str1'] );

            pnlog( 'Session Name = '. $zcSessName .', Session ID = '. $zcSessID );

            $sql =
                "SELECT *
                FROM `".TABLE_SAGEPAYNOW_SESSION."`
                WHERE `session_id` = '". $zcSessID ."'";
            $storedSession = $db->Execute( $sql );

            if( $storedSession->recordCount() < 1 )
            {
                $pnError = true;
                $pnErrMsg = PN_ERR_NO_SESSION;
                break;
            }
            else
                $_SESSION = unserialize( base64_decode( $storedSession->fields['saved_session'] ) );
            //// eof: Get Saved Session

            //// bof: Get ZenCart order details
            pnlog( 'Recreating Zen Cart order environment' );
            if ( defined( DIR_WS_CLASSES) ) 
            {
                pnlog( 'Additional debug information: DIR_WS_CLASSES is ' . DIR_WS_CLASSES );
            }
            else
            {
                pnlog( ' ***ALERT*** DIR_WS_CLASSES IS NOT DEFINED' );
            }
            if ( isset( $_SESSION ) ) 
            {
                pnlog( 'SESSION IS : ' . print_r( $_SESSION, true ) );
            }
            else
            {
                pnlog( ' ***ALERT*** $_SESSION IS NOT DEFINED' );
            }

        
            // Load ZenCart shipping class
            require_once( DIR_WS_CLASSES . 'shipping.php' );
pnlog( __FILE__ . ' line ' . __LINE__ );
            // Load ZenCart payment class
            require_once( DIR_WS_CLASSES . 'payment.php' );
            $payment_modules = new payment( $_SESSION['payment'] );
pnlog( __FILE__ . ' line ' . __LINE__ );
            $shipping_modules = new shipping( $_SESSION['shipping'] );
pnlog( __FILE__ . ' line ' . __LINE__ );
            // Load ZenCart order class
            require( DIR_WS_CLASSES . 'order.php' );
            $order = new order();
pnlog( __FILE__ . ' line ' . __LINE__ );
            // Load ZenCart order_total class
            require( DIR_WS_CLASSES . 'order_total.php' );
            $order_total_modules = new order_total();
pnlog( __FILE__ . ' line ' . __LINE__ );
            $order_totals = $order_total_modules->process();
            //// eof: Get ZenCart order details
pnlog( __FILE__ . ' line ' . __LINE__ );
            //// bof: Check data against ZenCart order
            pnlog( 'Checking data against ZenCart order' );

            // Check order amount
            pnlog( 'Checking if amounts are the same' );
            // patch for multi-currency - AGB 19/07/13 - see also includes/modules/payment/sagepaynow.php
            // if( !pnAmountsEqual( $pnData['amount_gross'], $order->info['total'] ) )            
            if( !pnAmountsEqual( $pnData['amount_gross'], $_SESSION['sagepaynow_amount'] ) )   
            {
                pnlog( 'Amount mismatch: PN amount = '.
                    $pnData['amount_gross'] .', ZC amount = '. $_SESSION['sagepaynow_amount']  );

                $pnError = true;
                $pnErrMsg = PN_ERR_AMOUNT_MISMATCH;
                break;
            }
            //// eof: Check data against ZenCart order

            // Create ZenCart order
            pnlog( 'Creating Zen Cart order' );
            $zcOrderId = $order->create( $order_totals );

            // Create Sage Pay Now order
            pnlog( 'Creating Sage Pay Now order' );
            $sqlArray = pn_createOrderArray( $pnData, $zcOrderId, $ts );
            zen_db_perform( TABLE_SAGEPAYNOW, $sqlArray );

            // Create Sage Pay Now history record
            pnlog( 'Creating Sage Pay Now payment status history record' );
            $pnOrderId = $db->Insert_ID();

            $sqlArray = pn_createOrderHistoryArray( $pnData, $pnOrderId, $ts );
            zen_db_perform( TABLE_SAGEPAYNOW_PAYMENT_STATUS_HISTORY, $sqlArray );

            // Update order status (if required)
            $newStatus = MODULE_PAYMENT_SAGEPAYNOW_ORDER_STATUS_ID;

            if( $pnData['payment_status'] == 'PENDING' )
            {
                pnlog( 'Setting Zen Cart order status to PENDING' );
                $newStatus = MODULE_PAYMENT_SAGEPAYNOW_PROCESSING_STATUS_ID;

                $sql =
                    "UPDATE ". TABLE_ORDERS ."
                    SET `orders_status` = " . MODULE_PAYMENT_SAGEPAYNOW_PROCESSING_STATUS_ID . "
                    WHERE `orders_id` = '" . $zcOrderId . "'";
                $db->Execute( $sql );
            }

            // Update order status history
            pnlog( 'Inserting Zen Cart order status history record' );

            $sqlArray = array(
                'orders_id' => $zcOrderId,
                'orders_status_id' => $newStatus,
                'date_added' => date( PN_FORMAT_DATETIME_DB, $ts ),
                'customer_notified' => '0',
                'comments' => 'Sage Pay Now status: '. $pnData['payment_status'],
                );
            zen_db_perform( TABLE_ORDERS_STATUS_HISTORY, $sqlArray );

            // Add products to order
            pnlog( 'Adding products to order' );
            $order->create_add_products( $zcOrderId, 2 );

            // Email customer
            pnlog( 'Emailing customer' );
            $order->send_order_email( $zcOrderId, 2 );

            // Empty cart
            pnlog( 'Emptying cart' );
            $_SESSION['cart']->reset( true );

            // Deleting stored session information
            $sql =
                "DELETE FROM `".TABLE_SAGEPAYNOW_SESSION."`
                WHERE `session_id` = '". $zcSessID ."'";
            $db->Execute( $sql );

            // Sending email to admin
            if( PN_DEBUG )
            {
                $subject = "Sage Pay Now IPN on your site";
                $body =
                    "Hi,\n\n".
                    "A Sage Pay Now transaction has been completed on your website\n".
                    "-------------------------------------------------------------\n".
                    "Site: ". STORE_NAME ." (". HTTP_SERVER . DIR_WS_CATALOG .")\n".
                    "Order ID: ". $zcOrderId ."\n".
                    //"User ID: ". $db->f( 'user_id' ) ."\n".
                    // TODO Implement correct form fields
                    "Sage Pay Now Transaction ID: ". $pnData['pn_payment_id'] ."\n".
                    "Sage Pay Now Payment Status: ". $pnData['payment_status'] ."\n".
                    "Order Status Code: ". $newStatus;
                zen_mail( STORE_OWNER, $pnDebugEmail, $subject, $body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, null, 'debug' );
            }

            break;

        /**
         * Pending transaction must be cleared
         *
         * This is for when there is an existing order in the system which
         * is in a PENDING state which has now been updated to COMPLETE.
         */
        case 'cleared':

            $sqlArray = pn_createOrderHistoryArray( $pnData, $pnOrderId, $ts );
            zen_db_perform( TABLE_SAGEPAYNOW_PAYMENT_STATUS_HISTORY, $sqlArray );

            $newStatus = MODULE_PAYMENT_SAGEPAYNOW_ORDER_STATUS_ID;
            break;

        /**
         * Pending transaction must be updated
         *
         * This is when there is an existing order in the system in a PENDING
         * state which is being updated and is STILL in a pending state.
         *
         * NOTE: Currently, this should never happen
         */
        case 'update':

            $sqlArray = pn_createOrderHistoryArray( $pnData, $pnOrderId, $ts );
            zen_db_perform( TABLE_SAGEPAYNOW_PAYMENT_STATUS_HISTORY, $sqlArray );

            break;

        /**
         * Pending transaction has failed
         *
         * NOTE: Currently, this should never happen
         */
        case 'failed':
			// TODO fix pn_payment_id
            $comments = 'Payment failed (Sage Pay Now id = '. $pnData['pn_payment_id'] .')';
            $sqlArray = pn_createOrderHistoryArray( $pnData, $pnOrderId, $ts );
            zen_db_perform( TABLE_SAGEPAYNOW_PAYMENT_STATUS_HISTORY, $sqlArray );

            $newStatus = MODULE_PAYMENT_SAGEPAYNOW_PREPARE_ORDER_STATUS_ID;

            // Sending email to admin
            $subject = "Sage Pay Now IPN Transaction on your site";
            $body =
                "Hi,\n\n".
                "A failed Sage Pay Now transaction on your website requires attention\n".
                "--------------------------------------------------------------------\n".
                "Site: ". STORE_NAME ." (". HTTP_SERVER . DIR_WS_CATALOG .")\n".
                "Order ID: ". $zcOrderId ."\n".
                //"User ID: ". $db->f( 'user_id' ) ."\n".
                // TODO Fix pn_payment_id
                "Sage Pay Now Transaction ID: ". $pnData['pn_payment_id'] ."\n".
                "Sage Pay Now Payment Status: ". $pnData['payment_status'] ."\n".
                "Order Status Code: ". $newStatus;
            zen_mail( STORE_OWNER, $pnDebugEmail, $subject, $body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, null, 'debug' );

            break;

        /**
         * Unknown t
         *
         * NOTE: Currently, this should never happen
         */
        case 'default':
            pnlog( "Can not process for txn type '". $txn_type .":\n".
                print_r( $pnData, true ) );
            break;
    }
}

// Update Zen Cart order and history status tables
if( !$pnError )
{
    if( $txnType != 'new' && !empty( $newStatus ) )
        pn_updateOrderStatusAndHistory( $pnData, $zcOrderId, $newStatus, $txnType, $ts );
}

// If an error occurred
if( $pnError )
{
    pnlog( 'Error occurred: '. $pnErrMsg );
    pnlog( 'Sending email notification' );

    $subject = "Sage Pay Now IPN error: ". $pnErrMsg;
    $body =
        "Hi,\n\n".
        "An invalid Sage Pay Now transaction on your website requires attention\n".
        "----------------------------------------------------------------------\n".
        "Site: ". STORE_NAME ." (". HTTP_SERVER . DIR_WS_CATALOG .")\n".
        "Remote IP Address: ". $_SERVER['REMOTE_ADDR'] ."\n".
        "Remote host name: ". gethostbyaddr( $_SERVER['REMOTE_ADDR'] ) ."\n".
        "Order ID: ". $zcOrderId ."\n";
        //"User ID: ". $db->f("user_id") ."\n";
        // TODO Implement trace
    if( isset( $pnData['pn_payment_id'] ) )
        $body .= "Sage Pay Now Transaction ID: ". $pnData['pn_payment_id'] ."\n";
    if( isset( $pnData['payment_status'] ) )
        $body .= "Sage Pay Now Payment Status: ". $pnData['payment_status'] ."\n";
    $body .=
        "\nError: ". $pnErrMsg ."\n";

    switch( $pnErrMsg )
    {
        case PN_ERR_AMOUNT_MISMATCH:
            $body .=
                "Value received : ". $pnData['amount_gross'] ."\n".
                "Value should be: ". $order->info['total'];
            break;

        // For all other errors there is no need to add additional information
        default:
            break;
    }

    zen_mail( STORE_OWNER, $pnDebugEmail, $subject, $body, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, null, 'debug' );
}

// Close log
pnlog( '', true );
?>