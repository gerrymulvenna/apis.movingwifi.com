<?php
// a simple Xero API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php and define your unique credentials for this service
require "credentials.php";  //$client_id, $client_secret, $redirect_uri

// API details
$urlAuthorize = "https://login.xero.com/identity/connect/authorize";
$urlAccessToken = "https://identity.xero.com/connect/token";
$urlConnections = "https://api.xero.com/connections";
$api_base = "https://api.xero.com/api.xro/2.0/";
$scopes = ["offline_access", "accounting.transactions", "accounting.contacts", "openid", "profile", "email"];

// service-specific strings
$title = "Xero";
$connect = "Connect to Xero";
$cookie = "movingwifi-Xero";

if (isset($_GET['state']) && isset($_COOKIE['oauth2state']))
{
	if ($_GET['state'] == $_COOKIE['oauth2state'])
	{
		$response = basicAuthRequest($urlAccessToken, "authorization_code", $_REQUEST['code'], $client_id, $client_secret, $redirect_uri);
		if ($response['code'] == 200)
		{
			$token = $response['response'];
			$cdata['access_token_expiry'] = time() + $token->expires_in;
			$cdata['refresh_token_expiry'] = strtotime('+60 days');
			$cdata['token'] = $token;
			// get tenants
			$tenants = apiRequest($urlConnections, $token->access_token);
			if ($tenants['code'] == 200)
			{
				$cdata['tenants'] = $tenants['response'];
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected - click to continue");
				print footer("Disconnect", "");
			}
			else
			{
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head($title, "Connected", "but failed to retrieve tenants info");
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
		setcookie('oauth2state', "", time() - 3600, '/');
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
			setcookie($cookie, "", time()-3600, '/');
			print head($title, "Disconnected");
		}
		elseif ($_REQUEST['operation'] == 'tenant')
		{
			$tenantId = $_REQUEST['tenantId'];
			$tenantName = $_REQUEST['tenantName'];
			print head("$title | $tenantName","Home");
			print generic_button('customers','Display Customers', ['operation'=>'customers','tenantId'=>$tenantId]);
			print generic_button('suppliers','Display Suppliers', ['operation'=>'suppliers','tenantId'=>$tenantId]);
			print generic_button("cookie", "Display cookie",['operation'=>'cookie']);
			print footer("Disconnect", "");
		}
		elseif ($_REQUEST['operation'] == 'customers')
		{
			$url = $api_base . "contacts";
			$cdata = unserialize($_COOKIE[$cookie]);
			$tenantId = $_REQUEST['tenantId'];
			print head("$title | Customers","Home");
			# call the API - the xero API needs xero-tenant-id in the header
			$data = apiRequest($url, $cdata['token']->access_token, 'GET', ['order'=>'Name','where'=>'IsCustomer=true'], ["xero-tenant-id: $tenantId"]);  
			if ($data['code'] == 200)
			{
				$table = contacts_summary($data['response']);
				print table_html($table);
				print footer("Disconnect", "");
			}
			else
			{
				print '<pre>';
				print_r($data);
				print '</pre>';
				print footer("Disconnect", "");
			}
		}
		elseif ($_REQUEST['operation'] == 'suppliers')
		{
			$url = $api_base . "contacts";
			$cdata = unserialize($_COOKIE[$cookie]);
			$tenantId = $_REQUEST['tenantId'];
			print head("$title | Suppliers","Home");
			# call the API - the xero API needs xero-tenant-id in the header
			$data = apiRequest($url, $cdata['token']->access_token, 'GET', ['order'=>'Name','where'=>'IsSupplier=true'], ["xero-tenant-id: $tenantId"]);  
			if ($data['code'] == 200)
			{
				$table = contacts_summary($data['response']);
				print table_html($table);
				print footer("Disconnect", "");
			}
			else
			{
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
			$response = basicRefreshRequest($urlAccessToken, "refresh_token", $cdata['token']->refresh_token, $client_id, $client_secret);
			if ($response['code'] == 200)
			{
				$token = $response['response'];
				$cdata['access_token_expiry'] = time() + $token->expires_in;
				$cdata['refresh_token_expiry'] = time() + $token->x_refresh_token_expires_in;
				$cdata['token'] = $token;
				setcookie($cookie, serialize($cdata), strtotime('+6 months'), '/');
				print head("$title | tenants", "Refreshed");
				foreach ($token->tenants as $tenant)
				{
					print generic_button("tenant",$tenant->tenantName, ['operation'=>'tenant','tenantId'=>$tenant->tenantId, 'tenantName'=>$tenant->tenantName], 'primary');
				}
				print generic_button("cookie", "Display cookie",['operation'=>'cookie']);
			}
			else
			{
				setcookie($cookie,"", time() - 3600, "/");  //delete cookie
				setcookie('oauth2state',"", time() - 3600, "/");  //delete cookie
				print head($title, "Refresh failed - click to continue");
			}
		}
		else
		{
			print head("$title | tenants", "Home");
			foreach ($token->tenants as $tenant)
			{
				print generic_button("tenant",$tenant->tenantName, ['operation'=>'tenant','tenantId'=>$tenant->tenantId, 'tenantName'=>$tenant->tenantName], 'primary');
			}
			print generic_button("cookie", "Display cookie",['operation'=>'cookie']);
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

    // store state in the session.
	setcookie('oauth2state', $state, time() + 600, '/');

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


function contacts_summary($response)
{
	$i = 0;
	// field names in first row
	$table[$i] = ['ContactID','Name','IsSupplier','IsCustomer','DefaultCurrency'];
	foreach ($response->Contacts as $contact)
	{
		$i++;
		$table[$i][] =(property_exists($contact, 'ContactID')) ? $contact->ContactID : "";
		$table[$i][] =(property_exists($contact, 'Name')) ? $contact->Name : "";
		$table[$i][] =(property_exists($contact, 'IsSupplier')) ? $contact->IsSupplier : "";
		$table[$i][] =(property_exists($contact, 'IsCustomer')) ? $contact->IsCustomer : "";
		$table[$i][] =(property_exists($contact, 'DefaultCurrency')) ? $contact->DefaultCurrency : "";
	}
	return $table;
}	

?>