<?php
/**
 * Returns a new random string to use as the state parameter in an
 * authorization flow.
 *
 * @param  int $length Length of the random string to be generated.
 * @return string
 */
function getRandomState($length = 32)
{
	// Converting bytes to hex will always double length. Hence, we can reduce
	// the amount of bytes by half to produce the correct length.
	return bin2hex(random_bytes($length / 2));
}

/**
 * Returns a new random string to use as PKCE code_verifier and
 * hashed as code_challenge parameters in an authorization flow.
 * Must be between 43 and 128 characters long.
 *
 * @param  int $length Length of the random string to be generated.
 * @return string
 */
function getRandomPkceCode($length = 64)
{
	return substr(strtr(base64_encode(random_bytes($length)), '+/', '-_'), 0, $length);
}

/**
 * Returns HTML for <head>  + start of <body> sections
 *
 * @param string $title Title text
 * @param string $home Display this text on home button, if blank don't include a home button
 * @param string $subtitle Subtitle text
 * @return string
 */
function head($title, $home = "", $subtitle = "simple API interaction")
{
	$html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
	<html>
	<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>' . $title . '</title>
	<link rel="stylesheet" href="/css/fonts.css">
	<link rel="stylesheet" href="/css/mini-default.css">
	<link rel="stylesheet" href="/css/style.css">
	<style>
		.card {margin: 0 auto;}
	</style>
	</head>
	';
	$html .= '<body>
	<header class="sticky">
		<div>
			<h3 class="headline">' . $title . '</h3>
			<label for="drawer-control" class="drawer-toggle persistent"></label> 
			<input type="checkbox" id="drawer-control" class="drawer persistent">
			<nav>
				<label for="drawer-control" class="drawer-close"></label>
				<a href="/">Start page</a> 
				<a href="/google/">Google Calendar</a> 
				<a href="/quickbooks/">Quickbooks</a>
				<a href="/xero/">Xero</a>
				<a href="https://github.com/gerrymulvenna/apis.movingwifi.com">View code on GitHub</a>
			</nav>
		</div>
	</header>
	<div class="container">';
	if (!empty($home))
	{
		$html .= '
		<div class="card large">
			<a id="home" class="button primary" href="./">' . $home . '</a>
			<p>' . $subtitle . '</p>
		</div>';
	}
	return $html;
}

/**
 * Returns HTML for the bottom of the screen including a button, eg labelled Disconnect
 *
 * @param string $button Button text
 * @param string $text Display text
 * @return string
 */
function footer($button, $text)
{
	$html = '
	<div class="footer">
		<div class="card large">' . $text . '<a class="button secondary" href="./?operation=revoke">' . $button . '</a></div>
	</div>
</div>';
	return $html;
}

/**
 * return HTML for a generic form with a submit button and hidden variables passed as an associative array
 *
 * @param string $id Used as ID and NAME in the form / submit button 
 * @param string $text Text used on the submit button 
 * @param array $vars Key / value pairs included in the form as HIDDEN fields
 * @param string $method POST or GET 
 * @param string $action Script to process the form
 * @return string HTML markup for a single button form with hidden fields
*/
function generic_button($id, $text, $vars, $class = "tertiary", $method = "GET", $action = "./")
{
	$html = '<div class="card large"><form action="' . $action . '" method="' . $method . '">';
	foreach ($vars as $key => $value)
	{
		$html .= '<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $value . '">';
	}
	$html .= '<input type="submit" name="' . $id . '" id="' . $id . '" value="' . $text . '" class="' . $class . '"></form></div>';
	return $html;
}
	
/**
 * uses cURL to issue a basic authenticated request
 *
 * @param string $url The destination address
 * @param string $grant_type Value of grant_type parameter in the request
 * @param string $code Code value
 * @param string $client_id user value
 * @param string $client_secret password value
 * @param string $callback the return_uri address
 */
function basicAuthRequest($url, $grant_type, $code, $client_id, $client_secret, $callback)
{
	$params = ['grant_type'=>$grant_type,'code'=>$code,'redirect_uri'=>$callback];
    // Set up cURL options.
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$eh = fopen('curl.log', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $eh);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $client_id . ':' . $client_secret);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, "MOVINGWIFI_PHP/1.0");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type' => 'application/x-www-form-urlencoded', 'Accept' => 'application/json']);
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

/**
 * sends an API call using GET method and Bearer authentication
 *
 * @param string $url destination address
 * @param string $access_token Access token 
 * @param array $vars Associative array of variables to send with the request
 */
function apiRequest($url, $access_token, $method = 'GET', $vars = [], $headers = [])
{
	// add required headers
	array_push($headers, 'Accept: application/json');
	array_push($headers, 'Authorization: Bearer ' . $access_token);
	array_push($headers, 'Content-Type: application/json');

    // Set up cURL options.
    $ch = curl_init();
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	$eh = fopen('curl.log', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $eh);
	if ($method == 'GET' && count($vars)>0)
	{
		$query = http_build_query($vars);
		if (strpos($url, '?'))
		{
			$url .= '&' . $query;
		}
		else
		{
			$url .= '?' . $query;
		}
	}
		
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "MOVINGWIFI_PHP/1.0");
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($method == 'POST')
	{
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($vars));
	}
	
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Set the header, response, error and http code.
	$data = [];
    $data['response'] = json_decode($response);
    $data['error'] = $error;
    $data['code'] = $http_code;
	return $data;
}


// generic function to return a HTML table for a two-dimensional array, where row 0 contains the fieldnames
function table_html($data)
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
			$html .= "<td data-label=\"$label\">$cell</td>\n";
		}
		$html .= "</tr>\n";
	}
	$html .= "</tbody></table>\n";
	return $html;
}

?>