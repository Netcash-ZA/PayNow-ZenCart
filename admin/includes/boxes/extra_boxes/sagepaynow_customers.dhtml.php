<?php
if (!defined('IS_ADMIN_FLAG'))
    die('Illegal Access');

if( MODULE_PAYMENT_SAGEPAYNOW_STATUS == 'True' )
{
    $za_contents[] = array(
        'text' => 'Sage Pay Now Orders',
        'link' => zen_href_link( 'sagepaynow.php', '', 'NONSSL' )
        );
}
?>