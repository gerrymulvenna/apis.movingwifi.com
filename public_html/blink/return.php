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
$mdata = json_decode($_GET["merchant_data"]);

print "<table>
<tr><td>transaction_id</td><td>$id</td></tr>
<tr><td>note</td><td>$note</td></tr>
<tr><td>status</td><td>$status</td></tr>
<tr><td>merchant_data</td><td>" . print_r($mdata) . "</td></tr>
</table>\n";

?>