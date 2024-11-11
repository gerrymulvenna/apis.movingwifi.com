<?php
// a simple Blink API example using PHP
error_reporting(-1);
//set Timezone
date_default_timezone_set('Europe/London');
require "../functions.php";
$title = "Blink payment sandbox";

print head($title, "Click to continue", "Payment response");
$id = $_GET["transaction_id"];
$note = urldecode($_GET["note"]);
$status = $_GET["status"];
$mdata = json_decode(urldecode($_GET["Merchant data"]));

print "<div class=\"card large warning\">
<h3 class=\"section\">Transaction</h3>
<p class=\"section\" style=\"text-align: left;\">
<strong>transaction_id:</strong> $id<br/>
<strong>note:</strong> $note<br/>
<strong>status:</strong> $status</p>
</div>\n";

$html = "<div class=\"card large error\"><h3 class=\"section\">merchant_data</h3>\n<p class=\"section\" style=\"text-align:left;\">";
foreach (get_object_vars($mdata) as $key => $value)
{
	$html .= "<strong>$key:</strong> $value<br/>\n";
}
$html .= "</p></div>\n";

print $html;

?>