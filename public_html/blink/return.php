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

print "<div class=\"card large warning\">
<h3 class=\"section\">Transaction</h3>
<p class=\"section\">
<strong>transaction_id:</strong> $id<br/>
<strong>note:</strong> $note<br/>
<strong>status:</strong> $status</p>
</div>\n";

$mdata = json_decode(urldecode($_GET["merchant_data"]));
$html = "<div class=\"card large error\"><h3 class=\"section\">merchant_data</h3>\n<p class=\"section\">";
foreach (get_object_vars($mdata) as $key => $value)
{
	$html .= "<strong>$key:</strong> $value<br/>\n";
}
$html .= "</p></div>\n";

print $html;

?>