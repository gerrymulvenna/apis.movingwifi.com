<?php
// a simple Quickbooks API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php and define your unique credentials for this service
require "credentials.php";  //$client_id, $client_secret, $redirect_uri

// API details
$urlAuthorize = "https://appcenter.intuit.com/connect/oauth2";
$urlAccessToken = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer";
$sandbox_base = "https://sandbox-quickbooks.api.intuit.com";
$scopes = ['com.intuit.quickbooks.accounting'];

// service-specific strings
$title = "Quickbooks";
$connect = "Connect to Quickbooks";
$cookie = "movingwifi-Quickbooks";

if (isset($_GET['state']) && isset($_COOKIE['oauth2state']) && isset($_GET['realmId']))
{
	if ($_GET['state'] == $_COOKIE['oauth2state'])
	{
		$response = basicAuthRequest($urlAccessToken, "authorization_code", $_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			$cdata['access_token_expiry'] = time() + $token->expires_in;
			$cdata['refresh_token_expiry'] = time() + $token->x_refresh_token_expires_in;
			$cdata['realmId'] = $_GET['realmId'];
			$cdata['access_token'] = $token->access_token;
			$cdata['refresh_token'] = $token->refresh_token;
			// get company info
			$url = $sandbox_base . "/v3/company/" . $cdata['realmId'] . "/companyinfo/" . $cdata['realmId'];
			$data = apiRequest($url, $token->access_token);
			if ($data['code'] == 200)
			{
				$cdata['CompanyName'] = $data['response']->CompanyInfo->CompanyName;
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected - click to continue", $cdata['CompanyName']);
				print footer("Disconnect", "");
			}
			else
			{
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected", "but failed to retrieve company info");
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

		print head($title, "Error - invalid state");
		print '<pre>';
		print_r($_GET);
		print_r($_COOKIE);
		print '</pre>';
	}
}

// If we have a session cookie, save it as a persistent cookie and then process any operation or show authenticated options
elseif (isset($_COOKIE[$cookie]))
{
	if (isset($_REQUEST['operation']))
	{
		if($_REQUEST['operation'] == 'cookie')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			print head("$title | cookie contents", "Home", $cdata['CompanyName']);
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
		elseif($_REQUEST['operation'] == 'invoices')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			$query = http_build_query(['query'=>"SELECT * from Invoice order by txndate desc"]);
			$url = "$sandbox_base/v3/company/" . $cdata['realmId'] . "/query?$query&minorversion=70";

			$data = apiRequest($url, $cdata['access_token']);
			if ($data['code'] == 200)
			{
				print head("$title | invoices", "Home", $cdata['CompanyName']);
				$table = invoice_summary($data['response']->QueryResponse);
				print table_html($table);
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | invoices", "Error - query invoice", $cdata['CompanyName']);
				print '<pre>';
				print_r($data);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
		elseif($_REQUEST['operation'] == 'company')
		{
			$cdata = unserialize($_COOKIE[$cookie]);
			$url = $sandbox_base . "/v3/company/" . $cdata['realmId'] . "/companyinfo/" . $cdata['realmId'];
			$data = apiRequest($url, $cdata['access_token']);
			if ($data['code'] == 200)
			{
				print head("$title | company", "Home", $cdata['CompanyName']);
				print '<pre>';
				print_r($data['response']);
				print '</pre>';
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | company", "Error - query invoice", $cdata['CompanyName']);
				print '<pre>';
				print_r($data);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
	}
	else
	{
		$now = time();
		$cdata = unserialize($_COOKIE[$cookie]);
		if ($now > $cdata['access_token_expiry'])
		{
			$response = basicRefreshRequest($urlAccessToken, "refresh_token", $cdata['refresh_token'], $client_id, $client_secret);
			if ($response['code'] == 200)
			{
				$token = $response['response'];
				$cdata['access_token_expiry'] = time() + $token->expires_in;
				$cdata['refresh_token_expiry'] = time() + $token->x_refresh_token_expires_in;
				$cdata['access_token'] = $token->access_token;
				$cdata['refresh_token'] = $token->refresh_token;
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Refreshed", $cdata['CompanyName']);
				print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
				print generic_button("Display invoices",['operation'=>'invoices'], "tertiary", "GET", "./");
				print generic_button("Display company info",['operation'=>'company'], "tertiary", "GET", "./");
			}
			else
			{
				setcookie($cookie,"", time() - 3600, "/");  //delete cookie
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				print head($title, "Refresh failed - click to continue", $cdata['CompanyName']);
			}
		}
		else
		{
			print head($title, "Home", $cdata['CompanyName']);
			print generic_button("Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
			print generic_button("Display invoices",['operation'=>'invoices'], "tertiary", "GET", "./");
			print generic_button("Display company info",['operation'=>'company'], "tertiary", "GET", "./");
		}
		print footer("Disconnect", "");
	}
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
    // store state in cookie
	setcookie('oauth2state', $state, time() + 600, '/');

    // display Connect to button
	print head($title);
	print generic_button($connect,['client_id'=>$client_id,
	                                                    'response_type'=>'code',
														'redirect_uri'=>$redirect_uri,
														'scope'=>implode(' ', $scopes)
														,'state'=>$state], "tertiary", "GET", $urlAuthorize);
}

function invoice_summary($response)
{
	$i = 0;
	// field names in first row
	$table[$i] = ['Id','TxnDate','DocNumber','TotalAmt','Balance','TotalTax','Customer','Currency'];
	foreach ($response->Invoice as $invoice)
	{
		$i++;
		$table[$i][] =(property_exists($invoice, 'Id')) ? $invoice->Id : "";
		$table[$i][] =(property_exists($invoice, 'TxnDate')) ? $invoice->TxnDate : "";
		$table[$i][] =(property_exists($invoice, 'DocNumber')) ? $invoice->DocNumber : "";
		$table[$i][] =(property_exists($invoice, 'TotalAmt')) ? $invoice->TotalAmt : "";
		$table[$i][] =(property_exists($invoice, 'Balance')) ? $invoice->Balance : "";
		$table[$i][] =(property_exists($invoice->TxnTaxDetail, 'TotalTax')) ? $invoice->TxnTaxDetail->TotalTax : "";
		$table[$i][] =(property_exists($invoice->CustomerRef, 'name')) ? $invoice->CustomerRef->name : "";
		$table[$i][] =(property_exists($invoice->CurrencyRef, 'value')) ? $invoice->CurrencyRef->value : "";
	}
	return $table;
}	
?>