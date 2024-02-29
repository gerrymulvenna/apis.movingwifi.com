<?php
// test PHP script
error_reporting(-1);
session_start();  //use session cookie for state 
//set Timezone
date_default_timezone_set('Europe/London');

require "../functions.php";

$title = "Test PHP script";
$cookie = "movingwifi_test";

if (isset($_COOKIE[$cookie]))
{
	if (isset($_REQUEST['operation']))
	{
		if ($_REQUEST['operation'] == 'revoke')
		{
			setcookie($cookie, "", time()-3600, "/");
			print head($title, "Revoked");
			print "</div></body></html>\n";
		}
	}
	else
	{
		$num = $_COOKIE[$cookie];
		setcookie($cookie, $num + 1,time()+3600, "/");
		print head($title, sprintf("%04d", $num));
		print generic_button("revoke","Revoke",['operation'=>'revoke'],"tertiary",'GET','./test.php');
		print "</div></body></html>\n";
	}
}
else
{
	setcookie($cookie, 1, time()+3600, "/");
	print head($title,"Home"));
	print generic_button("revoke","Revoke",['operation'=>'revoke'],"tertiary",'GET','./test.php');
	print "</div></body></html>\n";
}
?>