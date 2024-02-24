<?php
error_reporting(-1);
session_start();
//set Timezone
date_default_timezone_set('Europe/London');

require "functions.php";

print head("Some simple API interactions");

?>
	<div class="card large">
	<p class="alignleft">This is a showcase project to present some simple examples of API interactions, where the code (written in PHP) is kept deliberately 
	minimalist and self-contained, in order to best demonstrate the flow from Authentication to API call and response handling.</p>
	<p>Getting that initial back and forth correct and your first API call working invariably requires more head-scratching and debugging
	than anticipated. Each example (accessed by the menu top right) implements the initial authenticated connection and presents one or two 
	examples of using the API, hopefully to make the learning curve a bit easier at the start.</p>
	
	<table><thead><tr><th data-label="API">API</th><th data-label="Example use">Example use</th></tr>
	<tbody>
		<tr>
			<td data-label="API"><a href="/google" class="primary button">Google Calendar</a></td><td data-label="Example use">Display your list of calendars and display a list of future events from a calendar</td>
		</tr>
		<tr>
			<td data-label="API"><a href="/quickbooks" class="primary button">Quickbooks</a></td><td data-label="Example use">Display a summary table of invoices</td>
		</tr>
	</tbody>
	</table>
		
			

	<p class="alignleft">Gerry Mulvenna</p>

	<a href="https://github.com/gerrymulvenna/apis.movingwifi.com" class="button tertiary">View the code on GitHub</a>
	</div>
</div>
</body>
</html>