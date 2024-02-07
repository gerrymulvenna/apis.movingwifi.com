<?php
error_reporting(-1);
session_start();
require "../functions.php";
require "credentials.php";  //api_key, client_id, client_secret, redirect_uri
$urlAuthorize = 'https://accounts.google.com/o/oauth2/v2/auth';
$urlAccessToken = 'https://oauth2.googleapis.com/token';
$urlResourceOwnerDetails = 'https://openidconnect.googleapis.com/v1/userinfo';
$scopes = ['openid','email','profile','https://www.googleapis.com/auth/calendar.events.public.readonly','https://www.googleapis.com/auth/calendar.events.owned.readonly','https://www.googleapis.com/auth/calendar.events.readonly'];

$title = "Google Calendar API Test";
// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {
	$state = getRandomState();

    // store state in the session.
    $_SESSION['oauth2state'] = $state;

    // display Connect to Google button
	print head($title);
	print '<div class="card large">
	<form method="GET" action="' . $urlAuthorize . '">
	<input type="hidden" name="client_id" value="' . $client_id . '">
	<input type="hidden" name="response_type" value="code">
	<input type="hidden" name="redirect_uri" value="' . $redirect_uri . '">
	<input type="hidden" name="scope" value="' . implode(' ', $scopes) . '">
	<input type="hidden" name="state" value="' . $state . '">
	<input class="tertiary" type="submit" value="Connect to Google">
	</form></div>';
}
// Check given state against previously stored one to mitigate CSRF attack
elseif (empty($_GET['state']) || empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }

    print head($title, "Error - invalid state");
	print '<pre>';
	print_r($_SESSION);
	print '</pre>';

}
else
{
	print head($title, "Connected");
	print '<pre>';
	print_r($_REQUEST);
	print_r($_SESSION);
	print '</pre>';
	print footer("Revoke access", "");
}

?>