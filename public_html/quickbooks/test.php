<?php
// test PHP script
error_reporting(-1);
session_start();  //use session cookie for state 
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";

$title = "Test PHP script";
$cookie = "movingwifi_test";
$url = "https://charts.indylive.radio/showjson.php";

if (isset($_COOKIE[$cookie]))
{
	if (isset($_REQUEST['operation']))
	{
		if ($_REQUEST['operation'] == 'revoke')
		{
			setcookie($cookie, "", time()-3600, "/");
			print head($title . " REVOKED", "");
			print generic_button("home","Home",[],"primary",'GET','./test.php');
			print generic_button("restart","Restart",[],"tertiary",'GET','./test.php');
			print "</div></body></html>\n";
		}
		elseif ($_REQUEST['operation'] == 'increment')
		{
			$num = $_COOKIE[$cookie];
			setcookie($cookie, $num + 1, time()+3600, "/");
			print head($title . " INCREMENTED", "");
			print generic_button("home","Home",[],"primary",'GET','./test.php');
			print generic_button("increment","Increment",['operation'=>'increment'],"tertiary",'GET','./test.php');
			print generic_button("revoke","Revoke",['operation'=>'revoke'],"tertiary",'GET','./test.php');
			print "</div></body></html>\n";
		}
	}
	else
	{
		$num = $_COOKIE[$cookie];
		setcookie($cookie, $num ,time()+3600, "/");
		print head($title.sprintf(" %04d", $num), "");
		print generic_button("home","Home",[],"primary",'GET','./test.php');
		print generic_button("increment","Increment",['operation'=>'increment'],"tertiary",'GET','./test.php');
		print generic_button("revoke","Revoke",['operation'=>'revoke'],"tertiary",'GET','./test.php');
		print "</div></body></html>\n";
	}
}
else
{
	$data = apiTest($url);
	if ($data['code'] == 200)
	{
		$shows = $data['response'][0];
		setcookie($cookie, serialize($shows), strtotime('+6 months'), "/");
		print head($title, "Connected - click to continue", count(shows));
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
}


/**
 * sends an API call using GET method and without authentication
 *
 * @param string $url destination address
 * @param array $vars Associative array of variables to send with the request
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

    // Set the header, response, error and http code.
	$data = [];
    $data['response'] = json_decode($response);
    $data['error'] = $error;
    $data['code'] = $http_code;
	return $data;
}


?>