<?php
/**
 * sagepaynow_functions.php
 *
 * Functions used by payment module class for Sage Pay Now IPN payment method
 *
 */

// Posting URLs
// TODO Remove LIVE/TEST references as they are not used
define( 'MODULE_PAYMENT_SAGEPAYNOW_SERVER_LIVE', 'www.sagepay.co.za' );
define( 'MODULE_PAYMENT_SAGEPAYNOW_SERVER_TEST', 'www.sagepay.co.za' );

// Database tables
define( 'TABLE_SAGEPAYNOW', DB_PREFIX . 'sagepaynow' );
define( 'TABLE_SAGEPAYNOW_SESSION', DB_PREFIX . 'sagepaynow_session' );
define( 'TABLE_SAGEPAYNOW_PAYMENT_STATUS', DB_PREFIX . 'sagepaynow_payment_status' );
define( 'TABLE_SAGEPAYNOW_PAYMENT_STATUS_HISTORY', DB_PREFIX . 'sagepaynow_payment_status_history' );
define( 'TABLE_SAGEPAYNOW_TESTING', DB_PREFIX . 'sagepaynow_testing' );

// Formatting
define( 'PN_FORMAT_DATETIME', 'Y-m-d H:i:s' );
define( 'PN_FORMAT_DATETIME_DB', 'Y-m-d H:i:s' );
define( 'PN_FORMAT_DATE', 'Y-m-d' );
define( 'PN_FORMAT_TIME', 'H:i' );
define( 'PN_FORMAT_TIMESTAMP', 'YmdHis' );

// General
define( 'PN_SESSION_LIFE', 7 );         // # of days session is saved for
define( 'PN_SESSION_EXPIRE_PROB', 5 );  // Probability (%) of deleting expired sessions

/**
 * pn_createUUID
 *
 * This function creates a pseudo-random UUID according to RFC 4122
 *
 * @see http://www.php.net/manual/en/function.uniqid.php#69164
 */
function pn_createUUID()
{
    $uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );

    return( $uuid );
}

/**
 * pn_getActiveTable
 *
 * This function gets the currently active table. If in testing mode, it
 * returns the test table, if in live, it returns the live table
 *
 * @param $msg String Message to log
 */
function pn_getActiveTable()
{
    if( strcasecmp( MODULE_PAYMENT_SAGEPAYNOW_SERVER, 'Live' ) == 0 )
        $table = TABLE_SAGEPAYNOW;
    else
        $table = TABLE_SAGEPAYNOW_TESTING;

    return( $table );
}

/**
 * pn_createOrderArray
 *
 * Creates the array used to create a Sage Pay Now order
 *
 * @param $pnData Array Array of posted Sage Pay Now data
 * @param $zcOrderId Integer Order ID for Zen Cart order
 * @param $timestamp Integer Unix timestamp to use for transaction
 */
function pn_createOrderArray( $pnData = null, $zcOrderId = null, $timestamp = null )
{
    // Variable initialization
    $ts = empty( $timestamp ) ? time() : $timestamp;
	// TODO Some variables here out of scope
    $sqlArray = array(
        'm_payment_id' => $pnData['m_payment_id'],
        'pn_payment_id' => $pnData['pn_payment_id'],
        'zc_order_id' => $zcOrderId,
        'amount_gross' => $pnData['amount_gross'],
        'amount_fee' => $pnData['amount_fee'],
        'amount_net' => $pnData['amount_net'],
        'sagepaynow_data' => serialize( $pnData ),
        'timestamp' => date( PN_FORMAT_DATETIME_DB, $ts ),
        'status' => $pnData['payment_status'],
        'status_date' => date( PN_FORMAT_DATETIME_DB, $ts ),
        'status_reason' => '',
        );

    return( $sqlArray );
}

/**
 * pn_lookupTransaction
 *
 * Determines the type of transaction which is occuring
 *
 * @param $pnData Array Array of posted Sage Pay Now data
 */
function pn_lookupTransaction( $pnData = null )
{
    // Variable initialization
    global $db;
    $data = array();

    $data = array(
        'pn_order_id' => '',
        'zc_order_id' => '',
        'txn_type' => '',
        );

    // Check if there is an existing order
    $sql =
        "SELECT `id` AS `pn_order_id`, `zc_order_id`, `status`
        FROM `". pn_getActiveTable() ."`
        WHERE `m_payment_id` = '". $pnData['m_payment_id'] ."'
        LIMIT 1";
    $orderData = $db->Execute( $sql );

    $exists = ( $orderData->RecordCount() > 0 );

    pnlog( "Record count = ". $orderData->RecordCount() );

    // If record found, extract the useful information
    if( $exists )
        $data = array_merge( $data, $orderData->fields );

    pnlog( "Data:\n". print_r( $data, true ) );

    // New transaction (COMPLETE or PENDING)
    if( !$exists )
        $data['txn_type'] = 'new';
    // Current transaction is PENDING and has now cleared
    elseif( $exists && $pnData['payment_status'] == 'COMPLETE' )
        $data['txn_type'] = 'cleared';
    // Current transaction is PENDING and is still PENDING
    elseif( $exists && $pnData['payment_status'] == 'PENDING' )
        $data['txn_type'] = 'update';
    // Current trasnaction is PENDING and has now failed
    elseif( $exists && $pnData['payment_status'] == 'FAILED' )
        $data['txn_type'] = 'failed';
    else
        $data['txn_type'] = 'unknown';

    pnlog( "Data to be returned:\n". print_r( array_values( $data ), true ) );

    return( array_values( $data ) );
}

/**
 * pn_createOrderHistoryArray
 *
 * Creats the array required for an order history update
 *
 * @param $pnData Array Array of posted Sage Pay Now data
 * @param $pnOrderId Integer Order ID for Sage Pay Now order
 * @param $timestamp Integer Unix timestamp to use for transaction
 */
function pn_createOrderHistoryArray( $pnData = null, $pnOrderId = null, $timestamp = null )
{
    $sqlArray = array (
        'pn_order_id' => $pnOrderId,
        'timestamp' => date( PN_FORMAT_DATETIME_DB, $timestamp ),
        'status' => $pnData['payment_status'],
        'status_reason' => '',
        );

    return( $sqlArray );
}
// }}}
// {{{ pn_updateOrderStatusAndHistory()
/**
 * pn_updateOrderStatusAndHistory
 *
 * Update the Zen Cart order status and history with new information supplied
 * from Sage Pay Now.
 *
 * @param $pnData Array Array of posted Sage Pay Now data
 * @param $zcOrderId Integer Order ID for ZenCart order
 */
function pn_updateOrderStatusAndHistory( $pnData, $zcOrderId, $newStatus = 1, $txnType, $ts )
{
    // Variable initialization
    global $db;

    // Update ZenCart order table with new status
    $sql =
        "UPDATE `". TABLE_ORDERS ."`
        SET `orders_status` = '". (int) $newStatus ."'
        WHERE `orders_id` = '". (int) $zcOrderId ."'";
    $db->Execute( $sql );

    // Update Sage Pay Nowt order with new status
    $sqlArray = array(
        'status' => $pnData['payment_status'],
        'status_date' => date( PN_FORMAT_DATETIME_DB, $ts ),
        );
    zen_db_perform(
        pn_getActiveTable(), $sqlArray, 'update', "zc_order_id='". $zcOrderId ."'" );

    // Create new Sage Pay Now order status history record
    $sqlArray = array(
        'orders_id' => (int)$zcOrderId,
        'orders_status_id' => (int)$newStatus,
        'date_added' => date( PN_FORMAT_DATETIME_DB, $ts ),
        'customer_notified' => '0',
        'comments' => 'Sage Pay Now status: '. $pnData['payment_status'],
       );
    zen_db_perform( TABLE_ORDERS_STATUS_HISTORY, $sqlArray );

    //// Activate any downloads for an order which has now cleared
    if( $txnType == 'cleared' )
    {
        $sql =
            "SELECT `date_purchased`
            FROM `". TABLE_ORDERS ."`
            WHERE `orders_id` = ". (int)$zcOrderId;
        $checkStatus = $db->Execute( $sql );

        $zcMaxDays = date_diff( $checkStatus->fields['date_purchased'],
            date( PN_FORMAT_DATETIME ) ) + (int)DOWNLOAD_MAX_DAYS;

        pnlog( 'Updating order #'. (int)$zcOrderId . ' downloads. New max days: '.
            (int)$zcMaxDays .', New count: '. (int)DOWNLOAD_MAX_COUNT );

        $sql =
            "UPDATE `". TABLE_ORDERS_PRODUCTS_DOWNLOAD ."`
            SET `download_maxdays` = ". (int)$zcMaxDays .",
                `download_count` = ". (int)DOWNLOAD_MAX_COUNT ."
            WHERE `orders_id` = ". (int)$zcOrderId;
        $db->Execute( $sql );
    }
}
// }}}
// {{{ pn_removeExpiredSessions()
/**
 * pn_removeExpiredSessions
 *
 * Removes sessions from the Sage Pay Now session table which are passed their
 * expiry date. Sessions will be left like this due to shopping cart
 * abandonment (ie. someone get's all the way to the order confirmation
 * page but fails to click "Confirm Order"). This will also happen when orders
 * are cancelled.
 *
 * Won't be run every time it is called, but according to a probability
 * setting to ensure a non-excessive use of resources
 *
 * @param $pnData Array Array of posted Sage Pay Now data
 * @param $zcOrderId Integer Order ID for ZenCart order
 */
function pn_removeExpiredSessions()
{
    // Variable initialization
    global $db;
    $prob = mt_rand( 1, 100 );

    pnlog( 'Generated probability = '. $prob
        .' (Expires for <= '. PN_SESSION_EXPIRE_PROB .')' );

    if( $prob <= PN_SESSION_EXPIRE_PROB )
    {
        // Removed sessions passed their expiry date
        $sql =
            "DELETE FROM `". TABLE_SAGEPAYNOW_SESSION ."`
            WHERE `expiry` < '". date( PN_FORMAT_DATETIME_DB ) ."'";
        $db->Execute( $sql );
    }
}
// }}}
?>