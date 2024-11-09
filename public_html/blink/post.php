<?php
// a simple Blink API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";
// you will need to create the credentials.php file and define your unique credentials for this service
require "credentials.php";  //$secret_key, $api_key

// API details
$urlAccessToken = 'https://secure.blinkpayment.co.uk/api/pay/v1/tokens';
$api_base = 'https://secure.blinkpayment.co.uk';
$urlPaymentToken = 'https://gateway2.blinkpayment.co.uk/paymentform';

// service-specific strings
$title = "Blink";
$connect = "Get Blink Token";
$cookie = "movingwifi-blink";

print "Content-Type: application/json\n\n";

print json_encode($_POST);


?>