<?php
error_reporting(-1);
session_start();
//set Timezone
date_default_timezone_set('Europe/London');

require "functions.php";

print head("some simple API interactions");

?>
	<div class="card large">
	<p class="alignleft">This is a showcase project to present some simple examples of API interactions, where the code (written in PHP) is kept deliberately 
	minimalist and self-contained, in order to best demonstrate the flow from Authentication to API call and response handling. Each example
	(accessed by the menu top right) implements the initial authenticated connection and present one or two examples of using the API.</p>
	
	<table><thead><tr><th>API</th><th>Example use</th></tr>
	<tbody>
		<tr>
			<td>Google Calendar</td><td>Display your list of calendars<br>Display a list of future events from a calendar</td>
		</tr>
		<tr>
			<td>Quickbooks</td><td>Display a summary table of invoices</td>
		</tr>
	</tbody>
	</table>
		
			

	<p class="alignleft">Gerry Mulvenna</p>

	<a href="https://github.com/gerrymulvenna/apis.movingwifi.com" class="button tertiary">View the code on GitHub</a>
	</div>
</div>
</body>
</html>