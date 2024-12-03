<?php
// a simple Facebook API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php file and define your unique credentials for this service
require "credentials.php";  //$app_id, $app_secret, $redirect_uri, $app_token, $page_id

// API details
$urlAuthorize = 'https://www.facebook.com/v21.0/dialog/oauth';
$urlAccessToken = 'https://graph.facebook.com/v21.0/oauth/access_token';
$urlDebugToken = 'https://graph.facebook.com/debug_token';
$urlAccounts = 'https://graph.facebook.com/me/accounts';
$api_base = 'https://graph.facebook.com/v21.0/';

// service-specific strings
$title = "Facebook";
$connect = "Facebook Login";
$cookie = "movingwifi-facebook";

if (isset($_GET['state']) && isset($_COOKIE['oauth2state']) && isset($_GET['code']))
{
	if ($_GET['state'] == $_COOKIE['oauth2state'])
	{
		$response = accessTokenRequest($_REQUEST['code'], $app_id, $app_secret, $redirect_uri);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			$cdata['access_token'] = $token->access_token;
			// debug token
			$debug = debugToken($token->access_token, $app_token);
			if ($debug['code'] == 200)
			{
				// just grab what we need to keep cookie small
				$cdata['user_id'] = $debug['response']->data->user_id;
				$cdata['debug'] = $debug['response']->data;
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+60 days'), '/');
				print head($title, "Connected - click to continue", $cdata['user_id']);
				print footer("Disconnect", "");
			}
			else
			{
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+60 days'), '/');
				print head($title, "Connected - click to continue", "but failed to debug token");
				print footer("Disconnect", "");
			}
		}
		else
		{
			print head($title, "Error - not connected");
			print '<pre>';
			print json_encode($response);
			print '</pre>';
		}
	}
	else
	{
		setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
		setcookie('challenge',"", time() - 3600, "/");  //delete cookie

		print head($title, "Error - invalid state");
		print '<pre>';
		print_r($_GET);
		print_r($_COOKIE);
		print '</pre>';
	}
}

// If we have a cookie, get the connection details
elseif (isset($_COOKIE[$cookie]))
{
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
		elseif($_REQUEST['operation'] == 'accounts')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			$response = listAccounts($cdata['access_token']);
			// note response code of 200 for successfully api call
			if ($response['code'] == 200)
			{
				print head("$title | List account access", "Home", $cdata['user_id']);
				print '<pre>';
				print json_encode($response['response']);
				print '</pre>';
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | request unsuccessful", "Home", $cdata['user_id']);
				print '<pre>';
				print json_encode($response);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
		elseif($_REQUEST['operation'] == 'post')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			$url = $api_base . $page_id . "/feed";
			$response = apiRequest($url, $page_access_token,'POST',['message'=>$_REQUEST['text'],'published'=>true]);
			// note response code of 200 for successfully created post
			if ($response['code'] == 200)
			{
				print head("$title | Post success", "Home", $cdata['user_id']);
				print '<pre>';
				print json_encode($response['response']);
				print '</pre>';
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | Post unsuccessful", "Home", $cdata['user_id']);
				print '<pre>';
				print json_encode($response);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
		elseif($_REQUEST['operation'] == 'photo')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			$url = $api_base . $page_id . "/photos";
			$response = apiRequest($url, $page_access_token,'POST',[
				'url'=>$_REQUEST['url'],
				'published'=>false,
				'temporary'=>true
			]);
			// note response code of 200 for successfully created upload of a photo
			if ($response['code'] == 200)
			{
				$id = $response['response']->id;
				$url = $api_base . $page_id . "/feed";
				$data = apiRequest($url, $page_access_token,'POST',array(
					'message'=>"Our schedule for today on Indy Live Radio.",
					'published'=>true,
					'attached_media'=>array(
						array('media_fbid'=>$id)
					)
				));
				// note response code of 200 for successfully created post
				if ($data['code'] == 200)
				{
					print head("$title | Photo post success", "Home", $cdata['user_id']);
					print '<pre>';
					print json_encode($data['response']);
					print '</pre>';
					print footer("Disconnect", "");
				}
				else
				{
					print head("$title | Photo post unsuccessful", "Home", $cdata['user_id']);
					print '<pre>';
					print json_encode($data);
					print '</pre>';
					print footer("Disconnect", "");
				}
			}
			else
			{
				print head("$title | Photo upload unsuccessful", "Home", $cdata['user_id']);
				print '<pre>';
				print json_encode($response);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
	}
	else
	{
		$cdata = unserialize($_COOKIE[$cookie]);
		print head($title, "Home", $cdata['user_id']);
		print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
		print generic_button("List account access",['operation'=>'accounts'], "tertiary", "GET", "./");
		if (isset($page_access_token))
		{
			print post_button("Submit post to page",['operation'=>'post'], "text", "Enter your post text here");
			print post_button("Upload image from URL",['operation'=>'photo'], "url", "Enter your image URL here");
		}
		print footer("Disconnect", "");
	}
}
// display any error message
elseif (isset($_GET['error_code']))
{
	$error_code = $_GET['error_code'];
	$error_msg = $_GET['error_message'];
	print head($title, "Error $error_code - click to continue", $error_msg);
}
// If we don't have an authorization code then get one
elseif (!isset($_GET['code'])) 
{
	if (isset($_COOKIE['oauth2state']))
	{
		$state = $_COOKIE['oauth2state'];
	}
	else
	{
		$state = getRandomState();
	}
    // store state in the session.
	setcookie('oauth2state', $state, time() + 600, '/');

    // display Connect to button
	print head($title);
	print generic_button($connect,[
		'client_id'=>$app_id,
		'redirect_uri'=>$redirect_uri,
		'state'=>$state
	], "tertiary", "GET", $urlAuthorize);
}

function accessTokenRequest($code, $client_id, $client_secret, $callback)
{
	global $urlAccessToken;
	
	//build the default parameters
	$params = array('code'=> $code, 'redirect_uri' => $callback, 'client_id'=>$client_id, 'client_secret'=>$client_secret);
    // Set up cURL options.
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$eh = fopen('curl.log', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $eh);
	$url = $urlAccessToken . "?" . http_build_query($params);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, "MOVINGWIFI_PHP/1.0");
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
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

function debugToken($input_token, $app_token)
{
	global $urlDebugToken;
	
	//build the default parameters
	$params = array('input_token'=> $input_token, 'access_token' => $app_token);
    // Set up cURL options.
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$eh = fopen('curl.log', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $eh);
	$url = $urlDebugToken . "?" . http_build_query($params);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, "MOVINGWIFI_PHP/1.0");
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
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

function listAccounts($access_token)
{
	global $urlAccounts;
	
	//build the default parameters
	$params = array('access_token' => $access_token);
    // Set up cURL options.
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$eh = fopen('curl.log', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $eh);
	$url = $urlAccounts . "?" . http_build_query($params);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, "MOVINGWIFI_PHP/1.0");
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
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