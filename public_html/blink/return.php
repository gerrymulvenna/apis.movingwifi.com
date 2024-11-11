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
$html = "<dt>merchant_data</dt><dd><dl>\n";
foreach (get_object_vars($mdata) as $key => $value)
{
	$html .= "<dt>$key</dt><dd>$value</dd>\n";
}
$html .= "</dl></dd>\n";

print "<div class=\"card large\">
<dl>
<dt>transaction_id</dt>
<dd>$id</dd>
<dt>note</dt>
<dd>$note</dd>
<dt>status</dt>
<dd>$status</dd>
$html
</dl>
</div>\n";

?>