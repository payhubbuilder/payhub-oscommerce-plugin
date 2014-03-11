=== PayHub Checkout Add-on ===
Contributors: payhub
Website: http://payhub.com
Tags: payment, gateway, credit card
Stable Version for this Add-on: 2.1 
License: GNU

== Description ==
This is a payment add-on for osCommerce Online Merchant version 2.3.x  Installing this add-on will allow you to accept credit cards using PayHub Checkout.  An active PayHub account is required to use this add-on.  Please contact us to setup an account if you don't already have one.  Our contact information is at the bottom of this readme. 

== Release Notes ==

= v2.1 =
* First official release!
* Allows customers to pay by credit card directly through the osCommerce payment page using PayHub Checkout. 

== Installation and Configuration ==
Assuming you have osCommerce installed and configured...

Automatic Install:
* Log into your osCommerce installation using your admin credentials.
* Click on the Modules bar.
* Click on the Payment link.
* Scroll down and click on the Install Module button.
* Scroll down and you should see PayHub Checkout in the list. If for some reason you do not see PayHub Checkout as an option then ensure that the module is not already installed and if that is not the problem then see the "Manual Adding the PayHub Checkout add-on to osCommerce" steps below. 
* Click on the Info link on the same row as, and to the right of PayHub Checkout.
* Scroll down and click on the Install Module button.
* Scroll down and click on the Edit button.
* Scroll down and proceed to configure the add-on as follows:
  * "Do you want to accept Credit Cards through PayHub?" - set this to "True"
  * Enter your Organization ID, API Username, API Password, and Terminal ID.  See the section "How to find your API credentials" below.
  * Set the "Transaction Mode" to "demo", so that you can test the module in a non-live environment.  IMPORTANT: WHEN YOU ARE READY TO GO LIVE, YOU MUST SET THIS TO "live", OR TRANSACTIONS PROCESSED WILL NOT BE PAID TO YOU!
  * Leave the "Payment Zone" at the default, unless you have some specific need that requires you to change it.
  * Leave the "Set Order Status" option at the default, unless you have some specific need that requires you to change it.
  * Leave the "Set Order of Display" option at the default, unless you have some specific need that requires you to change it.
  * Leave the "cURL PRogram Location" option at the default, unless you have some specific need that requires you to change it.
* Once you are done configuring our add-on, click on the Save button.

Manual Adding the PayHub Checkout add-on to osCommerce:
Only use this method if you do not already see the PayHub Checkout option in the payment modules list.  This method requires that you have read and write access to the file system of your webserver.
* Go to http://developer.payhub.com and download the osCommerce add-on.
* Unzip the add-on and `cd` into the resulting directory.
* Copy the "payhub_checkout.php" files from the "catalog/includes/languages/english/modules/payment/" and "catalog/includes/modules/payment/" directories to the corresponding directories in the osCommerce installation.
* Refresh the payment modules page in osCommerce admin.
 
You should now see the "PayHub Checkout" option when completing a purchase in osCommerce.

== How to find your API credentials ==
1. Log into PayHub's Virtual Terminal
2. Click on Admin
3. Under General heading, click on 3rd Party API.
4. Copy down your Username, Password, and Terminal Id.  Please note the username and password is case sensitive.

== Notes on Testing ==
You should run the module in demo mode and try both successful and failed transactions before making it live. You can find test data to use here: http://developer.payhub.com/api#api-howtotest.

***ONCE YOU ARE DONE TESTING, MAKE SURE TO CHANGE THE "TRANSACTION MODE" TO "live" IN THE MODULE CONFIGURATION AREA.  IF YOU DO NOT DO THIS THEN TRANSACTIONS PROCESSED THROUGH THIS ADD-ON WILL NOT BE PAID TO YOU!***

== Notes on Security ==
This plugin requires validation of the host SSL certificate for PayHub servers.  This is important as it greatly reduces the chance of a successful "man in the middle" attack.

If you go through the installation and everything works fine, then you don't have to worry about the rest of this section.  If you are experiencing a problem where you receive a blank error when trying to process cards and the transaction never actually processes then read on...

Since our plugin uses cURL (http://curl.haxx.se/) to send transaction requests, you need to make sure that cURL knows where to find the CA certificate with which to validate our API SSL certs.  This is generally not a problem with hosted setups, but if you have built out your own server then you may find that this is a problem because newer versions of cURL don't include a CA bundle by default.  In this case, if you are using PHP 5.3.7 or greater you can:

*download http://curl.haxx.se/ca/cacert.pem and save it somewhere on your server.
*update php.ini -- add curl.cainfo = "PATH_TO/cacert.pem"

This solutions was shamelessly borrowed from the Stack Overflow post: http://stackoverflow.com/questions/6400300/php-curl-https-causing-exception-ssl-certificate-problem-verify-that-the-ca-cer.  Gotta love Stack Overflow ;^).

Alternatively, you can dig into the PayHub plugin itself and add the following key/value pair to the $c_opts array: CURLOPT_CAINFO => "payth/to/ca-bundle.pem".  See http://us2.php.net/manual/en/book.curl.php for more info.

== Getting Support from PayHub ==
Please contact us at wecare@payhub.com or 415-306-9476 for support.
