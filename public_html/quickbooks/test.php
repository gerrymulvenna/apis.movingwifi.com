<?php
// test PHP script
error_reporting(-1);
session_start();  //use session cookie for state 
//set Timezone
date_default_timezone_set('Europe/London');
$cookie = "movingwifi_Quickbooks";

setcookie($cookie,serialize($_COOKIE),time()+3600, "/");

print "<pre>\n";
print_r($_COOKIE);
print "</pre>\n";
?>