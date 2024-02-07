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
 * @param string $home Display this text on home button
 * @param string $subtitle Subtitle text
 * @return string
 */
function head($title, $home = "Home", $subtitle = "simple Google Calendar API interaction")
{
	$html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
	<html>
	<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>' . $title . 'Connect to Twitter</title>
	<link rel="stylesheet" href="/css/fonts.css">
	<link rel="stylesheet" href="/css/mini-default.css">
	<link rel="stylesheet" href="/css/style.css">
	<style>
		html { text-align: center;}
		pre { text-align: left;}
		input[type="submit"] {width: 90%;}
		textarea {width: 90%;}
		.card {margin: 0 auto;}
	</style>
	</head>
	';
	$html .= '<body>
	<header class="sticky">
	<H2>' . $title . '</h2>
	</header>
	<div class="container">
	<div class="card large">
	<a id="home" class="button primary" href="./">' . $home . '</a>
	<p>' . $subtitle . '</p>
	</div>
	';
	return $html;
}

/**
 * Returns HTML for the bottom of the screen including a "revoke" button
 *
 * @param string $button Button text
 * @param string $text Display text
 * @return string
 */
function footer($button, $text)
{
	$html = '<div class="footer"><div class="card large">' . $text . '<a class="button secondary" href="./?operation=revoke">' . $button . '</a></div></div></footer>';
	return $html;
}	

?>