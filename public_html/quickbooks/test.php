<?php
// test PHP script
error_reporting(-1);
ini_set('session.use_cookies',0);
ini_set('session.use_only_cookies',0);
session_start();

//set Timezone
date_default_timezone_set('Europe/London');
require "../functions.php";

$title = "Test PHP script";
$cookie = "movingwifi_test";
$url = "https://charts.indylive.radio/showjson.php";
if (isset($_SESSION[$cookie]))
{
	$cvalue = $_SESSION[$cookie];
	session_write_close();
	setcookie($cookie, $cvalue, time()+3600, '/');
}

$data = apiTest($url);
if ($data['code'] == 200)
{
	$shows = $data['response'];
	$_SESSION[$cookie] = serialize($shows);
	print head($title, "Connected - click to continue", count($shows));
	print "<pre>\n";
	print_r ($shows);
	print "</pre>\n";
	print "</div></body></html>\n";
}
else
{
	print head($title, "Error retrieving shows", "");
	print "</div></body></html>\n";
}


/**
 * sends an API call using GET method without authentication
 *
 * @param string $url destination address
 */
function apiTest($url)
{
	// add required headers
	$headers= array('Content-Type: application/json');

    // Set up cURL options.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, "MOVINGWIFI_PHP/1.0");
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Set the response, error and http code.
	$data = [];
    $data['response'] = json_decode($response);
    $data['error'] = $error;
    $data['code'] = $http_code;
	return $data;
}


?>