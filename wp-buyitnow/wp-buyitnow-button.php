<?php 
if(!defined('ABSPATH')){
	header('HTTP/1.1 403 Forbidden');
	exit();
}
/**
* @package Buy It Now, WordPress: Custom Button Generator
*/
/*
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

	//--> If discount set at document/post level, use it as our discount
	if (isset($ItemDiscount) && (int)$ItemDiscount > 0) { $discount = (int)$ItemDiscount; }
	//--> Else, fall back to global discount settings
	else { $discount = (int)$GLOBALS["discount"]; }

	//--> Product Price [Unchanged]
	$pPrice = sprintf('%.2f', $ProductPrice);
	$actualFullPrice = $pPrice;

	if ($discount >= 1) {
		$discountAmount = sprintf('%.2f', (($actualFullPrice / 100)*$discount));
		$PriceAfterDiscount = sprintf('%.2f', ($actualFullPrice-$discountAmount));
		//--> PayPal Discount Field [discount percentage, to be calculated at PayPal]
		$discountFieldPP = '<input type="hidden" name="discount_rate" value="'.$discount.'"/>';
	}
	else { $PriceAfterDiscount = $actualFullPrice; }

	if (($GLOBALS["IsTaxable"] == 1 || (int)$TaxableItem > 0) && (isset($GLOBALS["TaxRate"]) || isset($TaxableRate))) {
		if (isset($TaxableRate)) { $myTaxRate = $TaxableRate; }
		else { $myTaxRate = $GLOBALS["TaxRate"]; }
		$itemTaxRate = $myTaxRate;
		$taxFieldPP = '<input type="hidden" name="tax_rate" value="'.$itemTaxRate.'"/>';
	}
	//--> force to NOT charge tax
	else {
		// tax_exempt
	}

	$myStoreName = stripslashes($GLOBALS["StoreName"]);
	$myProductName = stripslashes($ProductName);

	if (isset($ProductID)) { $myProductID = ereg_replace("[^A-Za-z0-9-]", "", $ProductID); }
	else {
		$cleanSN = ereg_replace("[^A-Za-z0-9-]", "", $GLOBALS["StoreName"]);
		$cleanPN = ereg_replace("[^A-Za-z0-9-]", "", $ProductName);
		$myProductID = $cleanSN . '-' . $cleanPN;
	}

	//--> Price of item, unaltered
	$ItemPrice = $actualFullPrice;
 
	//--> DATETIME stamp as CustomerID
	//$datetime = new DateTime();
	//$CustomerID = ereg_replace("[^A-Za-z0-9]", "", $datetime->format('Y-m-d H:i:sP'));
	$CustomerID = $GLOBALS["datetimeUNIX"];

	//--> PayPal URIs
		//-->> URIs used for returning user to site after payment action was completed/cancelled
		$returnURLPP = ''.$GLOBALS["StoreDomain"].''.$GLOBALS["callbackURI"].'merchant_return_link=vps-net-sales&auth='.$CustomerID.'';
		$cancelURLPP = ''.$GLOBALS["StoreDomain"].''.$GLOBALS["callbackURI"].'act=cancelled';
		//-->> URI used for processing IPN transaction information [ paypal postback ]
		$goodURLPP = ''.$GLOBALS["StoreDomain"].''.$GLOBALS["callbackURI"].'act=goodppal&id='.$CustomerID.'&pp='.$ItemPrice.'&pn='.$myProductName.'&actprice='.$actualFullPrice;



	//--> Generate shipping tables
		//-->> PayPal Shipping Table
		$PPShipping = '';
		foreach ($GLOBALS["PayPal_Shipping_Table"] as $i => $value) {
			$PPShipping .= '<input type="hidden" name="'.$i.'" value="'.$value.'"/>';
		}


	$myBuyItNowButtonPayPal = '
	<form name="vps-net-com-paypal" action="'.$GLOBALS["fullPaymentURLPP"].'" method="post">
		<input type="hidden" name="business" value="'.$GLOBALS["PaymentMerchantPP"].'"/>
		<input type="hidden" name="cmd" value="'.$GLOBALS["ButtonTypePP"].'"/>
		<input type="hidden" name="item_name" value="'.$myProductName.'"/>
		<input type="hidden" name="item_number" value="'.$myProductID.'"/>
		<input type="hidden" name="currency_code" value="'.$GLOBALS["CurrencyCode"].'"/>
		<input type="hidden" name="amount" value="'.$ItemPrice.'"/>
		<input type="hidden" name="return" value="'.$returnURLPP.'"/>
		<input type="hidden" name="rm" value="2"/>
		<input type="hidden" name="notify_url" value="'.$goodURLPP.'"/>
		<input type="hidden" name="cancel_return" value="'.$cancelURLPP.'"/>
		<input type="hidden" name="no_note" value="1"/>
		<input type="hidden" name="cbt" value="Return to '.$myStoreName.'"/>
		'.$PPShipping.'
		'.$discountFieldPP.'
		'.$taxFieldPP.'
		'.$GLOBALS["testingField"].'
		<input type="image" name="PayPal" '.$GLOBALS["PayPalButtonImage"].'/>
	</form>
	';


?>

