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
		print head($title, "Home", "Token response");
		print "<pre>\n";
		print_r($response);
		print "</pre>\n";
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
	$params = array("api_key" => $api_key, "secret_key" => $secret_key);
	
	// add any extra params
	foreach($extra_params as $key => $value)
	{
		$params[$key] = $value;
	}
	
    // Set up cURL options.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type' => 'application/json', 'Accept' => 'application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_POST, true);
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


// OPTIONS:   curl_setopt($curl, CURLOPT_URL, $url);   curl_setopt($curl, CURLOPT_HTTPHEADER, array(      'APIKEY: 111111111111111111111',      'Content-Type: application/json',   ));   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);   // EXECUTE:   $result = curl_exec($curl);   if(!$result){die("Connection Failure");}   curl_close($curl);   return $result;
function callAPI($method, $url, $data)
{
	$curl = curl_init();
	switch (strtoupper($method))
	{      
		case "POST":
			curl_setopt($curl, CURLOPT_POST, 1);
			if ($data)
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			break;
		case "PUT":
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
			if ($data)
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			break;
		default:
			if ($data)
				$url = sprintf("%s?%s", $url, http_build_query($data));
	}
}
?>