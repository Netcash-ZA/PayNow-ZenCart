<?php
/**
 * paynow.php
 *
 * Lanugage defines for Netcash Pay Now payment module
 *
 */

define( 'MODULE_PAYMENT_NETCASH_PAYNOW_TEXT_ADMIN_TITLE', 'Netcash Pay Now' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_TEXT_CATALOG_TITLE', 'Netcash Pay Now' );

if( IS_ADMIN_FLAG === true )
    define( 'MODULE_PAYMENT_NETCASH_PAYNOW_TEXT_DESCRIPTION',
        '<strong>Netcash Pay Now</strong><br />'.
        '<a href="https://www.netcash.co.za/" target="_blank">'.
        'Manage your Netcash Pay Now account.</a><br /><br />'.
        '<font color="green">Configuration Instructions:</font><br />'.
        '<ol style="padding-left: 20px;">'.
        '<li><a href="http://www.netcash.co.za/" target="_blank">Register for a Netcash Pay Now account.</a></li>'.
        '<li>Click "install" above to enable Netcash Pay Now support and "edit" to tell Zen Cart your Netcash Pay Now settings</li>'.
        '</ol>'.
        '<font color="green"><hr /><strong>Requirements:</strong></font><br /><hr />'.
        '*<strong>*<strong>Port 80</strong> is used for bidirectional communication with the gateway, so must be open on your host\'s router/firewall<br />'.
        '*<strong>PHP allow_url_fopen</strong> must be enabled<br />'.
        '*<strong>Settings</strong> must be configured as described above.' );
else
    define( 'MODULE_PAYMENT_NETCASH_PAYNOW_TEXT_DESCRIPTION', '<strong>Netcash Pay Now</strong>');

define( 'MODULE_PAYMENT_NETCASH_PAYNOW_BUTTON_IMG', DIR_WS_IMAGES .'paynow/logo_small.png' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_BUTTON_ALT', 'Checkout with Netcash Pay Now' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ACCEPTANCE_MARK_TEXT', '' );

define( 'MODULE_PAYMENT_NETCASH_PAYNOW_TEXT_CATALOG_LOGO',
    '<a href="http://www.netcash.co.za" style="border: 0;" target="_blank">'.
    '<img src="'. MODULE_PAYMENT_NETCASH_PAYNOW_BUTTON_IMG .'"'.
    ' alt="'. MODULE_PAYMENT_NETCASH_PAYNOW_BUTTON_ALT .'"'.
    ' title="' . MODULE_PAYMENT_NETCASH_PAYNOW_BUTTON_ALT .'"'.
    ' style="vertical-align: text-bottom; border: 0px;" border="0"/></a>&nbsp;'.
    '<span class="smallText">' . MODULE_PAYMENT_NETCASH_PAYNOW_ACCEPTANCE_MARK_TEXT . '</span>' );

define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_FIRST_NAME', 'First Name:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_LAST_NAME', 'Last Name:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_BUSINESS_NAME', 'Business Name:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_ADDRESS_NAME', 'Address Name:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_ADDRESS_STREET', 'Address Street:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_ADDRESS_CITY', 'Address City:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_ADDRESS_STATE', 'Address State:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_ADDRESS_ZIP', 'Address Zip:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_ADDRESS_COUNTRY', 'Address Country:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_EMAIL_ADDRESS', 'Payer Email:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_EBAY_ID', 'Ebay ID:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_PAYER_ID', 'Payer ID:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_PAYER_STATUS', 'Payer Status:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_ADDRESS_STATUS', 'Address Status:' );

define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_PAYMENT_TYPE', 'Payment Type:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_PAYMENT_STATUS', 'Payment Status:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_PENDING_REASON', 'Pending Reason:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_INVOICE', 'Invoice:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_PAYMENT_DATE', 'Payment Date:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_CURRENCY', 'Currency:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_GROSS_AMOUNT', 'Gross Amount:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_PAYMENT_FEE', 'Payment Fee:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_CART_ITEMS', 'Cart items:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_TXN_TYPE', 'Trans. Type:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_TXN_ID', 'Trans. ID:' );
define( 'MODULE_PAYMENT_NETCASH_PAYNOW_ENTRY_PARENT_TXN_ID', 'Parent Trans. ID:' );

define( 'MODULE_PAYMENT_NETCASH_PAYNOW_PURCHASE_DESCRIPTION_TITLE', STORE_NAME .' purchase, Order #' );
?>