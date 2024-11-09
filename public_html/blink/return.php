<?php
// a simple Blink API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

print json_encode($_REQUEST);

?>