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
$title = "Blink payment sandbox";
$cookie = "movingwifi-blink";

if (isset($_REQUEST['operation']))
{
	if($_REQUEST['operation'] == 'cookie')
	{
		if (isset($_COOKIE[$cookie]))
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			print head("$title | cookie contents", "Home", "Cookie contents");
			print '<pre>';
			print_r($cdata);
			print '</pre>';
			print footer("Disconnect", "");
		}
		else
		{
			print head("$title", "Click to continue", "No cookie found");
		}
	}
	elseif($_REQUEST['operation'] == 'revoke')
	{
		setcookie($cookie,"", time() - 3600, "/");  //delete cookie
		print head($title, "Disconnected", "Token revoked");
	}
	elseif($_REQUEST['operation'] == 'token')
	{
		$data = getBlinkAccessToken($urlAccessToken, $api_key, $secret_key, 
			array(
				"enable_moto_payments" => true, 
				"application_name" => "MOVINGWIFI Sandbox", 
				"application_description" => "Gerry Mulvenna running some initial tests in PHP", 
				"source_site"=>"apis.movingwifi.com"
			)
		);
		if ($data['code'] == 201)
		{
			$token = $data['response'];
			setcookie($cookie, serialize($token), strtotime('+6 months'), '/');
			print head($title, "Connected - click to continue", "Token acquired");
		}
		else
		{
			setcookie($cookie,"", time() - 3600, "/");  //delete cookie
			print head($title, "Click to continue", "Request failed");
			print "<pre>\n";
			print_r($data);
			print "</pre>\n";
		}	
	}
	elseif($_REQUEST['operation'] == 'moto-payment-form')
	{
		print head($title, "Home", "Test MOTO payment");
		print payment_form("moto-payment");
	}
	elseif($_REQUEST['operation'] == 'moto-payment')
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
				$payment_intent = $intent_data["response"]->payment_intent;
				$transaction_unique = $intent_data["response"]->transaction_unique;
				// 2. get paymentToken
				$payment_token_data = getBlinkPaymentToken($urlPaymentToken, array(
					"process" => "tokenise",
					"merchantID" => $merchantID,
					"tokenType" => "card", 
					"tokenData[cardNumber]" => $cardNo, 
					"tokenData[cardExpiryDate]" => $expiry,
					"tokenData[cardCVV]" => $cvv
					)
				);
				if ($payment_token_data["code"] == 200)
				{
					$pdata = json_decode($payment_token_data["response"]);
					if (property_exists($pdata, "paymentToken"))
					{
						// 3. submit payment
						$ref = getRandomState(16);
						$order = getRandomState(8);
						$merchant_data = (object) array("reference" => $ref, "order_id" => $order);
						$payment_response = blinkAPIrequest($api_base . "/api/pay/v1/creditcards", $token->access_token, array(
							"payment_intent" => $payment_intent,
							"paymentToken" => $pdata->paymentToken,
							"type" => 2, 
							"customer_email" => "jobloggs@gmail.com", 
							"customer_name" => "Jo Bloggs",
							"customer_address" => "7 Merevale Avenue, Leicester",
							"customer_postcode" => "LE10 2BU",
							"merchant_data" => json_encode($merchant_data),
							"transaction_unique" => $transaction_unique
						));
						if ($payment_response["code"] == 200)
						{
							$paydata = $payment_response["response"];
							if (property_exists($paydata, "url"))
							{
								header('Location: ' . $paydata->url);
							}
							else
							{
								print head($title, "Click to continue", "No url in payment response");
								print "<pre>\n";
								print_r($paydata);
								print "</pre>\n";
							}
						}
						else
						{
							print head($title, "Click to continue", "Payment request failed");
							print "<pre>\n";
							print_r($payment_response);
							print "</pre>\n";
						}
					}
					else
					{
						print head($title, "Click to continue", "No paymentToken");
						print "<pre>\n";
						print_r($pdata);
						print "</pre>\n";
					}
				}
				else
				{
					print head($title, "Click to continue", "Payment token request failed");
					print "<pre>\n";
					print_r($payment_token_data);
					print "</pre>\n";
				}
			}
			else
			{
				print head($title, "Click to continue", "Intent request failed");
				print "<pre>\n";
				print_r($intent_data);
				print "</pre>\n";
			}
		}
		else
		{
			print head($title, "Click to continue", "No cookie found");
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
		print head($title, "Home", "Ready for payments");
		print generic_button("Test MOTO payment",['operation'=>'moto-payment-form'], "tertiary", "GET", "./");
		print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
		print footer("Disconnect", "Access expires " . $token->expired_on . "<br>Time now " . $now );
	}
	else
	{
		print head($title, "Click to continue", "Invalid token data");
		print "<pre>\n";
		print_r($token);
		print "</pre>\n";
	}
}
else 
{
    // display get token button
	print head($title, "Home", "Blink access token required");
	print generic_button("Get Blink access token",['operation'=>'token'], "tertiary", "GET", "./");
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

function payment_form ($operation)
{
	$html = '<form id="BlinkForm" method="post" action="./">
		<input type="hidden" id="operation" name="operation" value="' . $operation. '">
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
	$headers = array(
		"Content-Type: multipart/form-data",
		"Accept: application/json"
	);	
    // Set up cURL options.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
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