<?php
// a simple Facebook API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php file and define your unique credentials for this service
require "credentials.php";  //$app_id, $app_secret, $redirect_uri, $page_id

// API details
$urlAuthorize = 'https://www.facebook.com/v21.0/dialog/oauth';
$api_base = 'https://graph.facebook.com/v21.0/';

// service-specific strings
$title = "Facebook";
$connect = "Facebook Login";
$cookie = "movingwifi-facebook";

if (isset($_GET['state']) && isset($_COOKIE['oauth2state']))
{
	print_r($_REQUEST);
	exit(0);
	if ($_GET['state'] == $_COOKIE['oauth2state'])
	{
		$response = basicAuthRequest($urlAccessToken, "authorization_code", $_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			$cdata['access_token_expiry'] = time() + $token->expires_in;
			$cdata['access_token'] = $token->access_token;
			$cdata['refresh_token'] = $token->refresh_token;
			// get user info
			$url = $api_base . "/2/users/me";
			$user_data = apiRequest($url, $token->access_token,'GET',['user.fields'=>'created_at,profile_image_url,description,location,entities,url,public_metrics']);
			if ($user_data['code'] == 200)
			{
				// just grab what we need to keep cookie small
				$cdata['name'] = $user_data['response']->data->name;
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie('challenge',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected - click to continue", $cdata['name']);
				print footer("Disconnect", "");
			}
			else
			{
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie('challenge',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected - click to continue", "but failed to retrieve user info");
				print footer("Disconnect", "");
			}
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
		elseif($_REQUEST['operation'] == 'tweet')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			$url = $api_base . "/2/tweets";
			$response = apiRequest($url, $cdata['access_token'],'POST',['text'=>$_REQUEST['text']]);
			// note response code of 201 for successfully created tweet
			if ($response['code'] == 201)
			{
				print head("$title | Tweet success", "Home", $cdata['name']);
				print '<pre>';
				print_r($response['response']);
				print '</pre>';
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | Tweet unsuccessful", "Home", $cdata['name']);
				print '<pre>';
				print_r($response);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
	}
	else
	{
		$now = time();
		$cdata = unserialize($_COOKIE[$cookie]);
		if ($now >  $cdata['access_token_expiry'])
		{
			$response = basicRefreshRequest($urlAccessToken, "refresh_token", $cdata['refresh_token'], $client_id, $client_secret);
			if ($response['code'] == 200)
			{
				$token = $response['response'];
				$cdata['access_token_expiry'] = time() + $token->expires_in;
				$cdata['access_token'] = $token->access_token;
				$cdata['refresh_token'] = $token->refresh_token;
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Refreshed", $cdata['name']);
				print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
				print post_button("Post Tweet",['operation'=>'tweet'], "text", "Enter your tweet here");
			}
			else
			{
				setcookie($cookie,"", time() - 3600, "/");  //delete cookie
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie('challenge',"", time() - 3600, "/");  //delete cookie
				print head($title, "Refresh failed - click to continue");
			}	
		}
		else
		{
			print head($title, "Home", $cdata['name']);
			print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
			print post_button("Post Tweet",['operation'=>'tweet'], "text", "Enter your tweet here");
		}
		print footer("Disconnect", "");
	}
}
// If we don't have an authorization code then get one
elseif (!isset($_GET['code'])) {
	if (isset($_COOKIE['oauth2state']))
	{
		$state = $_COOKIE['oauth2state'];
	}
	else
	{
		$state = getRandomState();
	}
	if (isset($_COOKIE['challenge']))
	{
		$pkce = $_COOKIE['challenge'];
	}
	else
	{
		$pkce = getRandomPkceCode(25);
	}

    // store state in the session.
	setcookie('oauth2state', $state, time() + 600, '/');
	setcookie('challenge', $pkce, time() + 600, '/');

    // display Connect to button
	print head($title);
	print generic_button($connect,[
		'client_id'=>$app_id,
		'redirect_uri'=>$redirect_uri,
		'state'=>$state
	], "tertiary", "GET", $urlAuthorize);
}



?>