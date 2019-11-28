<?php
if (!defined('IS_ADMIN_FLAG'))
    die('Illegal Access');

if( MODULE_PAYMENT_NETCASH_PAYNOW_STATUS == 'True' )
{
    $za_contents[] = array(
        'text' => 'Netcash Pay Now Orders',
        'link' => zen_href_link( 'paynow.php', '', 'NONSSL' )
        );
}
?>