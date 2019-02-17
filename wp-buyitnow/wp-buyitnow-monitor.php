<?php 
if(!defined('ABSPATH')){
	header('HTTP/1.1 403 Forbidden');
	exit();
}
/**
* @package Buy It Now, WordPress: Dashboard Monitor [Sales & Payment Monitor]
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

//--> Creates Dashboard [Sales/Payment] Monitoring Box
function buyitnow_monitor_output() {
	$widget_options = buyitnow_monitor_options();


	//-->> Mostly paging mechanism
	$imCurrentlyViewing = 'There are currently no orders on file.';
	$whatOrder = ' order';
	$theWhat = '';
	$mostWhat = '';
	global $wpdb;
	$query = "SELECT * FROM ".$wpdb->options." WHERE option_name LIKE 'buyitnow-purchase-%' ORDER BY option_id DESC";
	$findOrders = $wpdb->get_results($query);
	$totalOrders = count($findOrders);
	$limit = (int)$widget_options['view_order_amount'];
	$limite = $limit;
	$first = '&lt;&lt; first';
	$prev = '&lt; prev';
	$next = 'next &gt;';
	$last = 'last &gt;&gt;';
	if (isset($_GET["buy-it-now-start"]) && (int)$_GET["buy-it-now-start"] > 0) {
		$limite = (int)$_GET["buy-it-now-start"] . ', ' . $limit;
		$first = '<a href="?buy-it-now-start=0#buyitnow_dashboard_monitor">'. $first . '</a>';
		$prev = '<a href="?buy-it-now-start='.((int)$_GET["buy-it-now-start"]-$limit).'#buyitnow_dashboard_monitor">'. $prev . '</a>';
	}
	if ($limit != -1) {
		$query .= " LIMIT $limite";
	}
	$results = $wpdb->get_results($query);
	$currentOrders = count($results);
	$viewing = $limit;
	if (!isset($_GET["buy-it-now-start"]) || (int)$_GET["buy-it-now-start"] == 0) {
		$theWhat = '';
		$mostWhat = ' most recent ';
		$prevfirst = 'display:none;';
	}
	if ($currentOrders > 1) { 
		$whatOrder .= 's'; 
		$outOf = ' out of <span class="b">'.$totalOrders.'</span> total'; 
	}
	else { 
		$currentOrders = ''; 
		$outOf = ''; 
	}
	if ($limit >= $totalOrders) { 
		$viewing = $totalOrders;
		$prevfirst = 'display:none;';
		$nextlast = 'display:none;';
		$theWhat = '';
		$mostWhat = '';
		$imCurrentlyViewing = 'Viewing all orders, currently there are <span class="b">'.$currentOrders.'</span> on file.';
	}
	else if ($totalOrders > $limit) {
		if (((int)$_GET["buy-it-now-start"] + $limit) < $totalOrders) {
			$next = '<a href="?buy-it-now-start='.((int)$_GET["buy-it-now-start"]+$limit).'#buyitnow_dashboard_monitor">'. $next . '</a>';
			$myLimit = ((int)($totalOrders / $limit)*$limit);
			if ($myLimit == $totalOrders) { $myLimit = (((int)($totalOrders / $limit)*$limit)-$limit); }
			$last = '<a href="?buy-it-now-start='.$myLimit.'#buyitnow_dashboard_monitor">'. $last . '</a>';
		}
		else {
			$theWhat = '';
			$mostWhat = ' eldest ';
			$nextlast = 'display:none;';
		}
		if (!$nextlast && !$prevfirst) {
			$imCurrentlyViewing = 'Viewing '.$whatOrder.' <span class="b">'.((int)$_GET["buy-it-now-start"] +1).' - '.((int)$_GET["buy-it-now-start"] + $currentOrders).'</span>'.$outOf.'.';
		}
		else {
			$imCurrentlyViewing = 'Viewing '.$theWhat.'<span class="b">'.$currentOrders.'</span>'.$mostWhat.''.$whatOrder.'</span>'.$outOf.'.';
		}
	}

?>
	<style type="text/css">
		#buyitnow_dashboard_monitor p.sub,#buyitnow_dashboard_monitor .table,#buyitnow_dashboard_monitor .versions{margin:-12px;}
		#buyitnow_dashboard_monitor .inside{font-size:12px;padding-top:20px;}
		#buyitnow_dashboard_monitor p.sub{padding:5px 0 5px;color:#8f8f8f;font-size:14px;position:relative;top:-17px;left:15px;}
		#buyitnow_dashboard_monitor .table{margin:0;padding:0;position:relative;}
		#buyitnow_dashboard_monitor .table_discussion{float:right;border-top:#ececec 1px solid;width:100%;}
		#buyitnow_dashboard_monitor .table_header{ border-bottom:#ececec 1px solid;width:100%; }
		#buyitnow_dashboard_monitor .table_footer{ border-top:#ececec 1px solid;width:100%; }
		#buyitnow_dashboard_monitor table td{padding:3px 0;white-space:nowrap;}
		#buyitnow_dashboard_monitor table tr.first td{border-top:none;}
		#buyitnow_dashboard_monitor .t{font-size:12px;padding-right:12px;padding-top:6px;color:#777;}
		#buyitnow_dashboard_monitor .t a{white-space:nowrap;}
		#buyitnow_dashboard_monitor .spam{color:red;padding-left:0px;}
		#buyitnow_dashboard_monitor .waiting{color:#e66f00;padding-left:0px;}
		#buyitnow_dashboard_monitor .approved{color:green;padding-left:0px;}
		#buyitnow_dashboard_monitor .comments{padding-left:0px;}
		#buyitnow_dashboard_monitor .comments-pc{padding-left:0px;}
		#buyitnow_dashboard_monitor .spam:hover, 
		#buyitnow_dashboard_monitor .waiting:hover, 
		#buyitnow_dashboard_monitor .approved:hover,
		#buyitnow_dashboard_monitor .comments:hover{color:#d54e21;padding-left:0px;}
		#buyitnow_dashboard_monitor .versions{padding:6px 10px 12px;clear:both;}
		#buyitnow_dashboard_monitor .versions .b{font-weight:bold;}
	</style>
	<script language="Javascript" type="text/javascript">
	var viewingTransaction = '';
	var toggleTransaction = function(itemid) {
		if (viewingTransaction == itemid) {
			jQuery("#vps-net-buy-it-now-"+viewingTransaction+"").fadeOut('fast');
			vtbgp = jQuery("#"+viewingTransaction+"").css("background-image").replace('arrows-dark','arrows');
			jQuery("#"+viewingTransaction+"").css("background-image",""+vtbgp+"");
			viewingTransaction = '';
		}
		else {
			if (viewingTransaction != '') {
				jQuery("#vps-net-buy-it-now-"+viewingTransaction+"").fadeOut('fast');
				vtbgp = jQuery("#"+viewingTransaction+"").css("background-image").replace('arrows-dark','arrows');
				jQuery("#"+viewingTransaction+"").css("background-image",""+vtbgp+"");
			}
			mybgp = jQuery("#"+itemid+"").css("background-image").replace('arrows','arrows-dark');
			jQuery("#"+itemid+"").css("background-image",""+mybgp+"");
			jQuery("#vps-net-buy-it-now-"+itemid+"").fadeIn('normal');
			viewingTransaction = itemid;
		}
	};
	</script>
	<div class='versions table_header' style='position:relative;top:-12px;left:0px;'>
		<center>
		<table width="100%">
			<tr>
				<td><div style='<?php echo $prevfirst; ?>'><span id="vps-net-buy-it-now-first"><?php echo $first; ?></span>&nbsp;&nbsp;<span id="vps-net-buy-it-now-prev"><?php echo $prev; ?></span></div></td>
				<td width="100%" align="center"><span><?php echo $imCurrentlyViewing; ?></span></td>
				<td><div style='<?php echo $nextlast; ?>'><span id="vps-net-buy-it-now-next"><?php echo $next; ?></span>&nbsp;&nbsp;<span id="vps-net-buy-it-now-last"><?php echo $last; ?></span></div></td>
			</tr>
		</table>
		</center>
	</div>
	<div class='table table_discussion' style='position:relative;top:18px;left:0px;padding-bottom:18px;'>
	<table width="100%">
		<tr>
			<td><p class="sub">Order (Transaction ID)</p></td>
			<td><p class="sub">ePay</p></td>
			<td><p class="sub">Received</p></td>
			<td><p class="sub">Status</p></td>
		</tr>

<?php
	foreach ( $results as $result ) {
		$myOrderInfo = split("-",$result->option_name);
		$myOrderInfo = str_replace("google",'<a href="https://'.$GLOBALS["PaymentURLGC"].'sell" target="Google">Google</a>',$myOrderInfo);
		$myOrderInfo = str_replace("paypal",'<a href="https://'.$GLOBALS["PaymentURLPP"].'" target="PayPal">PayPal</a>',$myOrderInfo);
		$myPaymentCenter = $myOrderInfo[2];
		$myPaymentData = $result->option_value;
		$bCDTS = explode("\n", $myPaymentData);
		//--> find logged timestamp
		if (strstr($myPaymentData,'logged: ')) {
			foreach ($bCDTS as $aCDTS) {
				if (strstr($aCDTS,'logged: ')) {
					$Logged = str_replace("logged: ",'',$aCDTS);
				}
			}
		}
		//--> GC and PayPal TRID get extracted from option_name [original transaction id is our control]
		$TransactionID = $myOrderInfo[3];
		$myOrderState = 'comments';
		$myOrderState2 = 'total';
		$myStatus = 'posted';
		$statusTITLE = 'Order Received';
		$myLoggedDate = date('M d, Y',trim($Logged));
		if (strstr($myPaymentData,'ORDER RECIEVED/PAYMENT PENDING') || strstr($myPaymentData,'ORDER CANCELLED/PAYMENT REFUNDED') || strstr($myPaymentData,'ORDER CANCELLED/PAYMENT REVERSED') || strstr($myPaymentData,'ORDER CANCELLED/PAYMENT DENIED')) { 
			$myOrderState = 'waiting';
			$myOrderState2 = 'pending';
			$myStatus = 'declined';
			$statusTITLE = 'Payment Declined';
			if (strstr($myPaymentData,'ORDER CANCELLED/PAYMENT REVERSED')) {
				$myOrderState = 'spam';
				$myOrderState2 = $myOrderState; 
				$myStatus = 'reversed';
				$statusTITLE = 'Order Cancelled/Payment Reversed';
			}
			else if (strstr($myPaymentData,'ORDER CANCELLED/PAYMENT REFUNDED')) {
				$myOrderState = 'spam';
				$myOrderState2 = $myOrderState; 
				//--> these are set to waiting/pending for the yellow color
				$myStatus = 'refunded';
				$statusTITLE = 'Order Cancelled/Payment Refunded';
			}
		}
		else if (stristr($myPaymentCenter,'paypal')) {
			//-->> Google: ORDER RECEIVED status is ok [PayPal: must be PAYMENT VERIFIED/CONFIRMED]
			if ((strstr($myPaymentData,'ORDER RECEIVED') || strstr($myPaymentData,'PAYMENT VERIFIED/CONFIRMED')) && stristr($myPaymentCenter,'paypal')) { 
				if (strstr($myPaymentData,'PAYMENT VERIFIED/CONFIRMED')) {
					$myOrderState = 'approved'; $myOrderState2 = $myOrderState;
					$myStatus = 'verified';
					$statusTITLE = 'Payment Verified';
				}
				else {
					// only paypal can be set to pending, for google's (basic) unsigned cart process doesn't allow it
					$myOrderState = 'waiting'; 
					$myOrderState2 = 'pending';
					$myStatus = 'pending';
					$statusTITLE = 'Payment Pending Verification';
				}
			}
		}
		$orderTD = $myOrderState;
		$orderHREF = '';
		$orderSPAN = $myOrderState2.'-count';
		$orderACLASS = $myOrderState;
		$statusSPAN = $myOrderState;
		echo "	<tr onclick=\"javascript:toggleTransaction('".trim($TransactionID)."');\" title='".$statusTITLE."' >";
		echo "		<td><div id='".trim($TransactionID)."' class='sidebar-name-arrow' style='cursor:pointer;background-position:left top;float:left;'></div><span class='".$orderACLASS."'><span class='".$orderSPAN."'>". $TransactionID ."</span></span></td>";
		echo "		<td class='comments-pc'>". $myPaymentCenter ."</td>";
		echo "		<td class='comments-pc'><span class='".$receivedSPAN."'>". $myLoggedDate ."</span></td>";
		echo "		<td class='comments-pc'><span class='".$statusSPAN."'>". $myStatus ."</span></td>";
		echo "	</tr>";
		echo "	<tr><td colspan='4'><div id='vps-net-buy-it-now-".trim($TransactionID)."' style='overflow:hidden;display:none;padding-bottom:18px;'>";
		echo "	<table width='100%'>";
		$seenCDTS = '';
		$seenCDTS1 = '';
		foreach ($bCDTS as $aCDTS) {
			$eCDTS = explode(": ", $aCDTS);
			if (!strstr($seenCDTS,' '.$eCDTS[0].': '.$eCDTS[1].' ') && !strstr($aCDTS,'[') && !strstr($aCDTS,']')) {
				$seenCDTS .= ' ' . $eCDTS[0].': '.$eCDTS[1].' ';
				$myDataValue = $eCDTS[1];
				if (strlen($eCDTS[1]) > 25) {
					$myDataValue = substr($myDataValue,0,25) . '...';
				}
				//-->> logged seen variables [never same variable twice in a callback, therefore any repeats means new callback report start]
				if (!strstr($seenCDTS1,' '.$eCDTS[0].' ')) {
					$seenCDTS1 .= ' ' . $eCDTS[0].' ';
				}
				//-->> break in between reports [and start variable filter over again]
				else {
					$seenCDTS1 = ' ' . $eCDTS[0].' ';
					echo "		<tr><td>&nbsp;</td><td width='100%'>&nbsp;</td></tr>";
					echo "		<tr><td>&nbsp;</td><td width='100%'>&nbsp;</td></tr>";
				}
				if (stristr($eCDTS[0],"logged")) {
					$eCDTS[1] = date('M d, Y',trim($eCDTS[1])) . " at " . date('H:i:s',trim($eCDTS[1])) . " GMT";
					$myDataValue = $eCDTS[1];
				}
				echo "		<tr><td style='padding-left:18px;'>".ucfirst($eCDTS[0])."</td><td width='100%' style='padding-left:6px;cursor:help;' title='".trim($eCDTS[1])."'>".$myDataValue."</td></tr>";
			}
		}
		echo "	</table></div></td></tr>";
	}
	?>
	
	</table>
	</div>
	<div class='versions table_footer' style='position:relative;top:0px;left:0px;'>
		<p><center>Buy-It-Now Plugin v1.3 by <span class="b"><a href="http://www.vps-net.com/" target="VPS-NET-COM" title="Virtual Private Servers & Networks - Information technology and Internet development at its' brightest.">VPS-NET</a></span>, featuring tamper resistant <span class="b"><a href="http://www.vps-net.com/internet-development-tools/html-data-obfuscation-web-tool.php" target="VPS-NET-COM" title="HTML & Data Obfuscator by VPS-NET - Mask e-mail addresses, links, data resources, phone numbers and source code with ease.">obfuscated code</a></span> buttons!</center></p>
	</div>

	
	<?php
}

//--> Dashboard action hook
function buyitnow_append_dashboard_widget() {
	wp_add_dashboard_widget('buyitnow_dashboard_monitor', 'Buy It Now by VPS-NET (Sales Monitor)', 'buyitnow_monitor_output', 'buyitnow_monitor_setup' );
}

function buyitnow_monitor_options() {
	$defaults = array( 'view_order_amount' => 5 );
	if ( ( !$options = get_option( 'buyitnow_dashboard_monitor' ) ) || !is_array($options) )
		$options = array();
	return array_merge( $defaults, $options );
}


function buyitnow_monitor_setup() {
	$options = buyitnow_monitor_options();
	if ( 'post' == strtolower($_SERVER['REQUEST_METHOD']) && isset( $_POST['widget_id'] ) && 'buyitnow_dashboard_monitor' == $_POST['widget_id'] ) {
		foreach ( array( 'view_order_amount' ) as $key )
				$options[$key] = $_POST[$key];
		update_option( 'buyitnow_dashboard_monitor', $options );
	}
 
?>
 
		<p>
			<label for="view_order_amount"><?php _e('How many orders would you like to display in the dashboard sales monitor?', 'default' ); ?>
				<select id="view_order_amount" name="view_order_amount">
					<?php for ( $i = 5; $i <= 200; $i = $i + 5 )
						echo "<option value='$i'" . ( $options['view_order_amount'] == $i ? " selected='selected'" : '' ) . ">$i</option>";
						?>
					</select>
				</label>
 		</p>
 
<?php
}

// Register the new dashboard widget into the 'wp_dashboard_setup' action hook
add_action('wp_dashboard_setup', 'buyitnow_append_dashboard_widget' );

?>
