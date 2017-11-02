<?php
if(!defined('ABSPATH')){header('HTTP/1.1 403 Forbidden');header('Location: http://www.kill-mill.com/');exit();}
/**
* @package Buy It Now, WordPress: Virtual Cart Processor
*/
/*
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

//-->>>> Start stagger of our output [so we can set cookies anywhere within these processes]
ob_start();

//-->>>> Start stagger of our output [so we can set cookies anywhere within these processes]
function vps_net_buy_it_now($markup) {
	$findContent = '~\<div class="buyitnow-content">(.*?)\</div>~s';
	$findProtectedContent = '~\<div class="buyitnow-protected-content">(.*?)\</div>~s';
	$findConfig = '~\[BIN(.+?)\]~s';
	if (preg_match_all($findContent,$markup,$content) || preg_match_all($findProtectedContent,$markup,$content)) {
		$isProtected = 0;
		if (preg_match_all($findContent,$markup,$content)) { 
			preg_match_all($findContent,$markup,$content); 
		}
		else {
			preg_match_all($findProtectedContent,$markup,$content); 
			$isProtected = 1;
		}
		foreach ($content[0] as $dataset) {
			if (preg_match_all($findConfig,$dataset,$vari)) {
				foreach ($vari[0] as $config) {
					$ProductName = getValues('BIN_Name',$config);
					$ProductPrice = getValues('BIN_Price',$config);
					$ProductID = getValues('BIN_ID',$config);
					$ProductID = preg_replace('/[^A-Za-z0-9-]/', '', $ProductID);
					$PaymentProcessors = getValues('BIN_Processors',$config);
					$TaxableItem = getValues('BIN_Taxable',$config);
					$TaxableRate = getValues('BIN_TaxRate',$config);
					$ItemDiscount = getValues('BIN_Discount',$config);
				}
				if (strlen($ProductID) >= 5) {
					$findProtected = '~\<div id="buyitnow-item-'.$ProductID.'">(.+?)\</div>~s';
					if (preg_match_all($findProtected,$dataset,$protected)) {
						foreach ($protected[0] as $privates) {
							$privates = str_replace('<div id="buyitnow-item-'.$ProductID.'">','',$privates);
							$privates = str_replace('</div>','',$privates);
							$protectedContent .= $privates;
						}
					}
				}
			}
		}

		//-->>>> Set cookie to keep track of product views by user, for google checkout + local shop tie-ins
			//-->>>>> With Google, since google redirects user to site, as we have directed, we have ProductID to work with always on user callbacks
			//-->>>>> So, with ProductID in callback, after payment is made, we can associate paid user's cookie for this product id with transaction ID, after subsequent verification
			//-->>>>> Two factors must be met: 
			//-->>>>> 	1. User must click Callback URL right after payment is made, or from google checkout or from email we sent [in essence giving us our ProductID]
			//-->>>>> 		** Tip: Might wanna SHOUT to user instructions to make sure they click the callback link on the 'Payment Complete' page at Google Checkout, to make sure this doesn't fail???
			//-->>>>> 	2. User's cookie for ProductID that was purchased must still be alive when Callback URl is clicked, in order for this to function, yet:
			//-->>>>> 	[Should work under almost any circumstance, unless user is trying to maniupulate the system in a way it wasn't meant to function, a rouge user per se]

			//-->>>>> 	KEEP IN MIND: 	The use of this cookie is strictly tracking multiple products being sold, and which has been paid for. 
			//-->>>>> 					Meaning, single product or all-access type websites don't need to worry about this at all, for those types of websites this works even if this cookie track fails. Which is why this plugin was marked as being perfect for full-access/all-inclusive/single-product/single-service websites.

		//-->>>
		if (!isset($_COOKIE['wp-buyitnow-pass'])) {
			$cProductID = str_replace("[^A-Za-z0-9-]","",$ProductID);
			//-- encrypt our remote addr | user agent
			$saltedPrawnHeads = encrypt("".$GLOBALS["callbackURI"]."-----".$_SERVER['REMOTE_ADDR']."----".$_SERVER['HTTP_USER_AGENT']."", $cProductID);
			//-- hex it to make sure we keep encrypted data integrity
			$saltedPrawnHeads = strToHex($saltedPrawnHeads);
			setcookie("wp-buyitnow-product", "".$cProductID."dTXTb".$saltedPrawnHeads."", time()+31536000, SITECOOKIEPATH, $GLOBALS["StoreDomainName"]);
		}

		//-->> Load Button Generator
		require_once dirname( __FILE__ ) . '/wp-buyitnow-button.php';
		$myPayPalForm = $GLOBALS["PayPalForm"];
		$myGoogleCheckoutForm = $GLOBALS["GoogleCheckoutForm"];
		$PayPalForm = '';
		$GoogleCheckoutForm = '';

		//--> If PaymentProcessor is set at the document level [if this is the case, we must be trying to remove one of the processors' buttons from showing]
		if (isset($PaymentProcessors) && (stristr($PaymentProcessors,'GoogleCheckout') || stristr($PaymentProcessors,'PayPal'))) {
			if (stristr($PaymentProcessors,'GoogleCheckout')) { $GoogleCheckoutForm = str_replace("%%GOOGLE-CHECKOUT-FORM%%", "$myBuyItNowButtonGoogleCheckout", $myGoogleCheckoutForm); }
			if (stristr($PaymentProcessors,'PayPal')) { $PayPalForm = str_replace("%%PAYPAL-FORM%%", "$myBuyItNowButtonPayPal", $myPayPalForm); }
		}
		//--> DEFAULT: Show all PaymentProcessors
		else {
			$GoogleCheckoutForm = str_replace("%%GOOGLE-CHECKOUT-FORM%%", "$myBuyItNowButtonGoogleCheckout", $myGoogleCheckoutForm);
			$PayPalForm = str_replace("%%PAYPAL-FORM%%", "$myBuyItNowButtonPayPal", $myPayPalForm);
		}

		//-->> Grab our form templates and their unified output template [either: OutgoingProtectedMarkup for protected content or OutgoingMarkup for unprotected]
		if ($isProtected) { $myform = $GLOBALS["OutgoingProtectedMarkup"]; }
		else { $myform = $GLOBALS["OutgoingMarkup"]; }
		$myform= str_replace("%%PRICE%%",$ProductPrice." ".$GLOBALS["CurrencyCode"],$myform);
		$myform= str_replace("%%PayPal%%",$PayPalForm,$myform);
		$myform= str_replace("%%Google%%",$GoogleCheckoutForm,$myform);

		//--> Obfuscate our outgoing markup <form>, if configured as such
		if ($GLOBALS["ObfuscatedMarkup"] == 1) {
			$mpn = ereg_replace("[^A-Za-z]", "", $ProductName);
			$varName = 'javascript_obfuscator_data';
			if (strlen($mpn) > 5 && strlen($mpn) < 55) { $varName = $mpn; }
			$myparsebackform = spicNspan($myform);
			$myform = "<script language=\"Javascript\" type=\"text/javascript\">var ".$varName." = \"".$myparsebackform."\";</script><script language=\"Javascript\" type=\"text/javascript\" src=\"http://vps-net.com/internet-development-tools/html-web-data-markup-obfuscation-javascript.php?generate-obfuscated-javascript=".$varName."\"></script>";
		}

	//--> OUTPUT
		//-->> Payment Cancelled Before Completion: Show button(s) after activity response
		if (isset($GLOBALS["Cancelled"])) {
			return '<p><div style="text-align:left;">'.$GLOBALS["CancelledPayment"].'<br><br>Truly,<br>'.$GLOBALS["StoreOwnerName"].'<br>'.$GLOBALS["StoreOwnerTitle"].'<br>'.$GLOBALS["StoreName"].'</div><br><br></p>' . $myform . '';
		}
		//-->> Order RECEIVED Successfully (user is coming back from processor after successful): Show activity response [no need to parse buy-it-now buttons again]
		else if (isset($GLOBALS["Successful"]) || isset($GLOBALS["VerifyAgain"])) {
			//-->> Pass GCauth, meaning user just came back from posting payment on GC, [set productID tracking cookie based on callback value, if cookie doesn't already exist (should exist if user cookies work and haven't been cleared)]
			if (isset($GLOBALS["isGCAUTH"]) && !isset($_COOKIE['wp-buyitnow-pass'])) {
				if (strlen($_GET['wp-buyitnow-item']) > 5) {
					//-->> Make sure product isn't already set
					if (!isset($_COOKIE['wp-buyitnow-product'])) {
						$cProductID = str_replace("[^A-Za-z0-9-]","",trim($_GET['wp-buyitnow-item']));
						//-- encrypt our remote addr | user agent
						$saltedPrawnHeads = encrypt("".$GLOBALS["callbackURI"]."-----".$_SERVER['REMOTE_ADDR']."----".$_SERVER['HTTP_USER_AGENT']."", $cProductID);
						//-- hex it to make sure we keep encrypted data integrity
						$saltedPrawnHeads = strToHex($saltedPrawnHeads);
						setcookie("wp-buyitnow-product", "".$cProductID."dTXTb".$saltedPrawnHeads."", time()+31536000, SITECOOKIEPATH, $GLOBALS["StoreDomainName"]);
					}
				}
			}

			//-->>> Ask for user to furnish payment transaction credentials [transactionID/email]
				//-->>>> If successful we give the thank you message, otherwise we simply show form an ask for them to enter their verification info
				if (isset($GLOBALS["Successful"])) { $verifyprompt = $GLOBALS["ThankYouForOrdering"].'<br><br>'; }
			$verifyprompt .= 'We will need to verify your purchase and identity to unlock your access. All we need is your Email Address and the Transaction ID Number associated with your purchase, you may enter that information into the fields below:'; 
			$verifyform = '<br><br><form method="post" action="#Straight A Study"><ul id="contact"><li><span class="text">Email Address</span><span class="required">(*)</span> <span class="wpcf7-form-control-wrap buyitnow-verify-email"><input type="text" name="buyitnow-verify-email" value="" class="wpcf7-text wpcf7-validates-as-required" size="40" /></span></li><li><span class="text">Transaction ID Number</span><span class="required">(*)</span> <span class="wpcf7-form-control-wrap buyitnow-verify-tid"><input type="text" name="buyitnow-verify-tid" value="" class="wpcf7-text wpcf7-validates-as-required" style="text-indent:150px;" size="40" /></span></li><li id="submit"><input type="submit" value="Unlock My Access"></li></ul></form>';
			//-->>> Bad Verification Attempt [try again]
			if(isset($GLOBALS["VerifyAgain"]) && !isset($GLOBALS["NewVerify"])) {
				$verifyprompt = 'Ooops, that caused an error!<br><br>Let\'s try that again, make sure you copy and paste the Transaction ID Number as provided by Google Checkout payment transactions or for PayPal users, this TransactionID can be found in the order verification email we sent out when you originally made your purchase. Also, make sure you use the proper email address, the address associated with your PayPal or Google Checkout account. Keep in mind, sometimes PayPal and Google Checkout may take a bit longer to process a payment, which means your access can\'t be unlocked until we have received confirmation of payment from either party.';
				if (isset($GLOBALS["VerifyError"])) { $verifyprompt = $GLOBALS["VerifyError"]; }
			}
			$verifyprompt .= $verifyform;
			return '<p><div style="text-align:left;">'.$verifyprompt.'<div style="position:relative;float:left;">Thanks again,<br>'.$GLOBALS["StoreOwnerName"].'<br>'.$GLOBALS["StoreOwnerTitle"].'<br>'.$GLOBALS["StoreName"].'</div></div><br><br></p>';
		}
		//-->> Asking for a refund/order cancellation
		else if (isset($GLOBALS["RefundRequest"])) {
			//-->>> Ask for user to furnish payment transaction credentials [transactionID/email]
			if (isset($_GET['email'])) { $mEmailAddy = $_GET['email']; }
			else if (isset($_POST['buyitnow-refund-email'])) { $mEmailAddy = $_POST['buyitnow-refund-email']; }
			if (isset($_GET['trid'])) { $mTransID = $_GET['trid']; }
			else if (isset($_POST['buyitnow-refund-tid'])) { $mTransID = $_POST['buyitnow-refund-tid']; }
			$myEmailAddy = $mEmailAddy == '' ? "" : $mEmailAddy;
			$myTransID = $mTransID == '' ? "" : $mTransID;
			$refundprompt = 'Want a refund?<br><br>Just fill in the following fields with your order information to complete your request. All we need is your Email Address and the Transaction ID Number associated with your purchase, you may enter that information into the fields below:'; 
			if (isset($GLOBALS["RefundError"])) {
				$refundprompt = $GLOBALS["RefundError"];
			}
			$refundform = '<br><br><form method="post"><ul id="contact"><li><span class="text">Email Address</span><span class="required">(*)</span> <span class="wpcf7-form-control-wrap buyitnow-refund-email"><input type="text" name="buyitnow-refund-email" value="'.$myEmailAddy.'" class="wpcf7-text wpcf7-validates-as-required" size="40" /></span></li><li><span class="text">Transaction ID Number</span><span class="required">(*)</span> <span class="wpcf7-form-control-wrap buyitnow-refund-tid"><input type="text" name="buyitnow-refund-tid" value="'.$myTransID.'" class="wpcf7-text wpcf7-validates-as-required" style="text-indent:150px;" size="40" /></span></li><li id="submit"><input type="submit" value="Request Refund"></li></ul></form>';
			$refundprompt .= $refundform;
			if (isset($GLOBALS["RefundResponse"])) {
				$refundprompt = $GLOBALS["RefundResponse"] . "<br><br>";
			}
			return '<p><div style="text-align:left;">'.$refundprompt.'<div style="position:relative;float:left;">Thanks again,<br>'.$GLOBALS["StoreOwnerName"].'<br>'.$GLOBALS["StoreOwnerTitle"].'<br>'.$GLOBALS["StoreName"].'</div></div><br><br></p>';
		}
		//-->> Payment Successfully Verified Locally (can be triggered by cookie/session/reauthorization):
		else if (isset($GLOBALS["Verified"])) {
			// remove config information
			$markup = preg_replace($findConfig,'',$markup);
			return $markup;
		}
		//-->> Default [Show Button(s)]
		else {
			//-->> Document has button configuration code in it
			if ($findConfig) {

				//-->> IF OUR COOKIE SAYS WE PASS, WE PASS
				if ($ProductID && isset($_COOKIE['wp-buyitnow-pass']) && (strlen($_COOKIE['wp-buyitnow-pass'])) > 0) {
					$myPass = $_COOKIE['wp-buyitnow-pass'];
					$myPass = decrypt(hexToStr($myPass),$ProductID);
					$myPasses = explode("-----",$myPass);
					global $wpdb;
					$lPTID = preg_replace('/[^A-Za-z0-9]/', '', $myPasses[1]);
					$vQ = "SELECT * FROM ".$wpdb->options." WHERE option_name = 'buyitnow-purchase-google-".$lPTID."' OR option_name = 'buyitnow-purchase-paypal-".$lPTID."' ORDER BY option_id DESC";
					$tTr = $wpdb->get_results($vQ);
					$fndIt = count($tTr);
					if ($fndIt == 1) {
						$lID = $tTr[0]->option_id;
						$lName = $tTr[0]->option_name;
						$lValue = $tTr[0]->option_value;
						if (stristr($lName,'PayPal')) { $iPaypal = 'PayPal'; }
						if (stristr($lName,'Google')) { $iGoogle = 'Google Checkout'; }
						//--> Payment Cancelled/Refunded/Declined
						if (stristr($lValue,'Cancelled') || stristr($lValue,'Refunded') || stristr($lValue,'Declined')) { $accessError = 1; }
						else if ($iGoogle || $iPaypal && (int)$myPasses[0] === (int)$lID) { $GoodToGo = 1; }
					}
					//-->> We are verified, let us through to content
					if ($GoodToGo) {
						$markup = preg_replace($findConfig,'',$markup);
						return $markup;
					}
					//-->> Any errors? Kill the cookie and make user re-auth himself [auto re-route to access declined screen, for re-auth]
					else {
						if(isset($_COOKIE['wp-buyitnow-pass'])) {
							setcookie("wp-buyitnow-pass", "", time()-31536000, SITECOOKIEPATH, $GLOBALS["StoreDomainName"]);
						}
					}
				}

				//-->>> replace config with our form
				$markup = preg_replace($findConfig,'<!--VPS-NET-VIRTUAL-PRIVATE-SERVERS-AND-NETWORKS-CONTENT-->',$markup);
				//-->>>> Document has protected content in it
				if ($findProtected && $isProtected == 1) {
					//-->>>>> Remove protected content
					$markup = preg_replace($findProtected,"<!-- PROTECTED CONTENT: You must pay to access it.-->\r\n\r\n<!--This digital content is being protected by Buy-It-Now, a WordPress plugin(http://wordpress.org/extend/plugins/) developed by VPS-NET(http://www.vps-net.com), which makes integrating an online ordering system into your website a quick, hassle-free and painless process! -->",$markup);
				}
				else {
					$markup = preg_replace($findProtected,"<!--Protected content by Buy-It-Now, a WordPress plugin(http://wordpress.org/extend/plugins/) developed by VPS-NET(http://www.vps-net.com), which makes integrating an online ordering system into your Wordpress site a quick, hassle-free and painless process! -->",$markup);
				}
				$elFormado = explode("<!--VPS-NET-VIRTUAL-PRIVATE-SERVERS-AND-NETWORKS-CONTENT-->", $markup);
				$markup = $elFormado[0] . '' . $myform . '' . $elFormado[1];

				//-->> We return to our parent process under various conditions, therefore we must return ASAP, waiting will overwrite written form [in essence, we will always return markup to parent]
				return $markup;
			}
			return $markup;
		}
		return $markup;
	}
	return $markup;
	//-->>>> End stagger of our output
	ob_end_flush();
}


//-- COMMON FUNCTIONS
function strToHex($string) {if(!$string){return $string;}$hex=''; for ($i=0; $i < strlen($string); $i++) { $hex .= dechex(ord($string[$i])); } return $hex; }
function encrypt($string, $key) {if(!$string || !$key){return $string;}$result = '';for($i=0; $i<strlen($string); $i++) {$char = substr($string, $i, 1);$keychar = substr($key, ($i % strlen($key))-1, 1);$char = chr(ord($char)+ord($keychar));$result.=$char;}return base64_encode($result);}
function decrypt($string, $key) {if(!$string || !$key){return $string;}$result = '';$string = base64_decode($string);for($i=0; $i<strlen($string); $i++) {$char = substr($string, $i, 1);$keychar = substr($key, ($i % strlen($key))-1, 1);$char = chr(ord($char)-ord($keychar));$result.=$char;}return $result;}
function getValues($lookfor, $lookin) { $find = '~'.$lookfor.'=(.+?);~s'; preg_match_all($find,$lookin,$results); foreach ($results[1] as $piece) { return $piece; } }
function hexToStr($hex) {if(!$hex){return $hex;}$string=''; for ($i=0; $i < strlen($hex)-1; $i+=2) { $string .= chr(hexdec($hex[$i].$hex[$i+1])); } return $string; }
function spicNspan($mysugar) {if(!$mysugar){return $mysugar;}$mysugar = str_replace("\t", "", $mysugar); $mysugar = str_replace("\n", "", $mysugar); $mysugar = str_replace("\r", "", $mysugar); $mysugar = str_replace('\"', '"', $mysugar); $mysugar = str_replace("\'", "'", $mysugar); $myCreamed = strToHex($mysugar); return $myCreamed; }
function buy_it_now_mailer($to_email, $from_email, $from_name, $subject, $message) { $admin_email = get_site_option( 'admin_email' ); if ( $admin_email == '' ) { $admin_email = 'support@' . $_SERVER['SERVER_NAME']; } if ( $from_email == '' ) { $from_email = $admin_email; } if ( $to_email == '' ) { $to_email = $admin_email; } if ( $from_name == '' ) { $from_name = $GLOBALS["StoreName"]; } $from_name = trim($from_name); $from_email = trim($from_email); $to_email = trim($to_email); $message = $message . "\r\n"; $message_headers = "From: \"{$from_name}\" <{$from_email}>\n" . "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"\n"; mail($to_email, $subject, $message, $message_headers); }

$RemoteAddr = $_SERVER['REMOTE_ADDR'];
if ($RemoteAddr != '') { $RemoteHostName = "\r\nremoteHostName: " . gethostbyaddr($RemoteAddr) . ""; }
$RemoteAgent = $_SERVER['HTTP_USER_AGENT'];
if ($RemoteAgent != '') { $RemoteAgent = "\r\nremoteAgent: " . $RemoteAgent . ""; }

if ( isset($_REQUEST['act']) && ( strstr($_REQUEST['act'],'cancelled') || ( strstr($_REQUEST['act'],'goodppal') && isset($_GET['id']) && isset($_GET['pp']) && isset($_POST['payer_email']) && isset($_POST['receiver_id']) && isset($_POST['payer_id']) && isset($_POST['verify_sign']) && isset($_POST['txn_id']) && isset($_POST['payment_type']) && isset($_POST['mc_currency']) && isset($_POST['residence_country']) ) ) ) {
	$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
}
else if (isset($_POST['buyitnow-refund-tid']) && isset($_POST['buyitnow-refund-email'])) {
	$act = 'DOrefund';
}
else if (isset($_GET['trid']) && isset($_GET['email'])) {
	$act = 'refund';
}
else if (isset($_POST['buyitnow-verify-email']) && isset($_POST['buyitnow-verify-tid'])) {
	$act = 'verify';
}
//-->> MAKE SURE ALL ITEMS WITH SERIAL-NUMBER COMING IN ARE SCREENED FOR REMOTE AGENT, SHOULD BE: Google Checkout Notification Agent
else if (isset($_POST['serial-number']) && strstr($RemoteAgent,'remoteAgent: Google Checkout Notification Agent')) {
	$act = 'goodgc';
}
else if (isset($_GET['wp-buyitnow-item']) && isset($_GET['wp-buyitnow-processor']) && !isset($_COOKIE['wp-buyitnow-pass'])) {
	$act = 'gcauth';
}
else if ( isset($_GET['auth']) && isset($_GET['merchant_return_link']) && isset($_POST['payer_email']) && isset($_POST['receiver_id']) && isset($_POST['payer_id']) && isset($_POST['verify_sign']) && isset($_POST['txn_id']) && isset($_POST['payment_type']) && isset($_POST['mc_currency']) && isset($_POST['residence_country']) ) {
	$act = 'ppauth';
}


if ( empty($act) ) {
	$act="pay";
}
switch ($act) {
	case 'pay' :
		add_filter('the_content', 'vps_net_buy_it_now');
	break;	

	case 'cancelled' :
		$Cancelled = 1;
		add_filter('the_content', 'vps_net_buy_it_now');
	break;

	//--> Order received
	case 'ppauth' :
		$Successful = 1;
		add_filter('the_content', 'vps_net_buy_it_now');
		if (isset($_GET['auth']) && isset($_GET['merchant_return_link']) && isset($_POST['payer_email']) && isset($_POST['receiver_id']) && isset($_POST['payer_id']) && isset($_POST['verify_sign']) && isset($_POST['txn_id']) && isset($_POST['payment_type']) && isset($_POST['mc_currency']) && isset($_POST['residence_country'])) {
			$CustomerDTS = trim($_POST['txn_id']);
			$CustomerEM = trim($_POST['payer_email']);
			$TransID = trim($_POST['txn_id']);
			$ItemName = trim($_POST['item_name']);
			$ItemNumber = trim($_POST['item_number']);
			foreach ($_POST as $key => $value) { $flatOUT .= "\r\n$key: $value"; }
			$CurrentOrderStatus = "\r\n\r\nORDER RECEIVED [customer:".$CustomerEM."]\r\nlogged: ".$datetimeUNIX."\r\nremoteIP: ".$RemoteAddr."".$RemoteHostName."".$RemoteAgent."" . $flatOUT;
			//--> If order status [option] exists
			if(!add_option('buyitnow-purchase-paypal-'.$CustomerDTS.'',''.$CurrentOrderStatus.'')) {
				$PreviousOrderStatus = get_option('buyitnow-purchase-paypal-'.$CustomerDTS.'');
				//--> Only update the order status if this status type isn't already saved
				if (!strstr($PreviousOrderStatus,'ORDER RECEIVED [customer:'.$CustomerEM.']')) {
					update_option('buyitnow-purchase-paypal-'.$CustomerDTS.'', ''.$PreviousOrderStatus.''.$CurrentOrderStatus.'');
					wp_cache_set('buyitnow-purchase-paypal-'.$CustomerDTS.'',  ''.$PreviousOrderStatus.''.$CurrentOrderStatus.'');
				}
			}
			//--> else create new order status [option]
			else {
				add_option('buyitnow-purchase-paypal-'.$CustomerDTS.'',''.$CurrentOrderStatus.'');
			}

			//--> email admin with new order received message
			if ($GLOBALS["NewOrderMessage"] == 1) {
				buy_it_now_mailer('','no-reply@'.$_SERVER['SERVER_NAME'].'',''.$GLOBALS["StoreName"].'','New order received for: '.$ItemName.' ['.$ItemNumber.']',"You've received an order for '".$ItemName." [".$ItemNumber."]'!\r\n\r\nPayment is currently being processed by PayPal for customer '".$CustomerEM."'.\r\n\r\n".$GLOBALS["EmailFooterMessage"]."");
			}
		}
	break;

	//--> Payment verified/confirmed
		//-->> Also used to process other PayPal API callbacks, such as refunds/chargebacks and the like
	case 'goodppal' :
		if (isset($_GET['id']) && isset($_GET['pp']) && isset($_POST['payer_email']) && isset($_POST['receiver_id']) && isset($_POST['payer_id']) && isset($_POST['verify_sign']) && isset($_POST['txn_id']) && isset($_POST['payment_type']) && isset($_POST['mc_currency']) && isset($_POST['residence_country'])) {
			$CustomerDTS = trim($_POST['txn_id']);
			$CustomerEM = trim($_POST['payer_email']);
			$TransID = trim($_POST['txn_id']);
			$ItemName = trim($_POST['item_name']);
			$ItemNumber = trim($_POST['item_number']);
			foreach ($_POST as $key => $value) { $flatOUT .= "\r\n$key: $value"; }
			//-->> Updating status of payment [refund/declined/chargeback]
			if (isset($_POST['parent_txn_id']) && isset($_POST['reason_code'])) {
				$CustomerDTS = trim($_POST['parent_txn_id']);
				//-->>> Payment Refunded
				if (strstr($_POST['payment_status'],'Refunded')) {
					$wasRefunded = 1;
					$CurrentOrderStatus = "\r\n\r\nORDER CANCELLED/PAYMENT REFUNDED [customer:".$CustomerEM."]\r\nlogged: ".$datetimeUNIX."\r\nremoteIP: ".$RemoteAddr."".$RemoteHostName."".$RemoteAgent."" . $flatOUT;
				}
				//-->>> Payment Reversed
				if (strstr($_POST['payment_status'],'Reversed')) {
					$wasReversed = 1;
					$CurrentOrderStatus = "\r\n\r\nORDER CANCELLED/PAYMENT REVERSED [customer:".$CustomerEM."]\r\nlogged: ".$datetimeUNIX."\r\nremoteIP: ".$RemoteAddr."".$RemoteHostName."".$RemoteAgent."" . $flatOUT;
				}
			}
			//-->> Payment Completed, Processed
			else if (strstr($_POST['payment_status'],'Completed') || strstr($_POST['payment_status'],'Processed')) {
				$isGood = 1;
				$CurrentOrderStatus = "\r\n\r\nPAYMENT VERIFIED/CONFIRMED [customer:".$CustomerEM."]\r\nlogged: ".$datetimeUNIX."\r\nremoteIP: ".$RemoteAddr."".$RemoteHostName."".$RemoteAgent."" . $flatOUT;
			}
			//-->> Payment Pending
			else if (strstr($_POST['payment_status'],'Pending')) {
				$isPending = 1;
				$CurrentOrderStatus = "\r\n\r\nORDER RECIEVED/PAYMENT PENDING [customer:".$CustomerEM."]\r\nlogged: ".$datetimeUNIX."\r\nremoteIP: ".$RemoteAddr."".$RemoteHostName."".$RemoteAgent."" . $flatOUT;
			}
			//-->> Payment Denied, Expired, Failed, Voided
			else if (strstr($_POST['payment_status'],'Denied') || strstr($_POST['payment_status'],'Expired') || strstr($_POST['payment_status'],'Failed') || strstr($_POST['payment_status'],'Voided')) {
				$wasDenied = 1;
				$CurrentOrderStatus = "\r\n\r\nORDER CANCELLED/PAYMENT DENIED [customer:".$CustomerEM."]\r\nlogged: ".$datetimeUNIX."\r\nremoteIP: ".$RemoteAddr."".$RemoteHostName."".$RemoteAgent."" . $flatOUT;
			}
			//-->> Unspecified Status
			else {
				$unDefinedStatus = 1;
				$CurrentOrderStatus = "\r\n\r\nUNDEFINED TRANSACTION REPORT [customer:".$CustomerEM."]\r\nlogged: ".$datetimeUNIX."\r\nremoteIP: ".$RemoteAddr."".$RemoteHostName."".$RemoteAgent."" . $flatOUT;
			}
			//-->> If order status [option] exists
			if(!add_option('buyitnow-purchase-paypal-'.$CustomerDTS.'',''.$CurrentOrderStatus.'')) {
				$PreviousOrderStatus = get_option('buyitnow-purchase-paypal-'.$CustomerDTS.'');
				//-->>> Only update the order status if this status type isn't already saved
					//-->>>> Undefined Status Reports (callbacks) are always saved to local transaction record
				if ($unDefinedStatus || ($isGood && !strstr($PreviousOrderStatus,'PAYMENT VERIFIED/CONFIRMED [customer:'.$CustomerEM.']')) || ($wasRefunded && !strstr($PreviousOrderStatus,'ORDER CANCELLED/PAYMENT REFUNDED')) || ($wasReversed && !strstr($PreviousOrderStatus,'ORDER CANCELLED/PAYMENT REVERSED')) || ($wasDenied && !strstr($PreviousOrderStatus,'ORDER CANCELLED/PAYMENT DENIED')) || ($isPending && !strstr($PreviousOrderStatus,'ORDER RECIEVED/PAYMENT PENDING')) ) {
					update_option('buyitnow-purchase-paypal-'.$CustomerDTS.'', ''.$PreviousOrderStatus.''.$CurrentOrderStatus.'');
					wp_cache_set('buyitnow-purchase-paypal-'.$CustomerDTS.'',  ''.$PreviousOrderStatus.''.$CurrentOrderStatus.'');
				}
			}
			//--> else create new order status [option]
			else { add_option('buyitnow-purchase-paypal-'.$CustomerDTS.'',''.$CurrentOrderStatus.''); }

			//--> Emails only for primary callback process (successful payment), not for secondary callbacks (pending, denied, failed, etc.)
			if (!isset($_POST['parent_txn_id'])) {
				$storeAdminURL = get_option('siteurl').'/wp-admin';
				//--> email admin with new order received message
				if ($GLOBALS["PaymentVerifiedMessage"] == 1) {
					buy_it_now_mailer('','no-reply@'.$_SERVER['SERVER_NAME'].'',''.$GLOBALS["StoreName"].'','Payment verified and processed for Transaction #'.$TransID.'',"Payment for order submitted by ".$CustomerEM." for '".$ItemName." [".$ItemNumber."]' purchased at '".$GLOBALS["StoreName"]."' has been successfully processed.\r\n\r\nFor more information about this order, just log in to your WordPress Store '".$GLOBALS["StoreName"].": ".$storeAdminURL." to review the Dashboard Sales Monitor or log in to PayPal and reference Transaction ID: ".$TransID.".\r\n\r\n".$GLOBALS["EmailFooterMessage"]."");
				}
				//--> email user with instructions on how to retrieve the purchased item [digital delivery]
				$accessContentURL = "".$GLOBALS["StoreDomain"]."".$GLOBALS["callbackURI"]."wp-buyitnow-item=&wp-buyitnow-processor=PayPal";
					//-->> Include link on how to request a refund for this purchase
					$refundRequestURL = ''.$GLOBALS["StoreDomain"].''.$GLOBALS["callbackURI"].'email=&trid=';
				$accessContentMessage = "Thank you for your purchase. We have received your order for '".$ItemName." [".$ItemNumber."]' from, ".$GLOBALS["StoreName"].", our online store.\r\n\r\nPayment is currently being processed by PayPal for '".$CustomerEM."', your email address, as supplied to us by PayPal.\r\n\r\nTo access the digital content you've purchased click, or copy and paste this link unto your browser: ".$accessContentURL.". You will be required to verify your Transaction ID: ".$TransID." along with your email address: ".$CustomerEM." to unlock your access to the digital content you've purchased. If you're not satisfied with your purchase you have up to 30 Days to request a refund. You may do so by clicking on, or copying and pasting this link to your browser: ".$refundRequestURL."\r\n\r\nWe thank you once again for your purchase.\r\n\r\n".$GLOBALS["EmailFooterMessage"]."";
				buy_it_now_mailer($CustomerEM,'no-reply@'.$_SERVER['SERVER_NAME'].'',''.$GLOBALS["StoreName"].'','Your '.$GLOBALS["StoreName"].' Purchase: '.$ItemName.' ['.$ItemNumber.']',$accessContentMessage);
			}

			header('HTTP/1.0 200 OK');
			exit();
		}
	break;

	//--> 
	/* 
	Considering the fact we are working with the most basic of Google Checkout payment processing application interfaces, some restrictions are inherent, for example: Google Checkout will not report to us anything other than the TransactionID when a payment is made, therefore we must work with this restriction in verifying paid users. Furthermore, we can't email a Google Checkout user a digital delivery message, therefore we rely on Google Checkout's native support for digital delivery, which gives the customer a link back to our site after they've made a payment for their product. That link is tied into the access verification system, by product ID, by way of transaction id, therefore helping us deliver content dynamically based on these transaction ids. Also, during verification we save the email address provided by the user, in essence for Google Checkout payments, we rely ONLY on the transaction ID and append the email addresses to the transaction id, as submitted by the user. [we caputre all email addresses submitted to our system associated with every Google Checkout transaction, for security and monitoring]
	I strongly recommend you use these Buy-It-Now Buttons ONLY with ObfuscatedMarkup ON! [helps provide some tamper protection for your buttons from malicious and rogue users]
	*/

	//--> Google Checkout callback API provides us with a way to find cancellations/refunds via report codes
	//--> ALL THESE MUST BE PRESENT FOR NEW ORDER W GOOD PAYMENT
	//--> [-00001-7 Fulfillment Order State | -00005-1 Notification Acknowledgement | -00005-5 Risk Information | -00006-1 Order State Change Chargeable | -00008-1 Order State Charging | -00010-1 Order State Charged | -00010-2 Order State Completed ]
		//--> ANYTHING ELSE IS PAYMENT ALTERATION
		//--> [-00012-3 Payment Refund | -00013-1 Order Cancelled | -00014-1 Payment Cancelled | -00004-1 Order Cancelled]
	case 'goodgc' :
		if ( isset($_POST['serial-number'])) {
			add_filter('the_content', 'vps_net_buy_it_now');
			$CustomerDTS = trim($_POST['serial-number']);
			$bCDTS = explode("-", $CustomerDTS);
			$TransactionID = trim($bCDTS[0]);
			foreach ($_GET as $key => $value) { $flatOUT .= "\r\n$key: $value"; }
			foreach ($_POST as $key => $value) { $flatOUT .= "\r\n$key: $value"; }
			$logContents = "\r\n\r\nORDER RECEIVED [transactionID:".$TransactionID."]\r\ntransactionID: ".$TransactionID."\r\nlogged: ".$datetimeUNIX."\r\nremoteIP: ".$RemoteAddr."".$RemoteHostName."".$RemoteAgent."" . $flatOUT;
			if(!add_option('buyitnow-purchase-google-'.$TransactionID.'',''.$logContents.'')) {
				$PreviousOrderStatus = get_option('buyitnow-purchase-google-'.$TransactionID.'');
				//--> Only update the order status if this serial-number doesn't already exist in its file
				if (!strstr($PreviousOrderStatus,''.$CustomerDTS.'')) {
					$currentStatus = '';
					//--> If Google Checkout is calling back with status changes
					if (strstr($CustomerDTS,"-00013-1") || strstr($CustomerDTS,"-00014-1") || strstr($CustomerDTS,"-00004-1") || strstr($CustomerDTS,"-00012-3")) { 
						//--> Make sure it doesn't already exist, no need for duplicate status changes on file
						if(!strstr($PreviousOrderStatus,"ORDER CANCELLED/PAYMENT REFUNDED")) {
							$currentStatus = "\r\n\r\nORDER CANCELLED/PAYMENT REFUNDED [transactionID:".$TransactionID."]";
						}
					}

					//--> In other processes we accumulate all email addresses submitted by Google Checkout users [if one or more exists append all to end of our report]
					if (stristr($PreviousOrderStatus,'email_addy: ')) {
						$POS = explode('email_addy:',$PreviousOrderStatus);
						$POS[0] = trim($POS[0]) . ','.$CustomerDTS.'';
						$PreviousOrderStatus = "\r\n\r\n".implode("\r\nemail_addy:",$POS)."" . $currentStatus;
						$PreviousOrderStatus = str_replace("\r\n\r\nemail_addy:","\r\nemail_addy:",$PreviousOrderStatus);
					}
					//--> No email addy present just save it wholly
					else {
						$PreviousOrderStatus .= ','.$CustomerDTS.''.$currentStatus;
					}
					update_option('buyitnow-purchase-google-'.$TransactionID.'', ''.$PreviousOrderStatus.'');
					wp_cache_set('buyitnow-purchase-google-'.$TransactionID.'',  ''.$PreviousOrderStatus.'');
				}
			}
			else {
				add_option('buyitnow-purchase-google-'.$TransactionID.'',''.$logContents.'');
				//--> email admin with new order received message
				if ($GLOBALS["NewOrderMessage"] == 1) {
					buy_it_now_mailer('','no-reply@'.$_SERVER['SERVER_NAME'].'',''.$GLOBALS["StoreName"].'','Google Checkout order received: #'.$TransactionID.'',"You've received a new ".$GLOBALS["StoreName"]." order.\r\n\r\nPayment for this order is currently being processed by Google Checkout as Transaction #".$TransactionID.".\r\n\r\n".$GLOBALS["EmailFooterMessage"]."");
				}
			}
			header('HTTP/1.0 200 OK');
			header("Content-type: text/plain; charset=utf-8");
			echo 'serial-number='.$CustomerDTS.'';
			exit();
		}
	break;

	//--> User just came back from successful payment on Google Checkout
	//--> Check referer, then ask them to verify their transaction id and email address, keep in mind we only have associated transaction id reported to us by Google Checkout at this point in the in the process.
		//-->> ALSO, used as general entrance to the verification request form for other processes
	case 'gcauth':
		$Successful = 1;
		$isGCAUTH = 1;
		add_filter('the_content', 'vps_net_buy_it_now');
	break;

	//--> Verify/Unlock Access to Product Digital Locker
	case 'verify':
		global $wpdb;
		$myPTEM = $_POST['buyitnow-verify-email'];
		$myPTID = preg_replace('/[^A-Za-z0-9]/', '', $_POST['buyitnow-verify-tid']);
		$verifyQ = "SELECT * FROM ".$wpdb->options." WHERE option_name = 'buyitnow-purchase-google-".$myPTID."' OR option_name = 'buyitnow-purchase-paypal-".$myPTID."' ORDER BY option_id DESC";
		$theTransaction = $wpdb->get_results($verifyQ);
		$foundIt = count($theTransaction);
		//--> Coming in from a verify button entrance, skip error parsing
		if (isset($_POST['buyitnow-verify-new'])) { $NewVerify = 1; }
		//--> Found the transaction record we need 
		if ($foundIt == 1 && is_email($myPTEM)) {
			$myID = $theTransaction[0]->option_id;
			$myName = $theTransaction[0]->option_name;
			$myValue = $theTransaction[0]->option_value;
			if (stristr($myName,'PayPal')) { $imPaypal = 'PayPal'; $myProcessor = $imPaypal; $isprocessor = 'paypal'; }
			if (stristr($myName,'Google')) { $imGoogle = 'Google Checkout'; $myProcessor = $imGoogle; $isprocessor = 'google'; }
			//--> Payment Cancelled/Refunded/Declined
			if (stristr($myValue,'Cancelled') || stristr($myValue,'Refunded') || stristr($myValue,'Declined')) {
				$VerifyAgain = 1;
				$whatAction = 'cancelled';
				if (stristr($myValue,'Declined')) {
					$whatAction = 'declined';
				}
				if (stristr($myValue,'Reversed')) {
					$whatAction = 'reversed';
				}
				else if (stristr($myValue,'Refunded')) {
					$whatAction = 'refunded';
				}
				$whatAction = 'has been ' . $whatAction;
				$VerifyError = "Ooops, looks like we found an issue!<br><br>Seems like the payment made for Transaction ID: ".$myPTID." ".$whatAction.". As a result, your access to this digital content has been declined. If you have any questions or concerns please contact ".$myProcessor." for more information about this transaction.";
			}
			//-->> Verify Paypal transactions further by associated email address
			else if ($imPaypal && stristr($myValue,'payer_email: '. $myPTEM)) {
				$Verified = 1;
			}
			//--> Catch GOOGLE transactions and since we've verified they've input an email address we move forward
			else if ($imGoogle) {
				//-->> Append email address to local transaction record, if it exists
				if(!add_option('buyitnow-purchase-google-'.$myPTID.'','')) {
					$Verified = 1;
					$PreviousOrderStatus = get_option('buyitnow-purchase-google-'.$myPTID.'');
					//-->>> If orderNumber doesn't exist in the local transaction record, do so now [only Google transactions]
					if (!stristr($PreviousOrderStatus,'itemNumber: ')) {
						//-->>> If cookie contains our product info
						if (isset($_COOKIE['wp-buyitnow-product'])) {
							$myExtras = '';
							$myProductIdent = explode("dTXTb",$_COOKIE['wp-buyitnow-product']);
							$myItemNumber = $myProductIdent[0];
							if (strlen($myItemNumber) > 0) {
								if (ctype_xdigit($myProductIdent[1])) { 
									$unSaltedPrawnHeads = hexToStr($myProductIdent[1]);
									$unSaltedPrawnHeads = decrypt($unSaltedPrawnHeads, $myItemNumber);
									$losExtras = explode("-----",$unSaltedPrawnHeads);
									if (strlen($losExtras[0]) > 0) {
										$goodExtras = 1;
										$myExtras .= "\r\nitemURI: ".$losExtras[0]."";
									}
									if (strlen($losExtras[2]) > 0) {
										$myExtras .= "\r\nuserWebBrowser: ".$losExtras[2]."";
									}
								}
								$myProdIdent = "\r\nitemNumber: ".$myItemNumber."".$myExtras;
								$PreviousOrderStatus = str_replace("\r\nlogged: ","".$myProdIdent."\r\nlogged: ",$PreviousOrderStatus);
								update_option('buyitnow-purchase-google-'.$myPTID.'', "".$PreviousOrderStatus."");
								wp_cache_set('buyitnow-purchase-google-'.$myPTID.'',  "".$PreviousOrderStatus."");
							}
						}
					}
					//-->>> If email address entered hasn't been saved to the local transaction record, do so now [only Google transactions can have multiple email addresses considering we don't have reference to actual address used at Google Checkout, therefore we log all email address input by user, easier to track rouge users]
					if (!stristr($PreviousOrderStatus,'email_addy: '.$myPTEM.'')) {
						update_option('buyitnow-purchase-google-'.$myPTID.'', "".$PreviousOrderStatus."\r\nemail_addy: ".$myPTEM."");
						wp_cache_set('buyitnow-purchase-google-'.$myPTID.'',  "".$PreviousOrderStatus."\r\nemail_addy: ".$myPTEM."");
					}
				}
				//--> SERIOUS ERROR: How can this transaction not exist at this point in the process???? {HACK!}
				else { $VerifyAgain = 1; }
			}
			//--> PayPal: Email doesn't match the one saved within local transaction record [tid]
			else { $VerifyAgain = 1; }

			//-->> Find order information if specific params exist, which means our order setup is good to go
			$PreviousOrderStatus = get_option('buyitnow-purchase-'.$isprocessor.'-'.$myPTID.'');
			//-->> itemNumber: Google | item_number: PayPal
			if (strstr($PreviousOrderStatus,'logged: ') && (strstr($PreviousOrderStatus,'item_number: ') || strstr($PreviousOrderStatus,'itemNumber: '))) {
				$POS = explode("\r\n",$PreviousOrderStatus);
				$myFirstName = '';
				$myLastName = '';
				if (!$myItemNumber) { $myItemNumber=''; }
				$myLogged = '';
				foreach ($POS as $aPOS) {
					//-->>> Always, 1st item only
					if (strstr($aPOS,'logged: ') && !$myLogged) {
						$theL = explode("logged: ",$aPOS);
						$myLogged = trim($theL[1]);
					}
					if ((strstr($aPOS,'item_number: ') || strstr($aPOS,'itemNumber: ')) && !$myItemNumber) {
						if (strstr($aPOS,'item_number: ')) { $theINum = explode('item_number: ',$aPOS); }
						else if (strstr($aPOS,'itemNumber: ')) { $theINum = explode('itemNumber: ',$aPOS); }
						$myItemNumber = trim($theINum[1]);
					}
				}
				//-->> If verified then we set the cookie according to our params, based on product id
				//-->> Which is why product ID can only START with letters and contain letters, numbers and hyphens [no spaces]
				$myItemNumber = preg_replace('/[^A-Za-z0-9-]/', '', $myItemNumber);
				$sPTID = $myPTID;
				$mySaltine = encrypt("".trim($myID)."-----".trim($sPTID)."-----".$myLogged."",$myItemNumber);
				$mySaltine = strToHex($mySaltine);
				if ($Verified) {
					setcookie("wp-buyitnow-pass", $mySaltine, time()+31536000, SITECOOKIEPATH, $GLOBALS["StoreDomainName"]);
				}
			}
		}
		//--> Re-request verification
		else { $VerifyAgain = 1; }
		
		//-->> If did not pass, yet cookie for this item exists, we kill it [meaning they've changed the status of the payment since cookie set]
		if ($VerifyAgain) {
			if(isset($_COOKIE['wp-buyitnow-pass'])) {
				setcookie("wp-buyitnow-pass", "", time()-31536000, SITECOOKIEPATH, $GLOBALS["StoreDomainName"]);
			}
		}



//		setcookie("wp-buyitnow-".$mySaltine."", '1', time()+31536000, SITECOOKIEPATH, $GLOBALS["StoreDomainName"]);

		add_filter('the_content', 'vps_net_buy_it_now');
	break;

	//--> Refund request, user clicked a link within email we sent
	case 'refund':
		$RefundRequest = 1;
		add_filter('the_content', 'vps_net_buy_it_now');
	break;

	//--> Verify/Process refund request
	case 'DOrefund':
		global $wpdb;
		$myPTEM = $_POST['buyitnow-refund-email'];
		$myPTID = preg_replace('/[^A-Za-z0-9]/', '', $_POST['buyitnow-refund-tid']);
		$verifyQ = "SELECT * FROM ".$wpdb->options." WHERE option_name = 'buyitnow-purchase-google-".$myPTID."' OR option_name = 'buyitnow-purchase-paypal-".$myPTID."' ORDER BY option_id DESC";
		$theTransaction = $wpdb->get_results($verifyQ);
		$foundIt = count($theTransaction);
		$isNewRefund = 1;
		//-->> NO REFUNDS AFTER 90 DAYS NO MATTER WHAT, GC and PP limit
		$refundable = 90;
		if (isset($GLOBALS["RefundTime"]) && (int)$GLOBALS["RefundTime"] > 0) { 
			$refundable = (int)$GLOBALS["RefundTime"]; 
		}
		//--> Found the transaction record we need
		if ($foundIt == 1 && is_email($myPTEM)) {
			$myName = $theTransaction[0]->option_name;
			$myValue = $theTransaction[0]->option_value;
			if (stristr($myName,'PayPal')) { $imPaypal = 'PayPal'; $myProcessor = $imPaypal; $myMerchantURI = 'http://'.$PaymentURLPP; $isprocessor = 'paypal'; }
			if (stristr($myName,'Google')) { $imGoogle = 'Google Checkout'; $myProcessor = $imGoogle; $myMerchantURI = 'http://'.$PaymentURLGC; $isprocessor = 'google'; }
			//-->> Verify Paypal transactions further by associated email address, otherwise verify this is a Google Checkout transaction
			if (($imPaypal && stristr($myValue,'payer_email: '. $myPTEM)) || $imGoogle) {
				$RefundRequest = 1;
				$RefundResponse = "You've successfully submitted your refund request for Transaction ID: ".$myPTID.".<br><br>Please allow up to 72 hours for your refund to take effect. If you have any questions or concerns regarding this refund request please contact ".$myProcessor.".";
				$PreviousOrderStatus = get_option('buyitnow-purchase-'.$isprocessor.'-'.$myPTID.'');
				$myEmail = '';
				$UsersFullName = '';
				//-->> Verify all transactions by making sure we have logged: something to it in the past, hence it exists
				if (strstr($PreviousOrderStatus,'logged: ')) {
					$POS = explode("\r\n",$PreviousOrderStatus);
					$myFirstName = '';
					$myLastName = '';
					foreach ($POS as $aPOS) {
						//-->>> Always, 1st item only
						if (strstr($aPOS,'logged: ') && !$myLogged) {
							$theL = explode("logged: ",$aPOS);
							$myLogged = trim($theL[1]);
						}
						else if (strstr($aPOS,'first_name: ') && !$myFirstName) {
							$theF = explode("first_name: ",$aPOS);
							$myFirstName = trim($theF[1]);
						}
						else if (strstr($aPOS,'last_name: ') && !$myLastName) {
							$theL = explode("last_name: ",$aPOS);
							$myLastName = trim($theL[1]);
						}
					}
					
					$now = time();
					$purchaseDate = $myLogged;
					$daysSince = round(abs($now-$purchaseDate)/60/60/24) - 1;
					//-->> Make sure we are within the refund time allotment
					if (isset($GLOBALS["RefundTime"]) || $daysSince > 90) {
						if (((int)$GLOBALS["RefundTime"] > 0 && $daysSince > (int)$GLOBALS["RefundTime"]) || $daysSince > 90) {
							unset($RefundResponse);
							$RefundRequest = 1;
							$RefundError = "Unfortunately, we aren't able to issue you a refund on Transaction ID: ".$myPTID.", considering it was bought over ".$daysSince." days ago. We only offer refunds for purchases made within the last ".$refundable." days.<br><br>We apologize for the inconvenience this may have caused. If you have any questions or issues regarding this transaction please contact ".$imGoogle.".";
						}
					}
					//-->> Only if no error ATM
					if (!$RefundError) {
						//-->> Is Google, append email address to local transaction record, no reason why it shouldn't exist, still we must double-check
						if ($imGoogle) {
							//-->> Append email address to local transaction record, if it exists
							if(!add_option('buyitnow-purchase-google-'.$myPTID.'','')) {
								//-->>> If email address entered hasn't been saved to the local transaction record, do so now [only Google transactions can have multiple email addresses considering we don't have reference to actual address used at Google Checkout, therefore we log all email address input by user, easier to track rouge users]
								if (!stristr($PreviousOrderStatus,'email_addy: '.$myPTEM.'')) {
									$myEmail = "\r\nemail_addy: ".$myPTEM."";
								}
							}
						}
						//-->> Is Paypal, find First/Last name to make it easier for admin to find transaction at PayPal
						else {
							$UsersFullName = $myFirstName . ' ' . $myLastName;
							$fromUser = 'from '.$UsersFullName;
						}
						//--> Is first refund request
						if (!strstr($PreviousOrderStatus,'REFUND REQUESTED')) {
							//-->>> Tag Transaction as REFUND REQUESTED
							update_option('buyitnow-purchase-'.$isprocessor.'-'.$myPTID.'', "\r\n\r\nREFUND REQUESTED [transactionID:".$myPTID."]\r\n\r\n".$PreviousOrderStatus."".$myEmail."");
							wp_cache_set('buyitnow-purchase-'.$isprocessor.'-'.$myPTID.'',  "\r\n\r\nREFUND REQUESTED [transactionID:".$myPTID."]\r\n\r\n".$PreviousOrderStatus."".$myEmail."");
						}
						//--> refund was already requested in the past
						else { $isNewRefund = 0; }
						//--> Only if first time requesting refund [message both parties]
						if ($isNewRefund == 1) {
							//-->> Send User a Confirmation of Refund Request
							buy_it_now_mailer($myPTEM,'no-reply@'.$_SERVER['SERVER_NAME'].'',''.$GLOBALS["StoreName"].'','REFUND REQUESTED: Transaction/Order ID Number '.$myPTID.'',"You've requested a refund for Transaction/Order ID: ".$myPTID.". Please allow up to 72 hours for your refund to take effect. We've granted you a refund considering this purchase is within the alloted ".$refundable."-day grace period.\r\n\r\n".$GLOBALS["EmailFooterMessage"]."");
							//-->> Send Administrator a Refund Request Notice, admin must manually Refund a payment transactions with the appropriate payment processor
							buy_it_now_mailer('','no-reply@'.$_SERVER['SERVER_NAME'].'',''.$GLOBALS["StoreName"].'','REFUND REQUEST: Transaction/Order ID Number '.$myPTID.'',"You've received a refund request ".$fromUser." for Transaction/Order ID: ".$myPTID.". Please log in to ".$myProcessor.": ".$myMerchantURI." to issue a refund for this transaction. Keep in mind, we've already calculated this order to be less than ".$refundable." days old, the refund grace period you've set. Therefore, this order has qualified for a refund at the time of its request.\r\n\r\nPLEASE NOTE: Returns will not be completed until you have logged on to ".$myProcessor.": ".$myMerchantURI." and have manually issued a refund for Transaction/Order ID: ".$myPTID.".\r\n\r\n".$GLOBALS["EmailFooterMessage"]."");
						}
					}
				}
			}
		}
		if (!$RefundResponse && !$RefundRequest) {
			$RefundRequest = 1;
			$RefundError = "Ooops, that caused an error!<br><br>Let's try that again, make sure you copy and paste the Transaction ID Number as provided by your Google Checkout payment transaction or for PayPal users, your transaction ID will be located in the email we sent you when you originally made your purchase. Also, make sure you use the proper email address, the address associated with your PayPal or Google Checkout account. Keep in mind, sometimes PayPal and Google Checkout may take a bit longer to process a payment, which means your transaction can't be refunded until we have received confirmation of payment from either party.";
		}
		add_filter('the_content', 'vps_net_buy_it_now');
	break;

}
?>