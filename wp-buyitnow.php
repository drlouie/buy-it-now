<?php
if(!function_exists('add_action')){
	header('HTTP/1.1 403 Forbidden');
	exit();
}
/**
* @package Buy It Now, WordPress
*/
/*
Plugin Name: WP-BuyItNow by VPS-NET (Simple Payment Gateway) [QDD]
Plugin URI: http://www.vps-net.com/cms-support/wordpress/plugins/ecommerce/payment/buy-it-now-paypal-google-checkout/plug-and-play-digital-content-delivery/
Description: Append PayPal and/or Google Checkout 'Buy It Now' buttons unto your posts, articles, menus and virtually anywhere you desire using shortcodes. QDD stands for the stages of the buy-it-now plugin, this one being the Quick Digital Delivery [QDD] level, quick deployment and minimal integration. A smart digital content payment and delivery platform for WebMasters, Blog Administrators and everyday users alike. Perfect for all-inclusive, all-access, single product or single service website business models. Custom adaptation for multi-product websites is available, at a nominal cost, just contact the developer to get started.
Version: 1.3
Author: Louie Rd
Author URI: http://LouieRD.com/
*/

/*  Copyright 2011 VPS-NET.COM (Email: wp-plugins@vps-net.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


///////////////////////////////////////////
/////////////* START VARIABLES *///////////
///////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// You can always attach most, if not all, of these variables to WordPress' native site/admin options if you'd like. For example:
// $StoreName = get_site_option( 'site_name' ) == '' ? "Jonathan's Virtual Classroom" : esc_html( get_site_option( 'site_name' ) );
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//--> GENERAL STORE INFORMATION

//--> Properties
$StoreDomain = "http://".$_SERVER["SERVER_NAME"]."";
$StoreName = "Jonathan's Virtual Classroom";
$StoreOwnerName = "Jonathan L.D.";
$StoreOwnerTitle = "Educational Speaker and Internet Columnist";
//-->> IP based server
if (filter_var($_SERVER["SERVER_NAME"], FILTER_VALIDATE_IP)) { $StoreDomainName = $_SERVER["SERVER_NAME"]; }
//-->> Domain-based server, for cross-browser compliant cookies [all subdomains included]
else { $SDN=explode(".",$_SERVER["SERVER_NAME"]);$RFst=array_shift($SDN); $StoreDomainName = '.'.implode('.',$SDN); }

//--> Automated Responses
$ThankYouForOrdering = "Hello and thank you for your purchase!<br><br>I'm glad you took the first step toward attaining better grades and enhancing your knowledge retention skills with these few easy steps. As soon as our system receives notice of a successful payment, you will be automatically given access to my audio program. As always, thank you for stopping by and for supporting my efforts in bringing you quality information that can help you change your life.";
$CancelledPayment = "Oops! Maybe you clicked the wrong link, but you've 'cancelled' your payment transaction.<br><br>Anyway, I'm glad you're showing interest in attaining better grades and enhancing your knowledge retention skills with the few easy steps contained in my audio program. Once your payment is processed and verified, we can send you an access link to my audio program. As always, thank you for stopping by and for supporting my efforts in bringing you quality information that can help you change your life.";

//--> OTHER VARIABLES

//--> Local Currency Code [default: USD]
$CurrencyCode = 'USD';

//--> GLOBAL Taxes On/Off [0/1]
$IsTaxable = 0;
	//-->> GLOBAL TaxRate(Percentage) [format: 0.00]
	//-->> Only used if Taxes is swtiched: On
	$TaxRate = '0.00';

//--> GLOBAL Discount(Percentage) [default: 0]
$discount = "0";

//--> REFUND (Time Alloted to user to ask for a refund, days) [default: 30]
//--> Just because a user isn't allowed to ask for a refund doesn't necessarily mean they can't get their payment processor to issue a refund[reversed payment] on their behalf, they just can't get one through this site, that's all.
$RefundTime = "30";

//--> [ADMIN] NEW ORDER MESSAGE On/Off [0/1] [Send message to admin upon receiving a new order]
$NewOrderMessage = 1;
//--> [ADMIN] PAYMENT SUCCESSFULY PROCESSED MESSAGE On/Off [0/1] [Send message to admin upon receiving confirmation of successful payment processing]
$PaymentVerifiedMessage = 1;

//--> FOR TESTING [PayPal Sandbox and/or Google Checkout Sandbox]
//--> [0=production, 1=testing]
$testing = 1;

//--> OBFUSCATED MARKUP  [Requires loading external dynamic javascript as part of the deobfuscation mechanism]
//--> Helps keep the mechanics and configuration behind your buy-it-now buttons away from prying eyes by generating tamper-proof code
$ObfuscatedMarkup = 0;

//--> PAYMENT PROCESSOR PARAMETERS

//--> PayPal Merchant Account/Cart Properties [Production and Sandbox: Account Email Addresses]
$PaymentMerchant_PayPal = '';
$PaymentMerchant_PayPal_Sandbox = 'merch_1311757145_biz@vps-net.com';
// DEFAULT BUTTON TYPE IS BUY-NOW [_xclick] PayPal
$ButtonTypePP = '_xclick';
//--> PayPal Button Image
$PayPalButtonImage = 'alt="Secure Checkout with PayPal" src="https://www.paypalobjects.com/en_US/i/btn/btn_xpressCheckout.gif" width="145" height="42"';
//--> PayPal Form as Button  (Customizable markup, just place %%PAYPAL-FORM%% where you want the button appear)
$PayPalForm = '
	<div id="paypal" style="width:165px;height:40px;overflow:hidden;clip:rect(0px,165px,40px,0px);">
		<div id="paypal-form">%%PAYPAL-FORM%%</div>
	</div>
';

//--> Google Checkout Merchant Account/Cart Properties [production and sandbox]
//--> [!!README!!] MAKE SURE TO UNCHECK SIGNED CART OPTION: My company will only post digitally signed carts. [!!README!!]
//--> TO FIND THIS OPTION: 
//--> Step 1: Log into checkout.google.com/sell/
//--> Step 2: Click 'Settings' tab [top] >> Then click 'Integration' [left]
//--> Step 3: Uncheck: My company will only post digitally signed carts.
//--> Step 4: Now, the following two variables, Google merchant ID (Google merchant key: not needed, not sending signed carts)
$PaymentMerchant_GoogleCheckout = '';
//--> Step 5: Find your Google Checkout Sandbox Merchant ID. You should be able to create/find it fairly easily.
$PaymentMerchant_GoogleCheckout_Sandbox = '308344762847990';
//--> Google Checkout Button Image
$GoogleCheckoutButtonImage = 'alt="Fast checkout through Google" src="http://checkout.google.com/buttons/checkout.gif?merchant_id='.$PaymentMerchant_GoogleCheckout.'&w=160&h=43&style=trans&variant=text&loc=en_US" width="160" height="43"';
//--> Google Checkout Form as Button (Customizable markup, just place %%GOOGLE-CHECKOUT-FORM%% where you want the button appear)
$GoogleCheckoutForm = '
	<div id="google-checkout" style="width:165px;height:40px;overflow:hidden;clip:rect(0px,165px,40px,0px);">
		<div id="google-checkout-form">%%GOOGLE-CHECKOUT-FORM%%</div>
	</div>
';



//--> GENERAL CALLBACK URL [callback to this very page user is currently viewing]
//--> Depending on your WordPress and Server setup, you might need to use either the query-string initilization(?) or continuance(&) character [$starter]
$starter = '&';
$myRUI = getenv("REQUEST_URI");
	
	//-->> PayPal IPN [Payment Transaction Responses - Callback API]: Route responses back through our button's URI path, which in turn will end up routing through here again ()
	if (strstr($myRUI,'act=')) { $myRUI = explode(''.$starter.'act=', $myRUI); $myRUI = $myRUI[0]; }
	if (strstr($myRUI,'merchant_return_link=')) { $myRUI = explode(''.$starter.'merchant_return_link=', $myRUI); $myRUI = $myRUI[0]; }
	
//--> GENERAL CALLBACK URL [callback to this very page user is currently viewing]
$callbackURI = $myRUI . "" . $starter;

//--> More Automated Responses
$EmailFooterMessage = "Do not reply to this message, as it was sent by ".$GLOBALS["StoreName"].": ".$GLOBALS["StoreDomain"].", an automated mailbox that's never checked.\r\n\r\nPowered by WP-BuyItNow, a WordPress Shopping Cart Plugin, by VPS-NET (http://www.vps-net.com)";

//--> SHIPPING TABLES: For both PayPal and GoogleCheckout [highly customizable] [both currently set to 'No Shipping Necessary', in essence, 'Digital Delivery'
//--> To customize the delivery method(s)/option(s)/price(s) start by changing/adding/deleting the name/value pairs according to each payment processors' variables
	
	//-->> PayPal HTML Variables: https://www.x.com/docs/DOC-1332
	$PayPal_Shipping_Table = array(
		'no_shipping' => '1'
	);
	
	//-->> Google Checkout - Shipping and Digital Delivery [HTML Variables]
	//-->> http://code.google.com/apis/checkout/developer/Google_Checkout_HTML_API.html#shipping_xsd
	function getGoogleShippingTable($myProductName) {
		$returnURLGC = "".$GLOBALS["StoreDomain"]."".$GLOBALS["callbackURI"]."wp-buyitnow-item=".trim(str_replace("'","",str_replace('"',"",stripslashes($myProductName))))."&wp-buyitnow-processor=Google";
		return array(
			//'shopping-cart.items.item-1.digital-content.display-disposition' => 'PESSIMISTIC',
			//'shopping-cart.items.item-1.digital-content.email-delivery' => 'true'
			'shopping-cart.items.item-1.digital-content.display-disposition' => 'OPTIMISTIC',
			'shopping-cart.items.item-1.digital-content.description' => 'You are done! You can now access the digital media you purchased by revisiting &amp;lt;a href='.$returnURLGC.'&amp;gt;'.trim(str_replace("'","",str_replace('"',"",stripslashes($GLOBALS["StoreName"])))).'&amp;lt;/a&amp;gt;.&lt;br&gt;&lt;br&gt;PLEASE NOTE: You will need to have the Google Checkout Transaction ID Number associated with this order, along with your email address, to log in and access the media you purchased.&lt;br&gt;&lt;br&gt;HINT: The Transaction ID is located in the URL of the webpage you are currently viewing here on Google Checkout. For instance, if the URL for this webpage ended with confirmation?t=888888888888, the Transaction ID for this order would be: 888888888888.&lt;br&gt;&lt;br&gt;Having trouble finding the Transaction ID? Just check your email, it will be included in your order confirmation message from Google Checkout.'
		);
	}
//--> 
//--> WORTHY TIP: You can alter the entire payment process for either PayPal or Google Checkout by appending, removing, changing the key/value pairs being sent to each processor
//--> WHAT THIS MEANS: You can utilize the Shipping Table arrays above to alter the way PayPal or Google Checkout processes your transactions in general or individually for each item you are selling.
//--> 



//--> OUTGOING TEMPLATE for parsing form(s)/button(s) for user view, if you'd like to change the way the button(s) display on the page, here's the perfect sport to do that:
$OutgoingMarkup = '
	<form method="post" action="#main" name="vps-net-com-buyitnow-verify-form"><input type="hidden" name="buyitnow-verify-new" value=""><input type="hidden" name="buyitnow-verify-email" value=""><input type="hidden" name="buyitnow-verify-tid" value=""></form>
	<div id="vps-net-com-buyitnow">
		<table cellpadding="0" cellspacing="0" border="0" style="border:0;margin:0;">
			<tr>
				<td style="border:0;margin:0;" align="left" colspan="2"><h3 style="font-weight:bold;margin-bottom:6px;">Price: $%%PRICE%%</h3></td>
			</tr>
			<tr>
				<td style="border:0;margin:0;" align="left" colspan="2"><h3>This digital content is being protected by WP-BuyItNow for '.$StoreName.', this website\'s storefront. In order to access this digitally protected content you have two options.</h3></td>
			</tr>
			<tr>
				<td style="border:0;margin:0;" align="center" colspan="2"><h4 style="font-weight:bold;margin-bottom:6px;">You may purchase access to this protected digital content:</h4></td>
			</tr>
			<tr>
				<td style="border:0;margin:0;" align="right">%%Google%%</td>
				<td style="border:0;margin:0;" align="left">%%PayPal%%</td>
			</tr>
			<tr>
				<td style="border:0;margin:0;" align="center" colspan="2"><h4 style="font-weight:bold;margin-bottom:6px;margin-top:12px;">OR, you may unlock your access to digital content:</h4></td>
			</tr>
			<tr>
				<td style="border:0;margin:0;" align="center" colspan="2"><div class="clearfix"><button onclick=javascript:document.forms["vps-net-com-buyitnow-verify-form"].submit();>Unlock My Access</button></div><!--<input type="submit" value="Unlock My Access">--></td>
			</tr>
			
		</table>
	</div>
';


/////////////////////////////////////////
/////////////* END VARIABLES *///////////
/////////////////////////////////////////


////////////////////////////////////////////////////////
/* SWITCH BETWEEN TEST AND PRODUCTION PAYMENT SYSTEMS */
////////////////////////////////////////////////////////
// can be triggered by request parameters
if ( isset($_REQUEST['test']) ) { $testing=$_REQUEST['test']; }
if ($testing == 1) {
	$ISTESTING = '&test=1';
	$testingField = '<input type="hidden" name="test" value="1"/>';
	$PaymentMerchantPP = $GLOBALS["PaymentMerchant_PayPal_Sandbox"];
	$PaymentURLPP = 'www.sandbox.paypal.com';
	$PaymentMerchantGC = $GLOBALS["PaymentMerchant_GoogleCheckout_Sandbox"];
	$PaymentURLGC = 'sandbox.google.com/checkout/';
}
else {
	$PaymentMerchantPP = $GLOBALS["PaymentMerchant_PayPal"];
	$PaymentURLPP = 'www.paypal.com';
	$PaymentMerchantGC = $GLOBALS["PaymentMerchant_GoogleCheckout"];
	$PaymentURLGC = 'checkout.google.com/';
	$CharSetAccept = 'accept-charset="utf-8"';
}

$fullPaymentURLGC = 'https://'.$PaymentURLGC.'api/checkout/v2/checkoutForm/Merchant/'.$PaymentMerchantGC.'';
$fullPaymentURLPP = 'https://'.$PaymentURLPP.'/cgi-bin/webscr';

//--> UNIX timestamp
$datetimeUNIX = time();

//--> Load Admin Dashboard Sales Monitor for Processed
require_once dirname( __FILE__ ) . '/wp-buyitnow/wp-buyitnow-monitor.php';

//--> Load BuyItNow Processor
require_once dirname( __FILE__ ) . '/wp-buyitnow/wp-buyitnow-processor.php';

?>
