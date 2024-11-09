<?php
// a simple Blink API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php file and define your unique credentials for this service
require "credentials.php";  //$secret_key, $api_key

// API details
$urlAccessToken = 'https://secure.blinkpayment.co.uk/api/pay/v1/tokens';
$api_base = 'https://secure.blinkpayment.co.uk';
$urlPaymentToken = 'https://gateway2.blinkpayment.co.uk/paymentform';

// service-specific strings
$title = "Blink";
$connect = "Get Blink Token";
$cookie = "movingwifi-blink";

if (isset($_REQUEST['operation']))
{
	if($_REQUEST['operation'] == 'cookie')
	{
		if (isset($_COOKIE[$cookie]))
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			print blink_head("$title | cookie contents", "Home", "Cookie contents");
			print '<pre>';
			print_r($cdata);
			print '</pre>';
			print footer("Disconnect", "");
		}
		else
		{
			print blink_head("$title", "Click to continue", "No cookie found");
		}
	}
	elseif($_REQUEST['operation'] == 'revoke')
	{
		setcookie($cookie,"", time() - 3600, "/");  //delete cookie
		print blink_head($title, "Disconnected", "Token revoked");
	}
	elseif($_REQUEST['operation'] == 'token')
	{
		$data = getBlinkAccessToken($urlAccessToken, $api_key, $secret_key, array("enable_moto_payments" => true, "application_name" => "MOT Manager Sandbox", "source_site"=>"apis.movingwifi.com"));
		if ($data['code'] == 201)
		{
			$token = $data['response'];
			setcookie($cookie, serialize($token), strtotime('+6 months'), '/');
			print blink_head($title, "Connected - click to continue", "Token acquired");
		}
		else
		{
			setcookie($cookie,"", time() - 3600, "/");  //delete cookie
			print blink_head($title, "Click to continue", "Request failed");
			print "<pre>\n";
			print_r($data);
			print "</pre>\n";
		}	
	}
	elseif($_REQUEST['operation'] == 'payment-form')
	{
		print blink_head($title, "Home", "Make payment");
		print payment_form();
	}
	elseif($_REQUEST['operation'] == 'payment')
	{
		if (isset($_COOKIE[$cookie]))
		{
			$amount = $_REQUEST['BlinkAmount'];
			$token = unserialize($_COOKIE[$cookie]);
			// 1. get intent
			$intent_data = blinkAPIrequest($api_base . "/api/pay/v1/intents", $token->access_token, array(
				"transaction_type" => "SALE",
				"payment_type" => "credit-card",
				"amount" => $amount, 
				"currency" => "GBP", 
				"return_url" => "https://apis.movingwifi.com/blink/return.php",
				"notification_url" => "https://apis.movingwifi.com/blink/notification.php",
				)
			);
			if ($intent_data["code"] == 201)
			{
				$cardNo = $_REQUEST['BlinkCardNo'];
				$expiry = $_REQUEST['BlinkExpiry'];
				$cvv = $_REQUEST['BlinkCVV'];
				$merchantID = $intent_data["response"]->merchant_id;
				// 2. get paymentToken
				$payment_token_data = getBlinkPaymentToken("https://apis.movingwifi.com/blink/post.php", array(
					"process" => "tokenise",
					"merchantID" => $merchantID,
					"tokenType" => "card", 
					"tokenData[cardNumber]" => $cardNo, 
					"tokenData[cardExpiryDate]" => $expiry,
					"tokenData[cardCVV]" => $cvv
					)
				);
				print blink_head($title, "Click to continue", "Payment token response");
				print "<pre>\n";
				print_r($payment_token_data);
				print "</pre>\n";
			}
			else
			{
				print blink_head($title, "Click to continue", "Intent request failed");
				print "<pre>\n";
				print_r($data);
				print "</pre>\n";
			}
		}
		else
		{
			print blink_head($title, "Click to continue", "No cookie found");
		}
	}
	elseif($_REQUEST['operation'] == 'sale-intent')
	{
		if (isset($_COOKIE[$cookie]))
		{
			$token = unserialize($_COOKIE[$cookie]);
			$data = blinkAPIrequest($api_base . "/api/pay/v1/intents", $token->access_token, array(
				"transaction_type" => "SALE",
				"payment_type" => "credit-card",
				"amount" => 10.00, 
				"currency" => "GBP", 
				"return_url" => "https://apis.movingwifi.com/blink/return.php",
				"notification_url" => "https://apis.movingwifi.com/blink/notification.php",
				)
			);
			if ($data['code'] == 201)
			{
				print blink_head($title, "Click to continue", "Intent response");
				print "<pre>\n";
				print_r($data["response"]);
				print "</pre>\n";
			}
			else
			{
				print blink_head($title, "Click to continue", "Request failed");
				print "<pre>\n";
				print_r($data);
				print "</pre>\n";
			}
		}
		else
		{
			print blink_head($title, "Click to continue", "No cookie found");
		}
	}
}
elseif(isset($_COOKIE[$cookie]))
{
	$token = unserialize($_COOKIE[$cookie]);
	if (property_exists($token, 'expired_on'))
	{
		$now = substr(date("c"),0,19); // yyyy-mm-ddThh:mm:ss is 19 chars
		if ($now >  substr($token->expired_on, 0, 19))
		{
			$data = getBlinkAccessToken($urlAccessToken, $api_key, $secret_key, array("enable_moto_payments" => true, "application_name" => "MOT Manager Sandbox", "source_site"=>"apis.movingwifi.com"));
			if ($data['code'] == 201)
			{
				$token = $data['response'];
				setcookie($cookie, serialize($token), strtotime('+6 months'), '/');
			}
		}
		else
		{
			setcookie($cookie, serialize($token), strtotime('+6 months'), '/');
		}
		print blink_head($title, "Home", "Ready for payments");
		print generic_button("Get intent",['operation'=>'sale-intent'], "tertiary", "GET", "./");
		print generic_button("Payment form",['operation'=>'payment-form'], "tertiary", "GET", "./");
		print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
		print footer("Disconnect", "Access expires " . $token->expired_on . "<br>Time now " . $now );
	}
	else
	{
		print blink_head($title, "Click to continue", "Invalid token data");
		print "<pre>\n";
		print_r($token);
		print "</pre>\n";
	}
}
else 
{
    // display get token button
	print blink_head($title);
	print generic_button($connect,['operation'=>'token'], "tertiary", "GET", "./");
}


/**
 * uses cURL to request access token from Blink API
 *
 * @param string $url The destination address
 * @param string $api_key user value
 * @param string $secret_key password value
 * @param array $extra_params extra parameters
 */
function getBlinkAccessToken($url, $api_key, $secret_key, $extra_params = [])
{
	//build the default parameters
	$params = array("api_key" => $api_key, "secret_key" => $secret_key);
	
	// add any extra params
	foreach($extra_params as $key => $value)
	{
		$params[$key] = $value;
	}
	$headers = array(
		"Content-Type: application/json",
		"Accept: application/json",
	);	
    // Set up cURL options.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    // Output the header in the response.
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    // Set the header, response, error and http code.
	$data = [];
	$data['header'] = substr($response, 0, $header_size);
    $data['response'] = json_decode(substr($response, $header_size));
    $data['error'] = $error;
    $data['code'] = $http_code;
	return $data;
}

function blinkAPIrequest($url, $access_token, $params = [])
{
	//build the default parameters
    // Set up cURL options.
	$headers = array(
		"Content-Type: application/json",
		"Accept: application/json",
		"Authorization: Bearer $access_token",
	);	
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    // Output the header in the response.
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
	
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    // Set the header, response, error and http code.
	$data = [];
	$data['header'] = substr($response, 0, $header_size);
    $data['response'] = json_decode(substr($response, $header_size));
    $data['error'] = $error;
    $data['code'] = $http_code;
	return $data;
}

/**
 * Returns HTML for <head>  + start of <body> sections
 *
 * @param string $title Title text
 * @param string $home Display this text on home button, if blank don't include a home button
 * @param string $subtitle Subtitle text
 * @return string
 */
function blink_head($title, $home = "", $subtitle = "Blink API demo")
{
	$html = '<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>' . $title . '</title>
		<link rel="stylesheet" href="/css/mini-default.css">
		<link rel="stylesheet" href="/css/style.css">
	</head>
	';
	$html .= '<body>
	<header class="sticky">
		<div>
			<h3 class="headline">' . $title . '</h3>
			<label for="drawer-control" class="drawer-toggle persistent"></label> 
			<input type="checkbox" id="drawer-control" class="drawer persistent">
			<nav>
				<label for="drawer-control" class="drawer-close"></label>
				<a href="/">Start page</a> 
				<a href="/dropbox/">Dropbox</a>
				<a href="/google/">Google Calendar</a> 
				<a href="/quickbooks/">Quickbooks</a>
				<a href="/twitter/">Twitter</a>
				<a href="/xero/">Xero</a>
				<a href="https://github.com/gerrymulvenna/apis.movingwifi.com">View code on GitHub</a>
			</nav>
		</div>
	</header>
	<div class="container">';
	if (!empty($home))
	{
		$html .= '
		<div class="card large">
			<a id="home" class="button primary" href="./">' . $home . '</a>
			<p>' . $subtitle . '</p>
		</div>';
	}
	return $html;
}


function payment_form ()
{
	$html = '<form id="BlinkForm" method="post" action="./">
		<input type="hidden" id="operation" name="operation" value="payment">
		<table class="table" style="width:fit-content;">
			<tbody>
				<tr>
					<td><label for="BlinkAmount" style="width:100%;padding:10px;background-color:#E0E0E0;text-align:right;">AMOUNT</label></td>
					<td><input type="text" style="padding:10px;" id="BlinkAmount" name="BlinkAmount" value="" placeholder="AMOUNT TO PAY" required></td>
				</tr>
				<tr>
					<td><label for="BlinkCardNo" style="width:100%;padding:10px;background-color:#E0E0E0;text-align:right;">CARD NO</label></td>
					<td><input type="text" style="padding:10px;" id="BlinkCardNo" name="BlinkCardNo" value="" placeholder="16 DIGIT CARD NUMBER" required></td>
				</tr>
				<tr>
					<td><label for="BlinkExpiry" style="width:100%;padding:10px;background-color:#E0E0E0;text-align:right;">EXPIRY</label></td>
					<td>
						<input type="text" style="width:5rem;padding:10px;" id="BlinkExpiry" name="BlinkExpiry" value="" placeholder="MM/YY" maxlength="5" required>
					</td>
				</tr>
				<tr>
					<td><label for="BlinkCVV" style="width:100%;padding:10px;background-color:#E0E0E0;text-align:right;">CVC NO</label></td>
					<td><input type="text" style="padding:10px;" id="BlinkCVV" name="BlinkCVV" value="" placeholder="3 DIGITS FROM BACK" inputmode="numeric" maxlength="3" required></td>
				</tr>
			</tbody>
		</table>
		<div>
			<h4 style="color:red;">Please note:</h4>
			<ul>
				<li>We never store credit card numbers.</li>
				<li>Your card number is only used to process <strong>this</strong> payment.</li>
			</ul>
		</div>
		<div>
			<input type="submit" name="Submit" class="button tertiary" style="padding:3px;margin-left:10px;" value="Make payment">
		</div>
	</form>';
	return $html;
}


function getBlinkPaymentToken($url, $params)
{
	$headers = array("Content-Type: multipart/form");	
    // Set up cURL options.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$eh = fopen('curl.log', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $eh);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    // Output the header in the response.
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    // Set the header, response, error and http code.
	$data = [];
	$data['header'] = substr($response, 0, $header_size);
    $data['response'] = substr($response, $header_size);
    $data['error'] = $error;
    $data['code'] = $http_code;
	return $data;
}
?>