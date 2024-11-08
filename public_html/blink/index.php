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
		$data = getBlinkAccessToken($urlAccessToken, $api_key, $secret_key, array("enable_moto_payments" => true, "application_name" => "MOT Manager Sandbox", "source_site"=>"apis.movingwifi.com"));
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
		print generic_button("Get SALE intent",['operation'=>'sale-intent'], "tertiary", "GET", "./");
		print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
		print footer("Disconnect", "Access expires on " . $token->expired_on . " vs " . $now);
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
	print head($title);
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
?>