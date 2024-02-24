<?php
// a simple Quickbooks API example using PHP
error_reporting(-1);
session_start();
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
require "credentials.php";  //client_id, client_secret, redirect_uri

// API details
$urlAuthorize = "https://appcenter.intuit.com/connect/oauth2";
$urlAccessToken = "https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer";
$sandbox_base = "https://sandbox-quickbooks.api.intuit.com";
$scopes = ['com.intuit.quickbooks.accounting'];

// service-specific strings
$title = "Quickbooks";
$connect = "Connect to Quickbooks";
$cookie = "movingwifi-Quickbooks";

if (isset($_GET['state']) && isset($_SESSION['oauth2state']) && isset($_GET['realmId']))
{
	if ($_GET['state'] == $_SESSION['oauth2state'])
	{
		$response = basicAuthRequest($urlAccessToken, "authorization_code", $_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			$token->access_token_expiry = time() + $token->expires_in;
			$token->realmId = $_GET['realmId'];
			// get company info
			$url = $sandbox_base . "/v3/company/" . $token->realmId . "/companyinfo/" . $token->realmId;
			$data = apiRequest($url, $token->access_token);
			if ($data['code'] == 200)
			{
				$token->CompanyInfo = $data['response']->CompanyInfo;
				print head($title, "Connected - click to continue", $token->CompanyInfo->CompanyName);
				$_SESSION[$cookie] = serialize($token);
				print footer("Disconnect", "");
			}
			else
			{
				print head($title, "Connected", "but failed to retrieve company info");
				$_SESSION[$cookie] = serialize($token);
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
		unset($_SESSION['oauth2state']); 

		print head($title, "Error - invalid state");
		print '<pre>';
		print_r($_REQUEST);
		print_r($_SESSION);
		print '</pre>';
	}
}

// If we have a cookie, get the connection details
elseif (isset($_SESSION[$cookie]))
{
	if (isset($_REQUEST['operation']))
	{
		if($_REQUEST['operation'] == 'cookie')
		{
			$token = unserialize($_SESSION[$cookie]);
			print head("$title | cookie contents", "Connected");
			print '<pre>';
			print_r($token);
			print '</pre>';
			print footer("Disconnect", "");
		}
		elseif($_REQUEST['operation'] == 'revoke')
		{
			print head($title, "Disconnected");
			unset($_SESSION[$cookie]);
		}
		elseif($_REQUEST['operation'] == 'invoices')
		{
			$token = unserialize($_SESSION[$cookie]);
			$query = http_build_query(['query'=>"SELECT * from Invoice order by txndate desc"]);
			$url = "$sandbox_base/v3/company/" . $token->realmId . "/query?$query&minorversion=70";

			$data = apiRequest($url, $token->access_token);
			if ($data['code'] == 200)
			{
				print head("$title | invoices", "Home");
				$table = invoice_summary($data['response']->QueryResponse);
				print table_html($table);
				print footer("Disconnect", "");
			}
			else
			{
				print head("$title | invoices", "Error - query invoice");
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
		$token = unserialize($_SESSION[$cookie]);
		if ($now <  $token->access_token_expiry)
		{
			print head($title, "Home", $token->CompanyInfo->CompanyName);
			print generic_button("cookie", "Display cookie",['operation'=>'cookie'], "tertiary", "GET", "./");
			print generic_button("invoices", "Display invoices",['operation'=>'invoices'], "tertiary", "GET", "./");
		}
		else
		{
			print head($title, "Disconnected");
			unset($_SESSION['oauth2state']); 
			unset($_SESSION[$cookie]);
		}
		print footer("Disconnect", "");
	}
}
// If we don't have an authorization code then get one
elseif (!isset($_GET['code'])) {
	$state = getRandomState();

    // store state in the session.
    $_SESSION['oauth2state'] = $state;

    // display Connect to button
	print head($title);
	print generic_button("connect", $connect,['client_id'=>$client_id,
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