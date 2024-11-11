<?php
// a simple Blink API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');
require "../functions.php";
$title = "Blink payment sandbox";

print head($title, "Click to continue", "Payment response");
$id = $_GET["transaction_id"];
$note = $_GET["note"];
$status = $_GET["status"];
$mdata = $_GET["merchant_data"];

print "<table style=\"width:fit-content;\">
<tr><td style=\"text-align:right;\">transaction_id</td><td>$id</td></tr>
<tr><td style=\"text-align:right;\">note</td><td>$note</td></tr>
<tr><td style=\"text-align:right;\">status</td><td>$status</td></tr>
<tr><td style=\"text-align:right;\">merchant_data</td><td>" . $mdata . "</td></tr>
</table>\n";

?>