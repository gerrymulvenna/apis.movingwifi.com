<?php
// a simple Twitter API example using PHP
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
		$cdata = unserialize($_COOKIE[$cookie]);
		print head("$title | cookie contents", "Home");
		print '<pre>';
		print_r($cdata);
		print '</pre>';
		print footer("Disconnect", "");
	}
	elseif($_REQUEST['operation'] == 'revoke')
	{
		setcookie($cookie,"", time() - 3600, "/");  //delete cookie
		print head($title, "Disconnected");
	}
	elseif($_REQUEST['operation'] == 'token')
	{
		$response = getBlinkAccessToken($urlAccessToken, $api_key, $secret_key);
		print head($title, "Token response");
		print_r($response);
	}
}
// If we don't have an authorization code then get one
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
	$params = [];
	$params['api_key'] = $api_key;
	$params['secret_key'] = $secret_key;
	
	// add any extra params
	foreach($extra_params as $key => $value)
	{
		$params[$key] = $value;
	}
    // Set up cURL options.
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$eh = fopen('curl.log', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $eh);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, "MOVINGWIFI_PHP/1.0");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type' => 'application/json', 'Accept' => 'application/json']);
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