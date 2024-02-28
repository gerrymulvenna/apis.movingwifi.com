<?php
error_reporting(-1);
session_start();
//set Timezone
date_default_timezone_set('Europe/London');

require "functions.php";

print head("Some simple API interactions");

?>
	<div class="row">
		<div class="col-sm-12">
			<p class="alignleft">This is a showcase project to present some simple examples of API interactions, where the code (written in PHP) is kept deliberately 
			minimalist and self-contained, in order to best demonstrate the flow from establishing the Oauth 2.0 connection to issuing an API call 
			and handling the response data.</p>
			<p class="alignleft">Getting that initial back and forth correct and your first API call working invariably requires more head-scratching and debugging
			than anticipated. Each example (accessed by the menu top right) implements the initial authenticated connection and presents one or two 
			examples of using the API, hopefully to make the learning curve a bit easier at the start.</p>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12 col-md-6 col-lg-3">
			<div class="card">
				<div class="section">
					<a href="/google" class="primary button">Google Calendar</a>
				</div>
				<div class="section")
					<p>Display your list of calendars and display a list of future events from a calendar.</p>
				</div>
			</div>
		</div>
		<div class="col-sm-12 col-md-6 col-lg-3">
	
					<td data-label="API"><a href="/google" class="primary button">Google Calendar</a></td><td data-label="Example use">Display your list of calendars and display a list of future events from a calendar</td>
				</tr>
			<div class="card">
				<div class="section">
					<a href="/quickbooks" class="primary button">Quickbooks</a>
				</div>
				<div class="section")
					<p>Display a summary table of invoices.</p>
				</div>
			</div>
		</div>
		<div class="col-sm-12 col-md-6 col-lg-3">
			<div class="card">
				<div class="section">
					<a href="/xero" class="primary button">Xero</a>
				</div>
				<div class="section")
					<p>List the Xero "tenants" and display customers and suppliers.</p>
				</div>
			</div>
		</div>
	</div>

	<p class="alignleft">Gerry Mulvenna</p>
	<a href="https://github.com/gerrymulvenna/apis.movingwifi.com" class="button tertiary">View the code on GitHub</a>
</div>
</body>
</html>