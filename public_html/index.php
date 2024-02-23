<?php
error_reporting(-1);
session_start();
//set Timezone
date_default_timezone_set('Europe/London');

require "functions.php";

print head("some simple API interactions");

?>
	<div class="card large alignleft">
	<p>This is a showcase project to present some simple examples of API interactions, where the code (written in PHP) is deliberately 
	kept relatively minimalist and self-contained, in order to best demonstrate the flow from Authentication to API call and response handling.</p>

	<p>Gerry Mulvenna</p>

	<a href="https://github.com/gerrymulvenna/apis.movingwifi.com" class="button">View on GitHub</a>
	</div>
</div>
</body>
</html>