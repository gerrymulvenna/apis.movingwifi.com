<?php
// test PHP script
error_reporting(-1);
session_start();  //use session cookie for state 
//set Timezone
date_default_timezone_set('Europe/London');

setcookie('gerry',serialize($_COOKIE),time()+3600, "/");

print "<pre>\n";
print_r($_COOKIE);
print "\n";
print strtotime('+6 months');
print "</pre>\n";
?>