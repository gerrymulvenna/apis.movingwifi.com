<?php
// a simple Blink API example using PHP - notification receiver
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');

$handle = fopen("notification.log", "a");
fwrite($handle, json_encode($_REQUEST));
fclose($handle);

?>