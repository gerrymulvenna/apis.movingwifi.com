<?php
// a simple Dropbox API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php file and define your unique credentials for this service
require "credentials.php";  //$client_id, $client_secret, $redirect_uri

// API details
$urlAuthorize = 'https://dropbox.com/oauth2/authorize';
$urlAccessToken = 'https://api.dropbox.com/oauth2/token';
$api_base = 'https://api.dropboxapi.com';

$scopes =  ['openid','profile','email','files.metadata.read','files.content.read','account_info.read'];

// service-specific strings
$title = "Dropbox";
$connect = "Connect to Dropbox";
$cookie = "movingwifi-dropbox";

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
			setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
			setcookie('challenge',"", time() - 3600, "/");  //delete cookie
			setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
			print head($title, "Connected - click to continue");
			print footer("Disconnect", "");
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
		elseif($_REQUEST['operation'] == 'folders')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			$url = "$api_base/2/files/list_folder";
			$path = $_REQUEST['path'];
			$data = apiRequest($url, $cdata['token']->access_token, 'POST', ["recursive"=>false,"path"=>$path]);
			if ($data['code'] == 200)
			{
				print head("$title | folders", "Home");
				$table = folders_summary($data['response']->entries);
				print table_folders_html($table);
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | Error - folders", "Home");
				print '<pre>';
				print_r($data);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
		elseif($_REQUEST['operation'] == 'user')
		{
			// get user info
			$cdata = unserialize($_COOKIE[$cookie]);
			$url = $api_base . "/2/openid/userinfo";
			$response = apiRequest($url, $cdata['token']->access_token);
			if ($response['code'] == 200)
			{
				print head("$title | user info", "Home");
				print '<pre>';
				print_r($response['response']);
				print '</pre>';
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | user info unsuccessful", "Home");
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
			$response = basicRefreshRequest($urlAccessToken, "refresh_token", $cdata['token']->refresh_token, $client_id, $client_secret);
			if ($response['code'] == 200)
			{
				$token = $response['response'];
				$cdata['access_token_expiry'] = time() + $token->expires_in;
				$cdata['token'] = $token;
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Refreshed");
				print generic_button("Display cookie",['operation'=>'cookie']);
				print generic_button("Get user info",['operation'=>'user']);
				print generic_button("List folders",['operation'=>'folders','path'=>'']);
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
			print head($title, "Home");
			print generic_button("Display cookie",['operation'=>'cookie']);
			print generic_button("Get user info",['operation'=>'user']);
			print generic_button("List folders",['operation'=>'folders','path'=>'']);
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
		$pkce = getRandomPkceCode(64);
	}

    // store state in the session.
	setcookie('oauth2state', $state, time() + 600, '/');
	setcookie('challenge', $pkce, time() + 600, '/');

    // display Connect to button
	print head($title);
	print generic_button($connect,['client_id'=>$client_id,
	                                                    'response_type'=>'code',
														'redirect_uri'=>$redirect_uri,
														'token_access_type'=>'offline',
														'scope'=>implode(' ', $scopes),
														'state'=>$state,
														'code_challenge'=>$pkce,
														'code_challenge_method'=>'plain'], "tertiary", "GET", $urlAuthorize);
}

function folders_summary($entries)
{
	foreach ($entries as $entry)
	{
		if (property_exists($entry, 'path_lower'))
		{
			$key = $entry->path_lower;
			$items[$key][] = $key;
			$items[$key][] =(property_exists($entry, 'name')) ? $entry->name : "";
			$items[$key][] =(property_exists($entry, 'id')) ? $entry->id : "";
			$items[$key][] =(property_exists($entry, '.tag')) ? $entry->{'.tag'} : "";
		}
	}
	// field names in first row
	$table[0] = ['path_lower','name','id','.tag'];
	// append the table values sorted by path_lower
	ksort($items);
	return (array_merge($table, array_values($items)));
}

function table_folders_html($data)
{
	$html = "<table class=\"tight\"><thead><tr>\n";
	$headings = array_shift($data);
	foreach ($headings as $fieldname)
	{
		$html .= "<th data-label=\"$fieldname\">$fieldname</th>\n";
	}
	$html .= "</tr></thead><tbody>\n";
	
	foreach ($data as $row)
	{
		$i = 0;
		$html .= "<tr>\n";
		foreach ($row as $cell)
		{
			$label = $headings[$i++];
			if ($label == 'path_lower')
			{
				// this relies on knowing that the fourth element is .tag, i.e. file or folder
				if ($row[3] == 'folder')
				{
					$html .= "<td data-label=\"$label\"><a href=\"./?operation=folders&path=$cell\">$cell</a></td>\n";
				}
				else
				{
					$html .= "<td data-label=\"$label\">$cell</td>\n";
				}
			}
			else
			{
				$html .= "<td data-label=\"$label\">$cell</td>\n";
			}
		}
		$html .= "</tr>\n";
	}
	$html .= "</tbody></table>\n";
	return $html;
}
	
?>