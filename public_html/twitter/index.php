<?php
// a simple Twitter API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php file and define your unique credentials for this service
require "credentials.php";  //$client_id, $client_secret, $redirect_uri

// API details
$urlAuthorize = 'https://twitter.com/i/oauth2/authorize';
$urlAccessToken = 'https://api.twitter.com/2/oauth2/token';
$urlResourceOwnerDetails = 'https://openidconnect.googleapis.com/v1/userinfo';
$api_base = 'https://api.twitter.com';

$scopes =  ['tweet.read','tweet.write','users.read offline.access'];

// service-specific strings
$title = "Twitter";
$connect = "Connect to Twitter";
$cookie = "movingwifi-twitter";

if (isset($_GET['state']) && isset($_COOKIE['oauth2state']) && isset($_COOKIE['challenge']))
{
	if ($_GET['state'] == $_COOKIE['oauth2state'])
	{
		$verifier = $_COOKIE['challenge'];
		$response = basicAuthRequest($urlAccessToken, "authorization_code", $_REQUEST['code'], $client_id, $client_secret, $redirect_uri, ['code_verifier'=>$verifier]);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			$cdata['access_token_expiry'] = time() + $token->expires_in;
			$cdata['token'] = $token;
			// get user info
			$url = $api_base . "/2/users/me";
			$user_data = apiRequest($url, $token->access_token,'GET',['user.fields'=>'created_at,profile_image_url,description,location,entities,url,public_metrics']);
			if ($user_data['code'] == 200)
			{
				$cdata['user'] = $user_data['response']->data;
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie('challenge',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected - click to continue", $cdata['user']->name);
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
			$response = apiRequest($url, $token->access_token,'POST',['text'=>$_REQUEST['data']]);
			if ($response['code'] == 200)
			{
				print head($title, "Post Tweet - response", $cdata['user']->name);
				print '<pre>';
				print_r($response['response']);
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
			$response = basicRefreshRequest($urlAccessToken, "refresh_token", $cdata['token']->refresh_token, $client_id, $client_secret);
			if ($response['code'] == 200)
			{
				$token = $response['response'];
				$cdata['access_token_expiry'] = time() + $token->expires_in;
				$cdata['token'] = $token;
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Refreshed", $cdata['user']->name);
				print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
				print post_button("Post Tweet",['operation'=>'tweet']);
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
			print head($title, "Home", $cdata['user']->name);
			print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
			print post_button("Post Tweet",['operation'=>'tweet']);
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
	print generic_button($connect,['client_id'=>$client_id,
	                                                    'response_type'=>'code',
														'redirect_uri'=>$redirect_uri,
														'scope'=>implode(' ', $scopes),
														'state'=>$state,
														'code_challenge'=>$pkce,
														'code_challenge_method'=>'plain'], "tertiary", "GET", $urlAuthorize);
}



?>