<?php
if(!function_exists('add_action')){
	header('HTTP/1.1 403 Forbidden');
	exit();
}
/**
* @package Buy It Now, WordPress
*/
/*
Plugin Name: WP-BuyItNow by VPS-NET (Simple Payment Gateway)
Plugin URI: http://vps-net.com/internet-development-tools/wordpress-plugins/buy-it-now-button.php
Description: Append PayPal buttons unto posts, articles, menus and virtually anywhere desired, using nothing but short codes. An integrated sales platform complete with payment lifecycle initialization and monitoring capabilities, along with digital or shipped product delivery functionality. BuyItNow also has cancellation, refund and order tracking information along with the uniquely cool 'digital media locker' functionality, which further enhances an online sales platform's capabilities. There are many other options, variables and features which make quick deployment a definite possibility, working with very minimal integration times along with the least possible amount of technical requirements on the server, host and the end-user level.
Version: 2.0.1
Author: Luis Gustavo Rodriguez (drlouie)
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
$StoreName = "Jon's Virtual Classroom";
$StoreOwnerName = "Jon L.D.";
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

//--> FOR TESTING [PayPal Sandbox]
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

//--> SHIPPING TABLES: For PayPal [currently set to 'No Shipping Necessary', in essence, 'Digital Delivery'
//--> To customize the delivery method(s)/option(s)/price(s) start by changing/adding/deleting the name/value pairs according to the payment processors' available variables
	
	//-->> PayPal HTML Variables: https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/#individual-items-variables
	$PayPal_Shipping_Table = array(
		'no_shipping' => '1'
	);
	
//--> 
//--> WORTHY TIP: You can alter the entire payment process for PayPal by appending, removing, changing the key/value pairs being sent to each processor
//--> WHAT THIS MEANS: You can utilize the Shipping Table arrays above to alter the way PayPal processes your transactions in general or individually for each item you are selling.
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
}
else {
	$PaymentMerchantPP = $GLOBALS["PaymentMerchant_PayPal"];
	$PaymentURLPP = 'www.paypal.com';
	$CharSetAccept = 'accept-charset="utf-8"';
}

$fullPaymentURLPP = 'https://'.$PaymentURLPP.'/cgi-bin/webscr';

//--> UNIX timestamp
$datetimeUNIX = time();

//--> Load Admin Dashboard Sales Monitor for Processed
require_once dirname( __FILE__ ) . '/wp-buyitnow/wp-buyitnow-monitor.php';

//--> Load BuyItNow Processor
require_once dirname( __FILE__ ) . '/wp-buyitnow/wp-buyitnow-processor.php';

?>
