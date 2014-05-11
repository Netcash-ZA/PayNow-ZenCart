Sage Pay Now Zen Cart Credit Card Payment Module
================================================

Revision 1.0.1

Introduction
------------

Zen Cart is an online store management system based on PHP and MySQL and is freely available under the GNU General Public License.

This is Sage Pay South Africa's Pay Now credit card gateway module for Zen Cart. The module gives you to ability to use the Sage Pay Now gateway that in turns lets you process credit card transactions. Sage Pay Now supports VISA and MasterCard.

Installation Instructions
-------------------------

Download the files from Github and extract them into the corresponding folders of your Zen Cart installation:
* https://github.com/SagePay/PayNow-SimpleCart/archive/master.zip

There are three folders that require have files in, namely:
* admin
* images
* includes

Additionally the `sagepaynow_ipn_handler.php` needs to be installed into the root of your Zen Cart installation.

Configuration
-------------

Prerequisites:

To transact with this module, you will need:
* Sage Pay Now login credentials
* Sage Pay Now Service key
* Zen Cart admin login credentials

A. Sage Pay Now Gateway Server Configuration Steps:

1. Log into your Sage Pay Now Gateway Server configuration page:
	https://merchant.sagepay.co.za/SiteLogin.aspx
2. Type in your Sage Pay Username, Password, and PIN
2. Click on Account Profile
3. Click Sage Connect
4. Click on Pay Now
5. Click "Active:"
6. Type in your Email address
7. Click "Allow credit card payments:"

8. The Accept and Decline URLs should both be:
	> http://zen_cart_installation/sagepaynow_ipn_handler.php

10. It is highly recommended that you "Make test mode active:" while you are still testing your site.

B. Zen Cart Steps:

1. Log into Zen Cart as administrator (http://zen_cart_installation/zen_admin)
2. Navigate to Modules / Payment
3. Click on "SagePayNow"
4. Click '+ install Module' on the right hand side to install the module
5. Click 'True' to enable the module
6. Type in your Sage Pay service key
7. Click 'update' when you are done

You are now ready to process credit card transaction with Sage Pay Now.

Remember to turn of "Make test mode active:" in the Sage Pay backend when you are ready to go live.

Here is a screenshot of the Zen Cart settings screen for the Sage Pay Now configuration:
![alt tag](http://zencart.gatewaymodules.com/zen_cart_screenshot1.png)

Demo Site
---------

Here is a Zen Cart demo site that used the Sage Pay now module:
http://zencart.gatewaymodules.com

Revision History
----------------

* 11 May 2014/1.0.1
** Improved documentation with callback module.
** Fix reference in code to "Service Key"
** Added small logo
* 13 Apr 2014/1.0.0	First version

Tested with Zen Cart version 3

Feedback, issues & feature requests
-----------------------------------

We welcome your feedback.

If you have any comments or questions please contact Sage Pay South Africa or log an issue on GitHub

