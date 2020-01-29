Netcash Pay Now Zen Cart Credit Card Payment Module
================================================

Revision 2.0.0

Introduction
------------

Zen Cart is an online store management system based on PHP and MySQL and is freely available under the GNU General Public License.

This is Sage Pay South Africa's Pay Now credit card gateway module for Zen Cart. The module gives you to ability to use the Netcash Pay Now gateway that in turns lets you process credit card transactions. Netcash Pay Now supports VISA and MasterCard.

Installation Instructions
-------------------------

Download the files from Github and extract them into the corresponding folders of your Zen Cart installation:
* Version < v1.5.4 https://github.com/SagePay/PayNow-SimpleCart/archive/master.zip
* Version > v1.5.4 https://github.com/SagePay/PayNow-SimpleCart/archive/v1.2.0.zip

There are three folders that require have files in, namely:
* admin /zcadmin
* images
* includes

Additionally the `paynow_ipn_handler.php` needs to be installed into the root of your Zen Cart installation.

Configuration
-------------

Prerequisites:

To transact with this module, you will need:
* Netcash Pay Now login credentials
* Netcash Pay Now Service key
* Zen Cart admin login credentials

A. Netcash Pay Now Gateway Server Configuration Steps:

1. Log into your Netcash Pay Now Gateway Server configuration page:
	https://merchant.netcash.co.za/SiteLogin.aspx
2. Type in your Netcash Username, Password, and PIN
2. Click on Account Profile
3. Click NetConnector
4. Click on Pay Now
5. Click "Active:"
6. Type in your Email address
7. Click "Allow credit card payments:"

8. The Accept, Decline, Notify and Redirect URLs should all be:
	> http://zen_cart_installation/paynow_ipn_handler.php

10. It is highly recommended that you "Make test mode active:" while you are still testing your site.

B. Zen Cart Steps:

1. Log into Zen Cart as administrator (http://zen_cart_installation/zen_admin)
2. Navigate to Modules / Payment
3. Click on "NetcashPayNow"
4. Click '+ install Module' on the right hand side to install the module
5. Click 'True' to enable the module
6. Type in your Netcash service key
7. Click 'update' when you are done

You are now ready to process credit card transaction with Netcash Pay Now.

Remember to turn of "Make test mode active:" in the Netcash backend when you are ready to go live.

Issues & Feature Requests
-------------------------

We welcome your feedback.

Please contact Netcash South Africa with any questions or issues.
