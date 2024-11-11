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
$mdata = json_decode(urldecode($_GET["merchant_data"]));
$html = "<tr><td style=\"text-align:center;\" colspan=\"2\">merchant_data</td></tr>\n";
foreach (get_object_vars($mdata) as $key => $value)
{
	$html .= "<tr><td style=\"text-align:right;\">$key</td><td>$value</td></tr>\n";
}

print "<table style=\"text-align:center;width:fit-content;\">
<tr><td style=\"text-align:right;\">transaction_id</td><td>$id</td></tr>
<tr><td style=\"text-align:right;\">note</td><td>$note</td></tr>
<tr><td style=\"text-align:right;\">status</td><td>$status</td></tr>
$html
</table>\n";

?>