<?php
error_reporting(-1);
session_start();
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
require "credentials.php";  //api_key, client_id, client_secret, redirect_uri

// Google API details
$urlAuthorize = 'https://accounts.google.com/o/oauth2/v2/auth';
$urlAccessToken = 'https://oauth2.googleapis.com/token';
$urlResourceOwnerDetails = 'https://openidconnect.googleapis.com/v1/userinfo';
$scopes = ['openid','email','profile','https://www.googleapis.com/auth/calendar.events.public.readonly','https://www.googleapis.com/auth/calendar.events.owned.readonly','https://www.googleapis.com/auth/calendar.events.readonly'];

$title = "Google Calendar API Test";

if (isset($_GET['state']) && isset($_SESSION['oauth2state']))
{
	if ($_GET['state'] == $_SESSION['oauth2state'])
	{
		$response = basicAuthRequest($urlAccessToken, "authorization_code", $_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			print head($title, "Connected");
			$token->access_token_expiry = time() + $token->expires_in;
			$_SESSION['movingwifi-gCal'] = serialize($token);
		}
		else
		{
			print head($title, "Error - not connected");
			print '<pre>';
			print_r($response);
			print '</pre>';
		}
	}
	else
	{
		unset($_SESSION['oauth2state']); 

		print head($title, "Error - invalid state");
		print '<pre>';
		print_r($_REQUEST);
		print_r($_SESSION);
		print '</pre>';
	}
}

// If we have a cookie, get the connection details
elseif (isset($_SESSION['movingwifi-gCal']))
{
	if (isset($_REQUEST['operation']))
	{
		if($_REQUEST['operation'] == 'cookie')
		{
			$token = unserialize($_SESSION['movingwifi-gCal']);
			print head($title, "Cookie");
			print '<pre>';
			print_r($token);
			print '</pre>';
		}
		elseif($_REQUEST['operation'] == 'revoke')
		{
			print head($title, "Disconnected");
			unset($_SESSION['movingwifi-gCal']);
		}
	}
	else
	{
		$now = time();
		$token = unserialize($_SESSION['movingwifi-gCal']);
		if ($now <  $token->access_token_expiry)
		{
			print head($title, "Owner details");
			print generic_button("cookie", "Display cooke",['operation'=>'cookie'], "tertiary", "GET", "./");
			
			$data = apiRequest($urlResourceOwnerDetails, $token->access_token);
			print '<pre>';
			print_r($data);
			print '</pre>';
		}
		else
		{
			print head($title, "Revoked");
			unset($_SESSION['oauth2state']); 
			unset($_SESSION['movingwifi-gCal']);
		}
		print footer("Revoke");
	}
}
// If we don't have an authorization code then get one
elseif (!isset($_GET['code'])) {
	$state = getRandomState();

    // store state in the session.
    $_SESSION['oauth2state'] = $state;

    // display Connect to Google button
	print head($title);
	print generic_button("google", "Connect to Google",['client_id'=>$client_id,
	                                                    'response_type'=>'code',
														'redirect_uri'=>$redirect_uri,
														'scope'=>implode(' ', $scopes)
														,'state'=>$state], "tertiary", "GET", $urlAuthorize);
}
?>